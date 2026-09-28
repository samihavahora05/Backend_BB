const { execSync } = require('child_process');
const fs = require('fs');

console.log('Running mobile lighthouse...');
try {
  execSync('npx -y lighthouse https://www.blueboxx.in/services/ --output=json --output-path="c:\\Users\\Lenovo\\Documents\\Downloads\\Frontend_BB_fixed_v4\\audit\\lighthouse-mobile.json" --chrome-flags="--headless=new --no-sandbox" --quiet', { stdio: 'inherit', timeout: 180000 });
} catch (e) {
  console.log('Mobile error:', e.message);
}

console.log('Running desktop lighthouse...');
try {
  execSync('npx -y lighthouse https://www.blueboxx.in/services/ --preset=desktop --output=json --output-path="c:\\Users\\Lenovo\\Documents\\Downloads\\Frontend_BB_fixed_v4\\audit\\lighthouse-desktop.json" --chrome-flags="--headless=new --no-sandbox" --quiet', { stdio: 'inherit', timeout: 180000 });
} catch (e) {
  console.log('Desktop error:', e.message);
}

console.log('All lighthouse runs completed.');
