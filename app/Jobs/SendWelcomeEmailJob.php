<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\EmailLog;
use App\Mail\WelcomeEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle(): void
    {
        $log = EmailLog::create([
            'recipient' => $this->user->email,
            'subject' => 'Welcome to Blueboxx DA! 🚀',
            'mailable_class' => WelcomeEmail::class,
            'status' => 'pending',
        ]);

        try {
            Mail::to($this->user->email)->send(new WelcomeEmail($this->user));
            $log->update(['status' => 'sent']);
        } catch (Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
