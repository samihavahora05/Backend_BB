<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\SystemApiCredential;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DynamicConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            // Check if tables exist before attempting to load data
            if (Schema::hasTable('system_api_credentials')) {
                $this->loadApiCredentials();
            }
            if (Schema::hasTable('system_settings')) {
                $this->loadSystemSettings();
            }
        } catch (\Exception $e) {
            // Silently fail if DB connection issue so we don't break the whole app
            \Log::error('DynamicConfigurationServiceProvider failed to load: ' . $e->getMessage());
        }
    }

    private function loadApiCredentials()
    {
        $credentials = Cache::rememberForever('dynamic_api_credentials', function () {
            return SystemApiCredential::where('status', true)->get();
        });

        foreach ($credentials as $cred) {
            switch ($cred->provider) {
                case 'razorpay':
                    config(['services.razorpay.key' => $cred->api_key]);
                    config(['services.razorpay.secret' => $cred->api_secret]);
                    break;
                case 'stripe':
                    config(['services.stripe.key' => $cred->api_key]);
                    config(['services.stripe.secret' => $cred->api_secret]);
                    break;
                case 'zoom':
                    config(['services.zoom.client_id' => $cred->api_key]);
                    config(['services.zoom.client_secret' => $cred->api_secret]);
                    // Assuming metadata holds account_id
                    if (isset($cred->metadata['account_id'])) {
                        config(['services.zoom.account_id' => $cred->metadata['account_id']]);
                    }
                    break;
                case 'twilio':
                    config(['services.twilio.sid' => $cred->api_key]);
                    config(['services.twilio.token' => $cred->api_secret]);
                    if (isset($cred->metadata['from_number'])) {
                        config(['services.twilio.from' => $cred->metadata['from_number']]);
                    }
                    break;
                case 'openai':
                    config(['services.openai.api_key' => $cred->api_key]);
                    break;
                case 'aws_s3':
                    config(['filesystems.disks.s3.key' => $cred->api_key]);
                    config(['filesystems.disks.s3.secret' => $cred->api_secret]);
                    if (isset($cred->metadata['region'])) config(['filesystems.disks.s3.region' => $cred->metadata['region']]);
                    if (isset($cred->metadata['bucket'])) config(['filesystems.disks.s3.bucket' => $cred->metadata['bucket']]);
                    break;
                case 'google_oauth':
                    config(['services.google.client_id' => $cred->api_key]);
                    config(['services.google.client_secret' => $cred->api_secret]);
                    break;
                case 'cloudinary':
                    config(['cloudinary.api_key' => $cred->api_key]);
                    config(['cloudinary.api_secret' => $cred->api_secret]);
                    if (isset($cred->metadata['cloud_name'])) {
                        config(['cloudinary.cloud_url' => 'cloudinary://'.$cred->api_key.':'.$cred->api_secret.'@'.$cred->metadata['cloud_name']]);
                    }
                    break;
                case 'firebase':
                    if (isset($cred->metadata['project_id'])) config(['services.firebase.project_id' => $cred->metadata['project_id']]);
                    if (isset($cred->metadata['credentials_file'])) config(['services.firebase.credentials_file' => $cred->metadata['credentials_file']]);
                    break;
            }
        }
    }

    private function loadSystemSettings()
    {
        $settings = Cache::rememberForever('dynamic_system_settings', function () {
            return SystemSetting::all();
        });

        // Group settings by their intended group
        $smtpSettings = $settings->where('group', 'smtp');
        if ($smtpSettings->isNotEmpty()) {
            $mapped = $smtpSettings->pluck('value', 'key');

            // Never let a DB-stored SMTP configuration silently override the
            // developer's own .env in local development — this previously
            // caused registration to fail with a Gmail auth error even
            // though MAIL_MAILER=log was set, because a stale/invalid
            // credential saved via the admin Settings screen took priority
            // on every request. Local always respects .env's MAIL_MAILER.
            $hasCompleteCredentials = $mapped->get('host') && $mapped->get('username') && $mapped->get('password');

            if (!app()->environment('local') && $mapped->has('mailer') && $mapped['mailer'] === 'SMTP' && $hasCompleteCredentials) {
                config(['mail.default' => 'smtp']);
                config(['mail.mailers.smtp.host' => $mapped->get('host')]);
                config(['mail.mailers.smtp.port' => $mapped->get('port')]);
                config(['mail.mailers.smtp.encryption' => $mapped->get('encryption')]);
                config(['mail.mailers.smtp.username' => $mapped->get('username')]);
                config(['mail.mailers.smtp.password' => $mapped->get('password')]);

                config(['mail.from.address' => $mapped->get('from_address')]);
                config(['mail.from.name' => $mapped->get('from_name')]);
            }
        }
    }
}
