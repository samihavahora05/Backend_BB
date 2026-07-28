<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemApiCredential;
use App\Models\Notification;
use App\Models\User;

class CheckIntegrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-integrations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily health check of all configured third-party API integrations.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting integration health checks...');
        $credentials = SystemApiCredential::where('status', true)->get();

        $superAdmins = User::whereHas('roles', function($q) {
            $q->where('name', 'super_admin');
        })->get();

        foreach ($credentials as $cred) {
            $this->info("Testing {$cred->provider}...");
            $success = true;
            $errorMsg = '';

            try {
                // The actual ping logic mirrors the SystemApiController
                switch ($cred->provider) {
                    case 'razorpay':
                        $api = new \Razorpay\Api\Api($cred->api_key, $cred->api_secret);
                        $api->order->all(['count' => 1]);
                        break;
                    case 'stripe':
                        $stripe = new \Stripe\StripeClient($cred->api_secret);
                        $stripe->balance->retrieve();
                        break;
                    // Additional ping logics can be added here
                }
            } catch (\Exception $e) {
                $success = false;
                $errorMsg = $e->getMessage();
            }

            if (!$success) {
                $this->error("FAILED: {$cred->provider} - {$errorMsg}");

                // Log Activity
                \App\Models\ActivityLog::create([
                    'user_id' => $superAdmins->first()->id ?? 1,
                    'action' => 'integration_failure',
                    'description' => "Automated health check failed for {$cred->provider}: {$errorMsg}",
                ]);

                // Notify Super Admins
                foreach ($superAdmins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'title' => 'Integration Failure: ' . ucfirst($cred->provider),
                        'message' => "The daily health check for {$cred->provider} failed. Error: {$errorMsg}",
                        'type' => 'alert',
                        'is_read' => false,
                        'metadata' => ['provider' => $cred->provider, 'error' => $errorMsg]
                    ]);
                }
            } else {
                $this->info("OK: {$cred->provider}");
            }
        }

        $this->info('Health checks completed.');
    }
}
