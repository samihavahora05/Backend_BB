<?php

namespace App\Services\Sms;

interface SmsGatewayInterface
{
    /**
     * Send SMS to a specific phone number.
     *
     * @param string $phone The recipient's phone number.
     * @param string $message The message body.
     * @return bool True if successful, false otherwise.
     */
    public function send(string $phone, string $message): bool;
}
