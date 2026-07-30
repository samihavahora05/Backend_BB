import fs from 'fs';
import path from 'path';

// Clear Laravel route cache before running
try {
  const routeCache = path.join(process.cwd(), 'bootstrap', 'cache', 'routes-v7.php');
  if (fs.existsSync(routeCache)) {
    fs.unlinkSync(routeCache);
    console.log('✅ Cleared Laravel route cache (routes-v7.php)');
  }
} catch (e) { }

const API_URL = 'http://127.0.0.1:8000/api';

async function request(endpoint, method = 'GET', data = null, token = null) {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  };
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const options = { method, headers };
  if (data) options.body = JSON.stringify(data);

  try {
    const res = await fetch(`${API_URL}${endpoint}`, options);
    const text = await res.text();
    try {
      return { status: res.status, data: JSON.parse(text) };
    } catch(e) {
      return { status: res.status, data: text };
    }
  } catch (err) {
    return { status: 0, data: err.message };
  }
}

async function runTests() {
  console.log('--- STARTING BLUEBOXX FULL API E2E VALIDATION ---\n');
  
  let passed = 0;
  let failed = 0;
  let summary = { pass: [], fail: [] };

  const assert = (condition, title, response = null) => {
    if (condition) {
      console.log(`✅ PASS: ${title}`);
      passed++;
      summary.pass.push(title);
    } else {
      console.error(`❌ FAIL: ${title}`);
      if (response) {
        console.error(`   Output: ${typeof response.data === 'string' ? response.data.substring(0, 200) : JSON.stringify(response.data).substring(0, 300)}`);
      }
      failed++;
      summary.fail.push(title);
    }
  };

  const tokens = {};
  const users = {};
  const randStr = Math.random().toString(36).substring(7);
  const password = 'Password123!';

  console.log('1. AUTO-PROVISIONING TEST USERS');
  
  // Register Student
  const studentEmail = `student_${randStr}@test.com`;
  const regStudent = await request('/register', 'POST', {
    name: 'Test Student',
    email: studentEmail,
    phone: `919${Math.floor(Math.random() * 10000000)}`,
    password: password,
    password_confirmation: password,
    role: 'student'
  });
  assert(regStudent.status === 201 || regStudent.status === 200, 'Register Student (Role: student)', regStudent);
  if (regStudent.data && regStudent.data.token) tokens.student = regStudent.data.token;

  // Register Company
  const companyEmail = `company_${randStr}@test.com`;
  const regCompany = await request('/register', 'POST', {
    name: 'Test Company',
    email: companyEmail,
    phone: `918${Math.floor(Math.random() * 10000000)}`,
    password: password,
    password_confirmation: password,
    role: 'company'
  });
  assert(regCompany.status === 201 || regCompany.status === 200, 'Register Company (Role: company)', regCompany);

  // Login Admin (from seeder)
  const loginAdmin = await request('/login', 'POST', {
    email: 'admin@blueboxx.in',
    password: 'password'
  });
  assert(loginAdmin.status === 200, 'Login Admin (admin@blueboxx.in)', loginAdmin);
  if (loginAdmin.data && loginAdmin.data.token) tokens.admin = loginAdmin.data.token;


  console.log('\n2. TESTING PUBLIC ENDPOINTS');
  const publicEndpoints = [
    { url: '/public/courses', name: 'Get Courses List' },
    { url: '/public/jobs', name: 'Get Jobs List' },
    { url: '/public/internships', name: 'Get Internships List' },
    { url: '/public/blogs', name: 'Get Blogs List' },
    { url: '/public/experts', name: 'Get Experts List' },
    { url: '/public/stats', name: 'Get Platform Stats' },
  ];

  for (const ep of publicEndpoints) {
    const res = await request(ep.url, 'GET');
    assert(res.status === 200, `Public API: ${ep.name} [GET ${ep.url}]`, res);
  }

  console.log('\n3. TESTING STUDENT ENDPOINTS');
  if (tokens.student) {
    const studentEndpoints = [
      { url: '/me', name: 'Get Profile' },
      { url: '/student/dashboard', name: 'Get Student Dashboard Metrics' },
      { url: '/student/courses', name: 'Get Student Courses' },
      { url: '/student/internships', name: 'Get Student Internships' },
    ];
    for (const ep of studentEndpoints) {
      const res = await request(ep.url, 'GET', null, tokens.student);
      assert(res.status === 200, `Student API: ${ep.name} [GET ${ep.url}]`, res);
    }
  } else {
    console.log('⚠️ Skipping Student Endpoints - No Token');
  }


  console.log('\n4. TESTING ADMIN ENDPOINTS & RBAC');
  if (tokens.admin) {
    const adminEndpoints = [
      { url: '/me', name: 'Get Admin Profile' },
      { url: '/admin/dashboard/summary', name: 'Get Admin Dashboard Summary' },
      { url: '/admin/users?role=student', name: 'List Students' },
      { url: '/admin/settings', name: 'Get Settings' },
    ];
    for (const ep of adminEndpoints) {
      const res = await request(ep.url, 'GET', null, tokens.admin);
      assert(res.status === 200, `Admin API: ${ep.name} [GET ${ep.url}]`, res);
    }
    
    // Test RBAC Security
    const rbacRes = await request('/admin/dashboard/summary', 'GET', null, tokens.student);
    assert(rbacRes.status === 403 || rbacRes.status === 401, `RBAC Check: Student denied from Admin Summary [Expected 403, Got ${rbacRes.status}]`, rbacRes);

  } else {
    console.log('⚠️ Skipping Admin Endpoints - No Token (Ensure database is seeded!)');
  }

  console.log('\n=======================================');
  console.log('FINAL VALIDATION REPORT');
  console.log('=======================================');
  console.log(`✅ Total Passed: ${passed}`);
  console.log(`❌ Total Failed: ${failed}`);
  
  if (failed > 0) {
    console.log('\nFAILED ENDPOINTS:');
    summary.fail.forEach(f => console.log(`- ${f}`));
    process.exit(1);
  } else {
    console.log('\n🎉 ALL APIS PASSED SUCCESSFULLY!');
  }
}

runTests().catch(err => {
  console.error("FATAL SCRIPT ERROR:", err);
});
