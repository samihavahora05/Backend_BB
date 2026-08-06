<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GlobalSetting;
use App\Models\SystemSetting;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Global Settings
        $settings = [
            ['key' => 'site_name', 'value' => 'Blueboxx DA', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_email', 'value' => 'contact@blueboxx.in', 'type' => 'string', 'group' => 'general'],
            ['key' => 'contact_phone', 'value' => '+91 9876543210', 'type' => 'string', 'group' => 'general'],
            ['key' => 'address', 'value' => 'Cyber City, Gurugram, India', 'type' => 'text', 'group' => 'general'],
            ['key' => 'facebook_url', 'value' => 'https://facebook.com/blueboxx', 'type' => 'string', 'group' => 'social'],
            ['key' => 'twitter_url', 'value' => 'https://twitter.com/blueboxx', 'type' => 'string', 'group' => 'social'],
            ['key' => 'linkedin_url', 'value' => 'https://linkedin.com/company/blueboxx', 'type' => 'string', 'group' => 'social'],
        ];

        foreach ($settings as $setting) {
            GlobalSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // System Settings
        $systemSettings = [
            ['key' => 'maintenance_mode', 'value' => 'false', 'group' => 'system'],
            ['key' => 'allow_registrations', 'value' => 'true', 'group' => 'system'],
            ['key' => 'default_currency', 'value' => 'INR', 'group' => 'system'],
        ];

        foreach ($systemSettings as $setting) {
            SystemSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
