<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OnlineUniversitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $universities = [
            [
                'name' => 'Lovely Professional University (LPU Online)',
                'location' => 'Phagwara, Punjab',
                'website_url' => 'https://lpuonline.com',
                'is_ugc_approved' => true,
                'naac_grade' => 'A++',
                'nirf_ranking' => 'Top 50',
                'is_wes_approved' => true,
                'degree_types' => ['BCA', 'MCA', 'MBA', 'BBA', 'M.Sc'],
                'popular_courses' => ['Data Science', 'Full Stack Development', 'Marketing', 'Finance'],
            ],
            [
                'name' => 'Amity University Online',
                'location' => 'Noida, Uttar Pradesh',
                'website_url' => 'https://amityonline.com',
                'is_ugc_approved' => true,
                'naac_grade' => 'A+',
                'nirf_ranking' => 'Top 100',
                'is_wes_approved' => true,
                'degree_types' => ['BCA', 'MCA', 'MBA', 'BBA', 'B.Com', 'M.Com'],
                'popular_courses' => ['Machine Learning', 'Cyber Security', 'HR Management', 'Business Analytics'],
            ],
            [
                'name' => 'JAIN (Deemed-to-be University) Online',
                'location' => 'Bengaluru, Karnataka',
                'website_url' => 'https://onlinejain.com',
                'is_ugc_approved' => true,
                'naac_grade' => 'A++',
                'nirf_ranking' => 'Top 100',
                'is_wes_approved' => true,
                'degree_types' => ['MCA', 'MBA', 'BBA', 'B.Com'],
                'popular_courses' => ['Artificial Intelligence', 'Cloud Computing', 'Finance', 'Marketing'],
            ],
            [
                'name' => 'Chandigarh University Online',
                'location' => 'Mohali, Punjab',
                'website_url' => 'https://onlinecu.in',
                'is_ugc_approved' => true,
                'naac_grade' => 'A+',
                'nirf_ranking' => 'Top 50',
                'is_wes_approved' => true,
                'degree_types' => ['BCA', 'MCA', 'MBA', 'B.A.', 'M.A.'],
                'popular_courses' => ['Journalism', 'Data Science', 'Management', 'IT'],
            ],
            [
                'name' => 'Uttaranchal University Online',
                'location' => 'Dehradun, Uttarakhand',
                'website_url' => 'https://uttaranchaluniversityonline.com',
                'is_ugc_approved' => true,
                'naac_grade' => 'A+',
                'nirf_ranking' => '',
                'is_wes_approved' => true,
                'degree_types' => ['MBA', 'MCA', 'BBA', 'BCA'],
                'popular_courses' => ['Data Analytics', 'Marketing', 'Finance'],
            ],
            [
                'name' => 'Vivekananda Global University (VGU) Online',
                'location' => 'Jaipur, Rajasthan',
                'website_url' => 'https://vguonline.com',
                'is_ugc_approved' => true,
                'naac_grade' => 'A+',
                'nirf_ranking' => '',
                'is_wes_approved' => false,
                'degree_types' => ['MBA', 'MCA', 'BBA', 'BCA'],
                'popular_courses' => ['Business Analytics', 'Digital Marketing'],
            ],
            [
                'name' => 'UPES Online',
                'location' => 'Dehradun, Uttarakhand',
                'website_url' => 'https://upesonline.ac.in',
                'is_ugc_approved' => true,
                'naac_grade' => 'A',
                'nirf_ranking' => 'Top 100',
                'is_wes_approved' => true,
                'degree_types' => ['MBA', 'BBA', 'Certificate'],
                'popular_courses' => ['Oil & Gas Management', 'Power Management', 'Logistics'],
            ],
            [
                'name' => 'Dr. D. Y. Patil Vidyapeeth Online',
                'location' => 'Pune, Maharashtra',
                'website_url' => 'https://dpuonline.com',
                'is_ugc_approved' => true,
                'naac_grade' => 'A++',
                'nirf_ranking' => 'Top 50',
                'is_wes_approved' => true,
                'degree_types' => ['MBA', 'BBA'],
                'popular_courses' => ['Hospital Administration', 'Marketing Management', 'HR'],
            ],
            [
                'name' => 'GLA University Online',
                'location' => 'Mathura, Uttar Pradesh',
                'website_url' => 'https://glaonline.com',
                'is_ugc_approved' => true,
                'naac_grade' => 'A',
                'nirf_ranking' => '',
                'is_wes_approved' => false,
                'degree_types' => ['BBA', 'B.Com', 'MBA'],
                'popular_courses' => ['Finance', 'Marketing', 'Banking'],
            ],
            [
                'name' => 'Sharda University Online',
                'location' => 'Greater Noida, Uttar Pradesh',
                'website_url' => 'https://shardaonline.ac.in',
                'is_ugc_approved' => true,
                'naac_grade' => 'A+',
                'nirf_ranking' => 'Top 100',
                'is_wes_approved' => true,
                'degree_types' => ['BBA', 'BCA', 'MBA', 'MCA'],
                'popular_courses' => ['Data Science', 'Marketing', 'Finance'],
            ],
            [
                'name' => 'Online Manipal University',
                'location' => 'Jaipur / Manipal',
                'website_url' => 'https://onlinemanipal.com',
                'is_ugc_approved' => true,
                'naac_grade' => 'A+',
                'nirf_ranking' => 'Top 100',
                'is_wes_approved' => true,
                'degree_types' => ['MBA', 'MCA', 'BBA', 'BCA', 'M.Sc'],
                'popular_courses' => ['Data Science', 'Business Analytics', 'IT'],
            ],
            [
                'name' => 'Shoolini University',
                'location' => 'Solan, Himachal Pradesh',
                'website_url' => 'https://shoolinionline.com',
                'is_ugc_approved' => true,
                'naac_grade' => 'A+',
                'nirf_ranking' => 'Top 100',
                'is_wes_approved' => false,
                'degree_types' => ['MBA', 'BBA'],
                'popular_courses' => ['Marketing', 'HR', 'Finance'],
            ]
        ];

        $dummyFullDesc = "Blueboxx DA partners with India's leading online universities to provide UGC-approved degree programs, industry-aligned curricula, placement assistance, and flexible learning opportunities for students and working professionals.\n\nOur online degree programs are designed to offer the same rigorous academics as on-campus degrees, with the flexibility to learn from anywhere at your own pace. With interactive live sessions, recorded lectures, and dedicated mentorship, you'll be well-prepared for your career.";
        
        $dummyAdmission = "1. Application Form: Fill out the online application form on our portal.\n2. Document Verification: Submit your educational certificates, ID proof, and photographs.\n3. Fee Payment: Pay the admission fee securely online.\n4. Enrollment: Once verified, you will receive your login credentials to the LMS.";

        $dummyPlacement = "Dedicated Career Services Cell that provides placement assistance to all eligible students. Activities include resume building workshops, interview preparation, mock interviews, and virtual career fairs with top recruiting companies in India and globally.";

        foreach ($universities as $index => $uni) {
            DB::table('cms_colleges')->insert([
                'name' => $uni['name'],
                'slug' => Str::slug($uni['name']) . '-' . time() . '-' . $index,
                'location' => $uni['location'],
                'website_url' => $uni['website_url'],
                
                'is_ugc_approved' => $uni['is_ugc_approved'],
                'naac_grade' => $uni['naac_grade'],
                'nirf_ranking' => $uni['nirf_ranking'],
                'is_wes_approved' => $uni['is_wes_approved'],
                
                'degree_types' => json_encode($uni['degree_types']),
                'popular_courses' => json_encode($uni['popular_courses']),
                
                'short_description' => "UGC-approved online degree programs from " . $uni['name'] . " with industry-aligned curriculum.",
                'full_description' => $dummyFullDesc,
                'admission_process' => $dummyAdmission,
                'placement_support' => $dummyPlacement,
                
                'duration' => '2 - 3 Years',
                'eligibility' => '10+2 / Graduation (depends on program)',
                'is_featured' => ($index < 4) ? 1 : 0, // Feature top 4
                'status' => 'published',
                
                // Fetch logos using clearbit
                'logo_url' => 'https://logo.clearbit.com/' . parse_url($uni['website_url'], PHP_URL_HOST),
                
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
