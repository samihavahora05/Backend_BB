<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\CmsCompany;
use App\Models\CmsIndustry;

class ImportCompaniesSeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
  [ "name" => "3D Studio", "industry" => "Game development services", "logoUrl" => "/logo/3D%20studio.png" ],
  [ "name" => "Asha Tours & Travels", "industry" => "SMEs & Startups", "logoUrl" => "/logo/Asha_tours-travels.jpeg" ],
  [ "name" => "Damyaa", "industry" => "SMEs & Startups", "logoUrl" => "/logo/Damyaa.png" ],
  [ "name" => "Flammer Technologies", "industry" => "IT & Software Development", "logoUrl" => "/logo/Flammer-logo-horizontal.png" ],
  [ "name" => "HS Structure", "industry" => "SMEs & Startups", "logoUrl" => "/logo/HS%20Structure.png" ],
  [ "name" => "Jash Packaging Co", "industry" => "SMEs & Startups", "logoUrl" => "/logo/Jashpackaging.jpeg" ],
  [ "name" => "Bizpack", "industry" => "Business growth services", "logoUrl" => "/logo/Logo-Bizpack-1024x451.png" ],
  [ "name" => "SIAMP", "industry" => "Project Outsourcing", "logoUrl" => "/logo/SIAMP.png" ],
  [ "name" => "Anacle", "industry" => "IT & Software Development", "logoUrl" => "/logo/anacle.webp" ],
  [ "name" => "APS-Associates", "industry" => "HR & Recruitment Firms", "logoUrl" => "/logo/aps-associates.png" ],
  [ "name" => "ATR", "industry" => "IT & Software Development", "logoUrl" => "/logo/atr-logo.png" ],
  [ "name" => "Ayansh Security", "industry" => "SMEs & Startups", "logoUrl" => "/logo/ayanshse%20sicyuraty.webp" ],
  [ "name" => "CSD Instruments", "industry" => "IT & Software Development", "logoUrl" => "/logo/csd.png" ],
  [ "name" => "Destinee Visa", "industry" => "Business consulting & execution", "logoUrl" => "/logo/destinee%20visa.jpeg" ],
  [ "name" => "Drapple Healthcare", "industry" => "HR & Recruitment Firms", "logoUrl" => "/logo/drapple%20healthcare.png" ],
  [ "name" => "Egneen Manket", "industry" => "SMEs & Startups", "logoUrl" => "/logo/egneenmanket.png" ],
  [ "name" => "EO Expents", "industry" => "SMEs & Startups", "logoUrl" => "/logo/eo%20expents.png" ],
  [ "name" => "Fabindia", "industry" => "SMEs & Startups", "logoUrl" => "/logo/fabindia.jpeg" ],
  [ "name" => "Farsan", "industry" => "SMEs & Startups", "logoUrl" => "/logo/farsan.jpeg" ],
  [ "name" => "Forstan Cafe", "industry" => "SMEs & Startups", "logoUrl" => "/logo/forstan%20cafe.jpg" ],
  [ "name" => "Green Clean Solar", "industry" => "SMEs & Startups", "logoUrl" => "/logo/green%20clean%20solar.jpeg" ],
  [ "name" => "Gujarat Living", "industry" => "SMEs & Startups", "logoUrl" => "/logo/gujrarat%20liaving.jpg" ],
  [ "name" => "Hamdan Sports Complex", "industry" => "Project Outsourcing", "logoUrl" => "/logo/hamdan%20sports%20complex.png" ],
  [ "name" => "Hotel Girnar", "industry" => "SMEs & Startups", "logoUrl" => "/logo/hotel%20girnar_kathiyawadi.jpg" ],
  [ "name" => "Indo German", "industry" => "Project Outsourcing", "logoUrl" => "/logo/indo%20german.png" ],
  [ "name" => "Layal Al Watam", "industry" => "SMEs & Startups", "logoUrl" => "/logo/layal%20al%20watam.png" ],
  [ "name" => "Little Millennium", "industry" => "SMEs & Startups", "logoUrl" => "/logo/little%20millanium.jpeg" ],
  [ "name" => "Manavta Foundation", "industry" => "SMEs & Startups", "logoUrl" => "/logo/manavta%20foundation.webp" ],
  [ "name" => "Manavta Hospital", "industry" => "SMEs & Startups", "logoUrl" => "/logo/manavta%20hospital.png" ],
  [ "name" => "Mark Cafe", "industry" => "SMEs & Startups", "logoUrl" => "/logo/mark%20cafe.jpg" ],
  [ "name" => "Office24", "industry" => "SMEs & Startups", "logoUrl" => "/logo/office24.webp" ],
  [ "name" => "Otto Valves & Rubers", "industry" => "SMEs & Startups", "logoUrl" => "/logo/otto-valves-rubers.png" ],
  [ "name" => "Pandit Restaurant", "industry" => "SMEs & Startups", "logoUrl" => "/logo/pandit%20rasturant.jpg" ],
  [ "name" => "Pranav Plastic", "industry" => "SMEs & Startups", "logoUrl" => "/logo/pranav%20plastic%20pvt.jpg" ],
  [ "name" => "Primax Engineers", "industry" => "SMEs & Startups", "logoUrl" => "/logo/primax-engineers-private-limited-90x90.jpg" ],
  [ "name" => "Qinoxy", "industry" => "IT & Software Development", "logoUrl" => "/logo/qinoxy.jpg" ],
  [ "name" => "Rang Techno", "industry" => "IT & Software Development", "logoUrl" => "/logo/rang%20techno.png" ],
  [ "name" => "Sabaz Tourism", "industry" => "SMEs & Startups", "logoUrl" => "/logo/sabaz%20tourism.jpeg" ],
  [ "name" => "Shiv Agro", "industry" => "SMEs & Startups", "logoUrl" => "/logo/shiv%20agro.webp" ],
  [ "name" => "Srauav Dixit", "industry" => "SMEs & Startups", "logoUrl" => "/logo/srauav%20dixit%20advakate.png" ],
  [ "name" => "Supriya Association", "industry" => "SMEs & Startups", "logoUrl" => "/logo/supriya-association.png" ],
  [ "name" => "Swasstik Enterprise", "industry" => "SMEs & Startups", "logoUrl" => "/logo/swasstik%20enterpris.webp" ],
  [ "name" => "Tensile Structure", "industry" => "SMEs & Startups", "logoUrl" => "/logo/tensile%20staucchar.svg" ]
];

        foreach ($companies as $c) {
            $industry = CmsIndustry::firstOrCreate([
                'slug' => Str::slug($c['industry'])
            ], [
                'name' => $c['industry']
            ]);

            CmsCompany::updateOrCreate(
                ['slug' => Str::slug($c['name'])],
                [
                    'name' => $c['name'],
                    'logo_url' => $c['logoUrl'],
                    'industry_id' => $industry->id,
                    'is_featured' => true,
                    'show_on_homepage' => true,
                    'status' => 'published',
                ]
            );
        }
    }
}
