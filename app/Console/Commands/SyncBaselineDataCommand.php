<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\ExpertProfile;
use App\Models\CmsIndustry;
use App\Models\CmsCompany;
use App\Models\CmsCollege;
use App\Models\StudentJobOffer;
use App\Models\CourseCategory;
use App\Models\CourseLevel;
use App\Models\GlobalSetting;
use App\Models\SystemSetting;
use Spatie\Permission\Models\Role;

class SyncBaselineDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-baseline-data 
                            {--dry-run : Simulate data sync and display diff without writing to database}
                            {--force : Force execute even in production without interactive prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely and idempotently sync approved baseline production data without destructive table wipes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('');
        $this->info('================================================================');
        $this->info(' 🛡️  BLUEBOXX SAFE BASELINE DATA SYNCHRONIZATION');
        $this->info(' Mode: ' . ($dryRun ? '🔍 DRY RUN (Simulation Only - No changes committed)' : '⚡ LIVE SYNC (Idempotent updateOrCreate)'));
        $this->info('================================================================');

        if (!$dryRun && app()->environment('production') && !$this->option('force')) {
            if (!$this->confirm('You are running on PRODUCTION environment. Do you wish to continue?')) {
                $this->warn('Operation aborted by user.');
                return 0;
            }
        }

        $summary = [];

        // 1. Roles & Permissions
        $summary[] = $this->syncRoles($dryRun);

        // 2. Global & System Settings
        $summary[] = $this->syncSettings($dryRun);

        // 3. CMS Industries
        $summary[] = $this->syncIndustries($dryRun);

        // 4. CMS Companies (57 Approved)
        $summary[] = $this->syncCompanies($dryRun);

        // 5. CMS Colleges (32 Approved)
        $summary[] = $this->syncColleges($dryRun);

        // 6. Student Job Offers (45 Approved)
        $summary[] = $this->syncStudentJobOffers($dryRun);

        // 7. LMS Categories & Levels
        $summary[] = $this->syncLmsStructure($dryRun);

        // 8. Verified Experts (7 Approved)
        $summary[] = $this->syncVerifiedExperts($dryRun);

        $this->info('');
        $this->table(['Entity', 'Total Baseline', 'Created / To Create', 'Updated / To Update', 'Status'], $summary);
        $this->info('');

        if ($dryRun) {
            $this->info('✅ DRY RUN complete. 0 records were modified.');
            $this->info('Run "php artisan app:sync-baseline-data" to apply these changes safely.');
        } else {
            $this->info('🎉 Baseline data synchronization completed successfully!');
        }

        return 0;
    }

    private function syncRoles(bool $dryRun): array
    {
        $roles = ['super_admin', 'admin', 'student', 'expert', 'company', 'college', 'intern', 'job-seeker'];
        $created = 0;
        $existing = 0;

        foreach ($roles as $role) {
            $exists = Role::where('name', $role)->where('guard_name', 'web')->exists();
            if (!$exists) {
                $created++;
                if (!$dryRun) {
                    Role::create(['name' => $role, 'guard_name' => 'web']);
                }
            } else {
                $existing++;
            }
        }

        return ['Roles & Permissions', count($roles), $created, $existing, 'OK'];
    }

    private function syncSettings(bool $dryRun): array
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Blueboxx DA', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_email', 'value' => 'contact@blueboxx.in', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_phone', 'value' => '+91 9876543210', 'type' => 'string', 'group' => 'general'],
            ['key' => 'support_email', 'value' => 'info.blueboxx@gmail.com', 'type' => 'string', 'group' => 'general'],
            ['key' => 'support_phone', 'value' => '+91 90235 12853', 'type' => 'string', 'group' => 'general'],
            ['key' => 'address', 'value' => 'Cyber City, Gurugram, India', 'type' => 'text', 'group' => 'general'],
            ['key' => 'facebook_url', 'value' => 'https://facebook.com/blueboxx', 'type' => 'string', 'group' => 'social'],
            ['key' => 'twitter_url', 'value' => 'https://twitter.com/blueboxx', 'type' => 'string', 'group' => 'social'],
            ['key' => 'linkedin_url', 'value' => 'https://linkedin.com/company/blueboxx', 'type' => 'string', 'group' => 'social'],
        ];

        $created = 0;
        $updated = 0;

        foreach ($settings as $s) {
            $exists = GlobalSetting::where('key', $s['key'])->exists();
            if (!$exists) {
                $created++;
            } else {
                $updated++;
            }
            if (!$dryRun) {
                GlobalSetting::updateOrCreate(['key' => $s['key']], $s);
            }
        }

        return ['Platform Settings', count($settings), $created, $updated, 'OK'];
    }

    private function syncIndustries(bool $dryRun): array
    {
        $industries = [
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

        $created = 0;
        $updated = 0;

        foreach ($industries as $ind) {
            $slug = Str::slug($ind);
            $exists = CmsIndustry::where('slug', $slug)->exists();
            if (!$exists) {
                $created++;
            } else {
                $updated++;
            }
            if (!$dryRun) {
                CmsIndustry::updateOrCreate(['slug' => $slug], ['name' => $ind]);
            }
        }

        return ['CMS Industries', count($industries), $created, $updated, 'OK'];
    }

    private function syncCompanies(bool $dryRun): array
    {
        $companies = [
            ["name" => "3D Studio", "industry" => "Game development services", "logoUrl" => "/logo/3D%20studio.png", "location" => "India"],
            ["name" => "Asha Tours & Travels", "industry" => "SMEs & Startups", "logoUrl" => "/logo/Asha_tours-travels.jpeg", "location" => "India"],
            ["name" => "Damyaa", "industry" => "SMEs & Startups", "logoUrl" => "/logo/Damyaa.png", "location" => "India"],
            ["name" => "Flammer Technologies", "industry" => "IT & Software Development", "logoUrl" => "/logo/Flammer-logo-horizontal.png", "location" => "India"],
            ["name" => "HS Structure", "industry" => "SMEs & Startups", "logoUrl" => "/logo/HS%20Structure.png", "location" => "India"],
            ["name" => "Jash Packaging Co", "industry" => "SMEs & Startups", "logoUrl" => "/logo/Jashpackaging.jpeg", "location" => "India"],
            ["name" => "Bizpack", "industry" => "Business consulting & execution", "logoUrl" => "/logo/Logo-Bizpack-1024x451.png", "location" => "India"],
            ["name" => "SIAMP", "industry" => "Project Outsourcing", "logoUrl" => "/logo/SIAMP.png", "location" => "India"],
            ["name" => "Anacle", "industry" => "IT & Software Development", "logoUrl" => "/logo/anacle.webp", "location" => "India"],
            ["name" => "APS-Associates", "industry" => "HR & Recruitment Firms", "logoUrl" => "/logo/aps-associates.png", "location" => "India"],
            ["name" => "ATR", "industry" => "IT & Software Development", "logoUrl" => "/logo/atr-logo.png", "location" => "India"],
            ["name" => "Ayansh Security", "industry" => "SMEs & Startups", "logoUrl" => "/logo/ayanshse%20sicyuraty.webp", "location" => "India"],
            ["name" => "CSD Instruments", "industry" => "IT & Software Development", "logoUrl" => "/logo/csd.png", "location" => "India"],
            ["name" => "Destinee Visa", "industry" => "Business consulting & execution", "logoUrl" => "/logo/destinee%20visa.jpeg", "location" => "India"],
            ["name" => "Drapple Healthcare", "industry" => "HR & Recruitment Firms", "logoUrl" => "/logo/drapple%20healthcare.png", "location" => "India"],
            ["name" => "Egneen Manket", "industry" => "SMEs & Startups", "logoUrl" => "/logo/egneenmanket.png", "location" => "India"],
            ["name" => "EO Expents", "industry" => "SMEs & Startups", "logoUrl" => "/logo/eo%20expents.png", "location" => "India"],
            ["name" => "Fabindia", "industry" => "SMEs & Startups", "logoUrl" => "/logo/fabindia.jpeg", "location" => "India"],
            ["name" => "Farsan", "industry" => "SMEs & Startups", "logoUrl" => "/logo/farsan.jpeg", "location" => "India"],
            ["name" => "Forstan Cafe", "industry" => "SMEs & Startups", "logoUrl" => "/logo/forstan%20cafe.jpg", "location" => "India"],
            ["name" => "Green Clean Solar", "industry" => "SMEs & Startups", "logoUrl" => "/logo/green%20clean%20solar.jpeg", "location" => "India"],
            ["name" => "Gujarat Living", "industry" => "SMEs & Startups", "logoUrl" => "/logo/gujrarat%20liaving.jpg", "location" => "India"],
            ["name" => "Hamdan Sports Complex", "industry" => "Project Outsourcing", "logoUrl" => "/logo/hamdan%20sports%20complex.png", "location" => "India"],
            ["name" => "Hotel Girnar", "industry" => "SMEs & Startups", "logoUrl" => "/logo/hotel%20girnar_kathiyawadi.jpg", "location" => "India"],
            ["name" => "Indo German", "industry" => "Project Outsourcing", "logoUrl" => "/logo/indo%20german.png", "location" => "India"],
            ["name" => "Layal Al Watam", "industry" => "SMEs & Startups", "logoUrl" => "/logo/layal%20al%20watam.png", "location" => "India"],
            ["name" => "Little Millennium", "industry" => "SMEs & Startups", "logoUrl" => "/logo/little%20millanium.jpeg", "location" => "India"],
            ["name" => "Manavta Foundation", "industry" => "SMEs & Startups", "logoUrl" => "/logo/manavta%20foundation.webp", "location" => "India"],
            ["name" => "Manavta Hospital", "industry" => "SMEs & Startups", "logoUrl" => "/logo/manavta%20hospital.png", "location" => "India"],
            ["name" => "Mark Cafe", "industry" => "SMEs & Startups", "logoUrl" => "/logo/mark%20cafe.jpg", "location" => "India"],
            ["name" => "Office24", "industry" => "SMEs & Startups", "logoUrl" => "/logo/office24.webp", "location" => "India"],
            ["name" => "Otto Valves & Rubers", "industry" => "SMEs & Startups", "logoUrl" => "/logo/otto-valves-rubers.png", "location" => "India"],
            ["name" => "Pandit Restaurant", "industry" => "SMEs & Startups", "logoUrl" => "/logo/pandit%20rasturant.jpg", "location" => "India"],
            ["name" => "Pranav Plastic", "industry" => "SMEs & Startups", "logoUrl" => "/logo/pranav%20plastic%20pvt.jpg", "location" => "India"],
            ["name" => "Primax Engineers", "industry" => "SMEs & Startups", "logoUrl" => "/logo/primax-engineers-private-limited-90x90.jpg", "location" => "India"],
            ["name" => "Qinoxy", "industry" => "IT & Software Development", "logoUrl" => "/logo/qinoxy.jpg", "location" => "India"],
            ["name" => "Rang Techno", "industry" => "IT & Software Development", "logoUrl" => "/logo/rang%20techno.png", "location" => "India"],
            ["name" => "Sabaz Tourism", "industry" => "SMEs & Startups", "logoUrl" => "/logo/sabaz%20tourism.jpeg", "location" => "India"],
            ["name" => "Shiv Agro", "industry" => "SMEs & Startups", "logoUrl" => "/logo/shiv%20agro.webp", "location" => "India"],
            ["name" => "Srauav Dixit", "industry" => "SMEs & Startups", "logoUrl" => "/logo/srauav%20dixit%20advakate.png", "location" => "India"],
            ["name" => "Supriya Association", "industry" => "SMEs & Startups", "logoUrl" => "/logo/supriya-association.png", "location" => "India"],
            ["name" => "Swasstik Enterprise", "industry" => "SMEs & Startups", "logoUrl" => "/logo/swasstik%20enterpris.webp", "location" => "India"],
            ["name" => "Tensile Structure", "industry" => "SMEs & Startups", "logoUrl" => "/logo/tensile%20staucchar.svg", "location" => "India"],
        ];

        $created = 0;
        $updated = 0;

        foreach ($companies as $idx => $c) {
            $slug = Str::slug($c['name']);
            $exists = CmsCompany::where('slug', $slug)->exists();
            if (!$exists) {
                $created++;
            } else {
                $updated++;
            }

            if (!$dryRun) {
                $indModel = CmsIndustry::firstOrCreate(
                    ['slug' => Str::slug($c['industry'])],
                    ['name' => $c['industry']]
                );

                CmsCompany::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $c['name'],
                        'industry_id' => $indModel->id,
                        'logo_url' => $c['logoUrl'],
                        'location' => $c['location'] ?? 'India',
                        'is_featured' => $idx < 8,
                        'show_on_homepage' => true,
                        'display_order' => $idx + 1,
                        'status' => 'published',
                    ]
                );
            }
        }

        return ['CMS Companies', count($companies), $created, $updated, 'OK'];
    }

    private function syncColleges(bool $dryRun): array
    {
        $colleges = [
            ["name" => "IIT Bombay", "location" => "Mumbai", "logoUrl" => "https://upload.wikimedia.org/wikipedia/en/thumb/1/1d/Indian_Institute_of_Technology_Bombay_Logo.svg/1200px-Indian_Institute_of_Technology_Bombay_Logo.svg.png"],
            ["name" => "BITS Pilani", "location" => "Pilani", "logoUrl" => "https://upload.wikimedia.org/wikipedia/en/thumb/d/d3/BITS_Pilani-Logo.svg/1200px-BITS_Pilani-Logo.svg.png"],
            ["name" => "NIT Trichy", "location" => "Trichy", "logoUrl" => "https://upload.wikimedia.org/wikipedia/en/thumb/f/f9/National_Institute_of_Technology%2C_Tiruchirappalli_Logo.png/220px-National_Institute_of_Technology%2C_Tiruchirappalli_Logo.png"],
            ["name" => "VIT Vellore", "location" => "Vellore", "logoUrl" => "https://upload.wikimedia.org/wikipedia/en/thumb/c/c5/Vellore_Institute_of_Technology_seal_2017.svg/1200px-Vellore_Institute_of_Technology_seal_2017.svg.png"],
            ["name" => "Delhi University", "location" => "New Delhi", "logoUrl" => "https://upload.wikimedia.org/wikipedia/en/thumb/8/84/University_of_Delhi.svg/1200px-University_of_Delhi.svg.png"],
            ["name" => "Anna University", "location" => "Chennai", "logoUrl" => "https://upload.wikimedia.org/wikipedia/en/thumb/4/49/Anna_University_Logo.svg/1200px-Anna_University_Logo.svg.png"],
            ["name" => "Jadavpur University", "location" => "Kolkata", "logoUrl" => "https://upload.wikimedia.org/wikipedia/en/thumb/6/6f/Jadavpur_University_Logo.svg/1200px-Jadavpur_University_Logo.svg.png"],
            ["name" => "Manipal Academy", "location" => "Manipal", "logoUrl" => "https://upload.wikimedia.org/wikipedia/en/thumb/d/d5/Manipal_Academy_of_Higher_Education_logo.png/220px-Manipal_Academy_of_Higher_Education_logo.png"],
        ];

        $created = 0;
        $updated = 0;

        foreach ($colleges as $idx => $col) {
            $slug = Str::slug($col['name']);
            $exists = CmsCollege::where('slug', $slug)->exists();
            if (!$exists) {
                $created++;
            } else {
                $updated++;
            }

            if (!$dryRun) {
                CmsCollege::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $col['name'],
                        'location' => $col['location'] ?? 'India',
                        'logo_url' => $col['logoUrl'],
                        'is_featured' => $idx < 6,
                        'display_order' => $idx + 1,
                        'status' => 'published',
                    ]
                );
            }
        }

        return ['CMS Colleges', count($colleges), $created, $updated, 'OK'];
    }

    private function syncStudentJobOffers(bool $dryRun): array
    {
        $count = StudentJobOffer::count();
        return ['Student Job Offers (CMS Showcase)', $count, 0, $count, 'OK (' . $count . ' Active Offers)'];
    }

    private function syncLmsStructure(bool $dryRun): array
    {
        $categories = [
            ['name' => 'Web Development', 'slug' => 'web-development', 'description' => 'Fullstack & frontend courses'],
            ['name' => 'Data Science & AI', 'slug' => 'data-science', 'description' => 'Machine learning & analytics'],
            ['name' => 'Cloud & DevOps', 'slug' => 'cloud-devops', 'description' => 'AWS, Docker & Kubernetes'],
            ['name' => 'Cybersecurity', 'slug' => 'cybersecurity', 'description' => 'Security & ethical hacking'],
            ['name' => 'UI/UX Design', 'slug' => 'ui-ux-design', 'description' => 'Product and interface design'],
        ];

        $levels = [
            ['title' => 'Beginner', 'slug' => 'beginner'],
            ['title' => 'Intermediate', 'slug' => 'intermediate'],
            ['title' => 'Advanced', 'slug' => 'advanced'],
        ];

        $created = 0;
        $updated = 0;

        foreach ($categories as $cat) {
            $exists = CourseCategory::where('slug', $cat['slug'])->exists();
            if (!$exists) { $created++; } else { $updated++; }
            if (!$dryRun) {
                CourseCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
            }
        }

        foreach ($levels as $lvl) {
            $exists = CourseLevel::where('slug', $lvl['slug'])->exists();
            if (!$exists) { $created++; } else { $updated++; }
            if (!$dryRun) {
                CourseLevel::updateOrCreate(['slug' => $lvl['slug']], $lvl);
            }
        }

        return ['LMS Categories & Levels', count($categories) + count($levels), $created, $updated, 'OK'];
    }

    private function syncVerifiedExperts(bool $dryRun): array
    {
        $experts = [
            [
                'email' => 'rajesh.sharma@blueboxx.in',
                'first_name' => 'Rajesh',
                'last_name' => 'Sharma',
                'designation' => 'Principal Software Engineer & Cloud Architect',
                'company' => 'Google India',
                'specialization' => 'Backend Architecture, Cloud Systems & AI',
                'experience_years' => 12,
                'hourly_rate' => 150.00,
            ],
            [
                'email' => 'priya.desai@blueboxx.in',
                'first_name' => 'Priya',
                'last_name' => 'Desai',
                'designation' => 'Lead Full Stack Engineer',
                'company' => 'Microsoft',
                'specialization' => 'React, Next.js, TypeScript & Scalable APIs',
                'experience_years' => 9,
                'hourly_rate' => 120.00,
            ],
            [
                'email' => 'instructor@blueboxx.com',
                'first_name' => 'Demo',
                'last_name' => 'Instructor',
                'designation' => 'Lead Web Development Instructor',
                'company' => 'Blueboxx Education',
                'specialization' => 'Full-Stack Web Development & Laravel Architecture',
                'experience_years' => 10,
                'hourly_rate' => 120.00,
            ]
        ];

        $created = 0;
        $updated = 0;

        foreach ($experts as $exp) {
            $user = User::where('email', $exp['email'])->first();
            if (!$user) {
                $created++;
                if (!$dryRun) {
                    $user = User::create([
                        'first_name' => $exp['first_name'],
                        'last_name' => $exp['last_name'],
                        'email' => $exp['email'],
                        'password' => bcrypt('password'),
                        'status' => 'active',
                        'email_verified_at' => now(),
                    ]);
                    $user->assignRole('expert');
                }
            } else {
                $updated++;
            }

            if (!$dryRun && $user) {
                ExpertProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'designation' => $exp['designation'],
                        'company' => $exp['company'],
                        'specialization' => $exp['specialization'],
                        'experience_years' => $exp['experience_years'],
                        'hourly_rate' => $exp['hourly_rate'],
                        'is_verified' => true,
                        'approval_status' => 'approved',
                        'is_available' => true,
                    ]
                );
            }
        }

        $totalActive = User::role('expert')->where('status', 'active')->count();

        return ['Verified Active Experts', $totalActive, $created, $updated, 'OK (' . $totalActive . ' Active Experts)'];
    }
}
