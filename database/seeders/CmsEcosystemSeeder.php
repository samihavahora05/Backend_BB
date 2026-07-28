<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\CmsIndustry;
use App\Models\CmsCompany;
use App\Models\CmsPlacementPartner;
use App\Models\CmsCollege;
use App\Models\CmsPortfolio;

class CmsEcosystemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Industries
        $industriesData = [
            "IT & Software Development",
            "AI Automation & Smart Business Systems",
            "CRM & ERP Solutions",
            "Digital & Performance Marketing",
            "Project Outsourcing",
            "Virtual & Trained Workforce",
            "SMEs & Startups",
            "Marketing & IT Agencies",
            "HR & Recruitment Firms",
            "Web & mobile app development",
            "Game development services",
            "Business consulting & execution"
        ];

        $industryMap = [];
        foreach ($industriesData as $ind) {
            $model = CmsIndustry::firstOrCreate([
                'name' => $ind,
                'slug' => Str::slug($ind)
            ]);
            $industryMap[$ind] = $model->id;
        }

        // 2. Placement Partners
        $partners = [
            ['name' => 'Vistaprint', 'logo_url' => 'https://logo.clearbit.com/vistaprint.com', 'industry' => 'Marketing & IT Agencies'],
            ['name' => 'Framestore', 'logo_url' => 'https://logo.clearbit.com/framestore.com', 'industry' => '3D ANIMATION'],
            ['name' => 'Anibrain', 'logo_url' => 'https://logo.clearbit.com/anibrain.com', 'industry' => 'Game development services'],
            ['name' => 'Weta Digital', 'logo_url' => 'https://logo.clearbit.com/wetafx.co.nz', 'industry' => '3D ANIMATION']
        ];

        foreach ($partners as $index => $partner) {
            CmsPlacementPartner::firstOrCreate(
                ['slug' => Str::slug($partner['name'])],
                [
                    'name' => $partner['name'],
                    'logo_url' => $partner['logo_url'],
                    'industry_id' => $industryMap[$partner['industry']] ?? null,
                    'is_featured' => true,
                    'display_order' => $index
                ]
            );
        }

        // 3. Companies
        $companies = [
            ['name' => '3D Studio', 'industry' => 'Game development services', 'logo_url' => '/logo/3D%20studio.png'],
            ['name' => 'Asha Tours & Travels', 'industry' => 'SMEs & Startups', 'logo_url' => '/logo/Asha_tours&travels.jpeg'],
            ['name' => 'Damyaa', 'industry' => 'SMEs & Startups', 'logo_url' => '/logo/Damyaa.png'],
            ['name' => 'Flammer Technologies', 'industry' => 'IT & Software Development', 'logo_url' => '/logo/Flammer-logo-horizontal.png'],
            ['name' => 'HS Structure', 'industry' => 'SMEs & Startups', 'logo_url' => '/logo/HS%20Structure.png'],
            ['name' => 'Jash Packaging Co', 'industry' => 'SMEs & Startups', 'logo_url' => '/logo/Jashpackaging.jpeg'],
            ['name' => 'Bizpack', 'industry' => 'Business consulting & execution', 'logo_url' => '/logo/Logo-Bizpack-1024x451.png'],
            ['name' => 'SIAMP', 'industry' => 'Project Outsourcing', 'logo_url' => '/logo/SIAMP.png']
        ];

        foreach ($companies as $index => $company) {
            CmsCompany::firstOrCreate(
                ['slug' => Str::slug($company['name'])],
                [
                    'name' => $company['name'],
                    'logo_url' => $company['logo_url'],
                    'industry_id' => $industryMap[$company['industry']] ?? null,
                    'is_featured' => true,
                    'show_on_homepage' => true,
                    'display_order' => $index
                ]
            );
        }

        // 4. Colleges
        $colleges = [
            ['name' => 'IIT Bombay', 'location' => 'Mumbai', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/thumb/1/1d/Indian_Institute_of_Technology_Bombay_Logo.svg/1200px-Indian_Institute_of_Technology_Bombay_Logo.svg.png'],
            ['name' => 'BITS Pilani', 'location' => 'Pilani', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/thumb/d/d3/BITS_Pilani-Logo.svg/1200px-BITS_Pilani-Logo.svg.png'],
            ['name' => 'NIT Trichy', 'location' => 'Trichy', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/thumb/f/f9/National_Institute_of_Technology%2C_Tiruchirappalli_Logo.png/220px-National_Institute_of_Technology%2C_Tiruchirappalli_Logo.png'],
            ['name' => 'VIT Vellore', 'location' => 'Vellore', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/thumb/c/c5/Vellore_Institute_of_Technology_seal_2017.svg/1200px-Vellore_Institute_of_Technology_seal_2017.svg.png']
        ];

        foreach ($colleges as $index => $college) {
            CmsCollege::firstOrCreate(
                ['slug' => Str::slug($college['name'])],
                [
                    'name' => $college['name'],
                    'location' => $college['location'],
                    'logo_url' => $college['logo_url'],
                    'is_featured' => true,
                    'display_order' => $index
                ]
            );
        }

        // 5. Portfolios
        CmsPortfolio::firstOrCreate(
            ['slug' => Str::slug('Brand Identity & 3D Promo Film')],
            [
                'title' => 'Brand Identity & 3D Promo Film',
                'studio' => 'Anibrain Studios',
                'category' => '3D ANIMATION',
                'description' => 'End-to-end brand film featuring photorealistic 3D product animation and motion graphics for theatrical release.',
                'tags' => ['3D Modeling', 'VFX', 'Motion Graphics'],
                'duration' => '8 WEEKS',
                'deliverables' => 'BRAND FILM + 3 TEASERS',
                'image_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&q=80',
                'link' => '#',
                'is_featured' => true,
                'display_order' => 0
            ]
        );
    }
}
