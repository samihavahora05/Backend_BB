<?php

namespace App\Services\Sms;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class SmsGatewayManager
{
    /**
     * Resolve the active SMS Gateway from System Settings.
     */
    public static function resolve(): SmsGatewayInterface
    {
        $provider = SystemSetting::where('group', 'sms')->where('key', 'default_provider')->value('value');
        $provider = $provider ?? 'msg91'; // Default to MSG91

        return match (strtolower($provider)) {
            'twilio' => new TwilioGateway(),
            'fast2sms' => new Fast2SmsGateway(),
            'textlocal' => new TextLocalGateway(),
            default => new Msg91Gateway(),
        };
    }
}
