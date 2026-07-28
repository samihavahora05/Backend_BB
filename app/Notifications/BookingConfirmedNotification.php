<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\MentorBooking;

class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;

    public function __construct(MentorBooking $booking)
    {
        $this->booking = $booking;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Mentorship Session Confirmed')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Your mentorship session with ' . $this->booking->expert->user->name . ' has been confirmed.')
                    ->line('Date: ' . $this->booking->booking_date->format('F j, Y'))
                    ->line('Time: ' . $this->booking->start_time . ' - ' . $this->booking->end_time)
                    ->action('Join Meeting', $this->booking->meeting_link ?? url('/dashboard'))
                    ->line('Thank you for using BlueBoxx!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Session Confirmed',
            'message' => 'Your session with ' . $this->booking->expert->user->name . ' is confirmed.',
            'type' => 'booking_confirmed',
            'extra_data' => [
                'booking_id' => $this->booking->id,
                'meeting_link' => $this->booking->meeting_link
            ]
        ];
    }
}
