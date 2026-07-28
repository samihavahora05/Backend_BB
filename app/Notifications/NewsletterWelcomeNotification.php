<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\NewsletterSubscriber;

class NewsletterWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $subscriber;

    public function __construct(NewsletterSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Welcome to the BlueBoxx Newsletter!')
                    ->greeting('Hello,')
                    ->line('Thank you for subscribing to the BlueBoxx DA Newsletter.')
                    ->line('You are now part of our community. Expect to receive the latest updates, exclusive course discounts, and industry news right in your inbox.')
                    ->action('Explore Courses', url('/courses'))
                    ->line('If you ever wish to unsubscribe, you can do so at any time.');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
