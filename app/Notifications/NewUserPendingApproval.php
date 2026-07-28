<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class NewUserPendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    protected $newUser;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $newUser)
    {
        $this->newUser = $newUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $role = $this->newUser->roles->first()?->name ?? 'User';
        return [
            'type' => 'user_approval',
            'user_id' => $this->newUser->id,
            'email' => $this->newUser->email,
            'role' => $role,
            'message' => "A new {$role} ({$this->newUser->email}) has registered and is pending your approval.",
            'action_url' => '/admin/approvals'
        ];
    }
}
