<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Lead;

class ContactInquiryAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $lead;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('New Lead Inquiry: ' . ($this->lead->subject ?? 'Website Contact Form'))
                    ->greeting('Hello Admin,')
                    ->line('A new inquiry has been submitted on the BlueBoxx DA website.')
                    ->line('**Name:** ' . $this->lead->name)
                    ->line('**Email:** ' . $this->lead->email)
                    ->line('**Phone:** ' . ($this->lead->phone ?? 'N/A'))
                    ->line('**Course Interested:** ' . ($this->lead->course_interested ?? 'N/A'))
                    ->line('**Message:**')
                    ->line($this->lead->message ?? 'No message provided.')
                    ->line('---')
                    ->line('**Metadata:**')
                    ->line('Source Page: ' . ($this->lead->source_page ?? 'N/A'))
                    ->line('IP Address: ' . ($this->lead->ip_address ?? 'N/A'))
                    ->action('View Lead in Admin', url('/admin/leads/' . $this->lead->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_lead',
            'title' => 'New ' . ($this->lead->type ?? 'Inquiry'),
            'message' => $this->lead->name . ' submitted a new inquiry.',
            'link' => '/admin/crm',
            'lead_id' => $this->lead->id
        ];
    }
}
