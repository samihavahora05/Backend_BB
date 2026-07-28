<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Lead;

class ContactInquiryUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $lead;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('We have received your inquiry - BlueBoxx DA')
                    ->greeting('Hello ' . $this->lead->name . ',')
                    ->line('Thank you for reaching out to BlueBoxx DA! We have successfully received your inquiry.')
                    ->line('One of our academic counselors will review your request and get back to you shortly (usually within 24 hours).')
                    ->line('If your inquiry is urgent, please feel free to call our support hotline.')
                    ->line('Thank you for considering BlueBoxx DA!');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
