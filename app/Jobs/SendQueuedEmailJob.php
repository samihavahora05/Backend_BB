<?php

namespace App\Jobs;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendQueuedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $recipient;
    public $mailable;
    public $mailableClass;
    public $subject;
    protected $logId;

    public function __construct(string $recipient, $mailable, string $subject)
    {
        $this->recipient = $recipient;
        $this->mailable = $mailable;
        $this->mailableClass = get_class($mailable);
        $this->subject = $subject;

        // Create log entry immediately upon dispatch
        $log = EmailLog::create([
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'mailable_class' => $this->mailableClass,
            'status' => 'pending',
        ]);
        $this->logId = $log->id;
    }

    public function handle(): void
    {
        $log = EmailLog::find($this->logId);

        try {
            Mail::to($this->recipient)->send($this->mailable);
            
            if ($log) {
                $log->update(['status' => 'sent']);
            }
        } catch (Exception $e) {
            if ($log) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $log = EmailLog::find($this->logId);
        if ($log) {
            $log->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage()
            ]);
        }
    }
}
