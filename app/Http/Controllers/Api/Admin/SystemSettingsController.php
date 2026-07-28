<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SystemSettingsController extends Controller
{
    public function getSettings($group)
    {
        $settings = SystemSetting::where('group', $group)->pluck('value', 'key');
        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function updateSettings(Request $request, $group)
    {
        $settings = $request->input('settings', []);
        
        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $value]
            );
        }

        \Illuminate\Support\Facades\Cache::forget('dynamic_system_settings');

        \App\Models\ActivityLog::create([
            'user_id' => $request->user() ? $request->user()->id : 1,
            'action' => 'update_settings',
            'description' => "Updated system settings for group: {$group}",
        ]);

        return response()->json(['success' => true, 'message' => 'Settings updated successfully']);
    }

    public function testSmtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            // Setup dynamic SMTP config
            $smtpSettings = SystemSetting::where('group', 'smtp')->pluck('value', 'key');
            
            if ($smtpSettings->count() > 0) {
                config([
                    'mail.mailers.smtp.host' => $smtpSettings->get('host', env('MAIL_HOST')),
                    'mail.mailers.smtp.port' => $smtpSettings->get('port', env('MAIL_PORT')),
                    'mail.mailers.smtp.encryption' => $smtpSettings->get('encryption', env('MAIL_ENCRYPTION')),
                    'mail.mailers.smtp.username' => $smtpSettings->get('username', env('MAIL_USERNAME')),
                    'mail.mailers.smtp.password' => $smtpSettings->get('password', env('MAIL_PASSWORD')),
                    'mail.from.address' => $smtpSettings->get('from_address', env('MAIL_FROM_ADDRESS')),
                    'mail.from.name' => $smtpSettings->get('from_name', env('MAIL_FROM_NAME')),
                ]);
            }

            Mail::raw('This is a test email from Blueboxx DA System Settings.', function ($message) use ($request) {
                $message->to($request->email)->subject('SMTP Test - Blueboxx DA');
            });

            return response()->json(['success' => true, 'message' => 'Test email sent successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }
}
