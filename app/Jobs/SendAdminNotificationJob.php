<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendAdminNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $subject;
    public $messageBody;
    public $data;

    public function __construct($subject, $messageBody, $data = [])
    {
        $this->subject = $subject;
        $this->messageBody = $messageBody;
        $this->data = $data;
    }

    public function handle(): void
    {
        try {
            Mail::raw($this->messageBody . "\n\nData: " . json_encode($this->data, JSON_PRETTY_PRINT), function ($message) {
                $message->to('info.blueboxx@gmail.com')
                        ->subject($this->subject);
            });
        } catch (\Exception $e) {
            Log::error('SendAdminNotificationJob Error: ' . $e->getMessage());
        }
    }
}
