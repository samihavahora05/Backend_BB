<?php
ignore_user_abort(true);
set_time_limit(0);

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CmsCollege;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

$urls = [
    "IIT Bombay" => "https://upload.wikimedia.org/wikipedia/en/1/1d/Indian_Institute_of_Technology_Bombay_Logo.svg",
    "BITS Pilani" => "https://upload.wikimedia.org/wikipedia/en/d/d3/BITS_Pilani-Logo.svg",
    "NIT Trichy" => "https://upload.wikimedia.org/wikipedia/en/f/f9/National_Institute_of_Technology%2C_Tiruchirappalli_Logo.png",
    "VIT Vellore" => "https://upload.wikimedia.org/wikipedia/en/c/c5/Vellore_Institute_of_Technology_seal_2017.svg",
    "Sigma University" => "https://t3.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=http://sigmauniversity.ac.in&size=128",
    "Parul University" => "https://upload.wikimedia.org/wikipedia/en/1/1c/Parul_University_logo.png",
    "ITM Vocational University" => "https://upload.wikimedia.org/wikipedia/en/4/4b/ITM_Vocational_University_logo.png",
    "RMS Polytechnic" => "https://t3.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=http://rms.edu.in&size=128",
    "Chandigarh University" => "https://upload.wikimedia.org/wikipedia/commons/1/13/Chandigarh_University_Seal.png",
    "Jain University" => "https://upload.wikimedia.org/wikipedia/en/a/a2/Jain_University_logo.png",
    "Shoolini University" => "https://upload.wikimedia.org/wikipedia/en/b/b5/Shoolini_University_logo.png",
    "GLA University" => "https://upload.wikimedia.org/wikipedia/en/6/6d/GLA_University_logo.png",
    "Online Manipal" => "https://upload.wikimedia.org/wikipedia/en/1/13/Manipal_Academy_of_Higher_Education_logo.png",
    "UPES" => "https://upload.wikimedia.org/wikipedia/en/1/10/UPES_Logo.png",
    "Mangalayatan University" => "https://upload.wikimedia.org/wikipedia/en/a/a2/Mangalayatan_University_Logo.png",
    "Lovely Professional University (LPU)" => "https://upload.wikimedia.org/wikipedia/en/8/87/Lovely_Professional_University_logo.png",
    "Amity University" => "https://upload.wikimedia.org/wikipedia/en/d/d5/Amity_University_logo.svg",
    "Uttaranchal University" => "https://upload.wikimedia.org/wikipedia/en/2/23/Uttaranchal_University_Logo.png",
    "VGU Jaipur" => "https://upload.wikimedia.org/wikipedia/en/4/49/Vivekananda_Global_University_logo.png",
    "D.Y Patil University" => "https://upload.wikimedia.org/wikipedia/en/4/47/DY_Patil_University_logo.png",
    "Sharda University" => "https://upload.wikimedia.org/wikipedia/en/1/14/Sharda_University_Logo.png"
];

$colleges = CmsCollege::all();
$count = 0;

foreach ($colleges as $college) {
    if (!isset($urls[$college->name])) continue;

    if (strpos($college->logo_url, '/storage/') === 0) {
        echo "Skipping {$college->name}, already downloaded.\n";
        continue;
    }

    $url = $urls[$college->name];
    echo "Processing {$college->name}... URL: {$url}\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BlueboxxBot/1.0 (' . uniqid() . '; admin@blueboxx.in) Mozilla/5.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode == 200 && $imageData && strlen($imageData) > 100) {
        $ext = 'png';
        if (strpos($url, '.svg') !== false) $ext = 'svg';
        if (strpos($url, '.jpg') !== false) $ext = 'jpg';
        
        $filename = 'colleges/' . Str::slug($college->name) . '-' . time() . '.' . $ext;
        Storage::disk('public')->put($filename, $imageData);
        
        $college->logo_url = '/storage/' . $filename;
        $college->save();
        echo "Saved {$college->name} to {$filename}!\n";
        $count++;
    } else {
        echo "Failed to download {$college->name} (HTTP $httpCode, Error: $error).\n";
    }
    
    // Avoid Wikipedia 429
    if (strpos($url, 'wikimedia') !== false) {
        sleep(2);
    }
}

echo "Done! Updated {$count} colleges.\n";
