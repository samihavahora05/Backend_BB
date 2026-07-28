<?php

namespace App\Notifications;

use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PlatformNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $title;
    public string $message;
    public array $extraData;
    public string $notificationType;

    public function __construct(string $title, string $message, string $notificationType, array $extraData = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->notificationType = $notificationType;
        $this->extraData = $extraData;
    }

    public function via(object $notifiable): array
    {
        // All platform notifications go to database.
        // We will trigger Push (FCM) if the user has tokens.
        $channels = ['database'];

        // Automatically trigger push notification on send
        $fcmService = resolve(FcmService::class);
        $fcmService->sendToUser($notifiable, $this->title, $this->message, array_merge([
            'type' => $this->notificationType,
        ], $this->extraData));

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->notificationType,
            'extra_data' => $this->extraData,
        ];
    }
}
