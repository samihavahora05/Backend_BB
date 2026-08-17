<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('cms_companies')) {
            $companies = [
                ['name' => 'Sawariya Solution', 'industry' => 'Game Development Services', 'logo' => '/logo/sawariya.png'],
                ['name' => 'Jash Packaging Co', 'industry' => 'SMEs & Startups', 'logo' => '/logo/Jashpackaging.jpeg'],
                ['name' => 'AATAPI Wonderland', 'industry' => 'Entertainment & Hospitality', 'logo' => '/logo/aatapi.png'],
                ['name' => 'Speedline Taxis', 'industry' => 'Transport & Logistics', 'logo' => '/logo/speedline.png'],
                ['name' => 'Pandits Cafe And Restaurant', 'industry' => 'Food & Hospitality', 'logo' => '/logo/pandit%20rasturant.jpg'],
                ['name' => 'Aldar International', 'industry' => 'Business Consulting & Execution', 'logo' => '/logo/aldar.png'],
                ['name' => 'Essar Group', 'industry' => 'Infrastructure & Energy', 'logo' => '/logo/essar.png'],
                ['name' => 'Nexrise Aac Blocks', 'industry' => 'Manufacturing & Building', 'logo' => '/logo/nexrise.png'],
                ['name' => 'Packman', 'industry' => 'Bespoke Packaging', 'logo' => '/logo/packman.png'],
                ['name' => 'ADF Aroma De France', 'industry' => 'Cosmetics & FMCG', 'logo' => '/logo/adf.png'],
                ['name' => 'Cizzara', 'industry' => 'Fashion & E-Commerce', 'logo' => '/logo/cizzara.png'],
                ['name' => 'Shiv Agro Chemical Industries', 'industry' => 'Chemicals & Agriculture', 'logo' => '/logo/shiv%20agro.webp'],
                ['name' => 'Pizza Bell', 'industry' => 'Food & Restaurants', 'logo' => '/logo/pizzabell.png'],
                ['name' => 'Anibrain', 'industry' => '3D Animation & VFX', 'logo' => '/logo/anibrain.png'],
                ['name' => 'VFXWAALA', 'industry' => 'Visual Effects & Post Production', 'logo' => '/logo/vfxwaala.png'],
                ['name' => 'Weta Digital', 'industry' => 'Motion Picture & VFX', 'logo' => '/logo/weta.png'],
                ['name' => 'Vistaprint', 'industry' => 'Graphic Design & Printing', 'logo' => '/logo/vistaprint.png'],
                ['name' => 'National Foods', 'industry' => 'FMCG & Food Processing', 'logo' => '/logo/nationalfoods.png'],
                ['name' => 'Method Studios', 'industry' => 'Animation & Media', 'logo' => '/logo/method.png'],
                ['name' => '3D Studio', 'industry' => 'Game Development Services', 'logo' => '/logo/3D%20studio.png'],
                ['name' => 'Damyaa', 'industry' => 'SMEs & Startups', 'logo' => '/logo/Damyaa.png'],
                ['name' => 'APS-Associates', 'industry' => 'Financial & Accounting', 'logo' => '/logo/aps-associates.png'],
                ['name' => 'Asha Tours & Travels', 'industry' => 'Travel & Tourism', 'logo' => '/logo/Asha_tours&travels.jpeg'],
                ['name' => 'Ayansh Security', 'industry' => 'Security & Protection', 'logo' => '/logo/ayanshse%20sicyuraty.webp'],
                ['name' => 'NHSRCL Logo', 'industry' => 'Project Outsourcing', 'logo' => '/logo/nhsrcl.png'],
                ['name' => 'Bizpack', 'industry' => 'Business Consulting & Execution', 'logo' => '/logo/Logo-Bizpack-1024x451.png'],
                ['name' => 'Associated Power Solution Pvt. Ltd', 'industry' => 'IT & Software Development', 'logo' => '/logo/associatedpower.png'],
                ['name' => '3insys', 'industry' => 'IT & Software Development', 'logo' => '/logo/3insys.png'],
                ['name' => 'Indian Western Railway', 'industry' => 'Public Transport & Govt', 'logo' => '/logo/railway.png'],
                ['name' => 'Global Discovery School', 'industry' => 'Education & Academics', 'logo' => '/logo/globaldiscovery.png'],
                ['name' => 'Flammer Technologies', 'industry' => 'IT & Software Development', 'logo' => '/logo/Flammer-logo-horizontal.png'],
                ['name' => 'HS Structure', 'industry' => 'SMEs & Startups', 'logo' => '/logo/HS%20Structure.png'],
                ['name' => 'SIAMP', 'industry' => 'Project Outsourcing', 'logo' => '/logo/SIAMP.png'],
                ['name' => 'Anacle', 'industry' => 'IT & Software Development', 'logo' => '/logo/anacle.webp'],
                ['name' => 'ATR', 'industry' => 'IT & Software Development', 'logo' => '/logo/atr-logo.png'],
                ['name' => 'CSD Instruments', 'industry' => 'IT & Software Development', 'logo' => '/logo/csd.png'],
                ['name' => 'Destinee Visa', 'industry' => 'Business consulting & execution', 'logo' => '/logo/destinee%20visa.jpeg'],
                ['name' => 'Drapple Healthcare', 'industry' => 'HR & Recruitment Firms', 'logo' => '/logo/drapple%20healthcare.png'],
                ['name' => 'Fabindia', 'industry' => 'SMEs & Startups', 'logo' => '/logo/fabindia.jpeg'],
                ['name' => 'Farsan', 'industry' => 'SMEs & Startups', 'logo' => '/logo/farsan.jpeg'],
                ['name' => 'Green Clean Solar', 'industry' => 'SMEs & Startups', 'logo' => '/logo/green%20clean%20solar.jpeg'],
                ['name' => 'Gujarat Living', 'industry' => 'SMEs & Startups', 'logo' => '/logo/gujrarat%20liaving.jpg'],
                ['name' => 'Hamdan Sports Complex', 'industry' => 'Project Outsourcing', 'logo' => '/logo/hamdan%20sports%20complex.png'],
                ['name' => 'Indo German', 'industry' => 'Project Outsourcing', 'logo' => '/logo/indo%20german.png'],
                ['name' => 'Manavta Hospital', 'industry' => 'SMEs & Startups', 'logo' => '/logo/manavta%20hospital.png'],
                ['name' => 'Qinoxy', 'industry' => 'IT & Software Development', 'logo' => '/logo/qinoxy.jpg'],
                ['name' => 'Rang Techno', 'industry' => 'IT & Software Development', 'logo' => '/logo/rang%20techno.png'],
            ];

            foreach ($companies as $idx => $comp) {
                $slug = Str::slug($comp['name']);
                DB::table('cms_companies')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name'          => $comp['name'],
                        'logo_url'      => $comp['logo'],
                        'is_featured'   => true,
                        'display_order' => $idx + 1,
                        'status'        => 'published',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
