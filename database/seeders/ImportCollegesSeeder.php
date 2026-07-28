<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CmsCollege;
use Illuminate\Support\Str;

class ImportCollegesSeeder extends Seeder
{
    public function run()
    {
        // Remove the unwanted "Top University" again just in case
        CmsCollege::where('name', 'like', '%Top%University%')->delete();
        CmsCollege::where('name', 'Top University')->delete();

        $colleges = [
            [
                "name" => "IIT Bombay",
                "location" => "Mumbai, India",
                "logo_url" => "https://logo.clearbit.com/iitb.ac.in"
            ],
            [
                "name" => "BITS Pilani",
                "location" => "Pilani, India",
                "logo_url" => "https://logo.clearbit.com/bits-pilani.ac.in"
            ],
            [
                "name" => "NIT Trichy",
                "location" => "Trichy, India",
                "logo_url" => "https://logo.clearbit.com/nitt.edu"
            ],
            [
                "name" => "VIT Vellore",
                "location" => "Vellore, India",
                "logo_url" => "https://logo.clearbit.com/vit.ac.in"
            ],
            [
                "name" => "Sigma University",
                "location" => "Vadodara, Gujarat",
                "logo_url" => "https://sigmauniversity.ac.in/wp-content/uploads/2023/12/logo-sigma-2023.png"
            ],
            [
                "name" => "Parul University",
                "location" => "Vadodara, Gujarat",
                "logo_url" => "https://logo.clearbit.com/paruluniversity.ac.in"
            ],
            [
                "name" => "ITM Vocational University",
                "location" => "Vadodara, Gujarat",
                "logo_url" => "https://logo.clearbit.com/itm.edu"
            ],
            [
                "name" => "RMS Polytechnic",
                "location" => "Vadodara, Gujarat",
                "logo_url" => "https://rms.edu.in/wp-content/uploads/2021/08/RMS-Logo-1.png"
            ],
            [
                "name" => "Chandigarh University",
                "location" => "India",
                "logo_url" => "https://www.blueboxx.in/wp-content/uploads/2024/10/Chandigrah-university.png"
            ],
            [
                "name" => "Jain University",
                "location" => "Bangalore, India",
                "logo_url" => "https://www.blueboxx.in/wp-content/uploads/2024/10/jain_university_logo_freelogovectors.net_-1.png"
            ],
            [
                "name" => "Shoolini University",
                "location" => "Himachal Pradesh, India",
                "logo_url" => "https://www.blueboxx.in/wp-content/uploads/2024/10/Shoolini_University_of_Biotechnology_and_Management_Sciences_logo.png"
            ],
            [
                "name" => "GLA University",
                "location" => "Mathura, India",
                "logo_url" => "https://www.blueboxx.in/wp-content/uploads/2024/10/gla-1024x359.png"
            ],
            [
                "name" => "Online Manipal",
                "location" => "India",
                "logo_url" => "https://www.blueboxx.in/wp-content/uploads/2024/10/online-manipal-logo-for-homepage.png"
            ],
            [
                "name" => "UPES",
                "location" => "Dehradun, India",
                "logo_url" => "https://www.blueboxx.in/wp-content/uploads/2024/10/upes-logo-black.png"
            ],
            [
                "name" => "Mangalayatan University",
                "location" => "Aligarh, India",
                "logo_url" => "https://www.blueboxx.in/wp-content/uploads/2024/10/Mnaglatayn-white-logo-1.png"
            ],
            [
                "name" => "Lovely Professional University (LPU)",
                "location" => "Punjab, India",
                "logo_url" => "https://logo.clearbit.com/lpu.in"
            ],
            [
                "name" => "Amity University",
                "location" => "Noida, India",
                "logo_url" => "https://logo.clearbit.com/amity.edu"
            ],
            [
                "name" => "Uttaranchal University",
                "location" => "Dehradun, India",
                "logo_url" => "https://logo.clearbit.com/uudoon.in"
            ],
            [
                "name" => "VGU Jaipur",
                "location" => "Jaipur, India",
                "logo_url" => "https://logo.clearbit.com/vgu.ac.in"
            ],
            [
                "name" => "D.Y Patil University",
                "location" => "Navi Mumbai, India",
                "logo_url" => "https://logo.clearbit.com/dypatil.edu"
            ],
            [
                "name" => "Sharda University",
                "location" => "Greater Noida, India",
                "logo_url" => "https://logo.clearbit.com/sharda.ac.in"
            ]
        ];

        foreach ($colleges as $c) {
            $existing = CmsCollege::where('name', $c['name'])->first();
            if (!$existing) {
                CmsCollege::create([
                    'name' => $c['name'],
                    'location' => $c['location'],
                    'logo_url' => $c['logo_url'],
                    'slug' => Str::slug($c['name'])
                ]);
            } else {
                $existing->update([
                    'logo_url' => $c['logo_url'],
                    'location' => $c['location']
                ]);
            }
        }
    }
}
