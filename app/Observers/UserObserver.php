<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\AdminAlertNotification;
use Illuminate\Support\Facades\Notification;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Don't notify if the user was created by an admin (or is an admin)
        if ($user->hasAnyRole(['admin', 'super_admin', 'super-admin'])) {
            return;
        }

        $admins = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['super_admin', 'super-admin', 'admin']);
        })->get();
        
        $roleName = $user->roles->first()->name ?? 'User';
        
        $title = "New {$roleName} Registration";
        $message = "{$user->first_name} {$user->last_name} registered as a new {$roleName}.";
        
        // Determine URL based on role
        $actionUrl = "/admin/users";
        if ($roleName === 'student') $actionUrl = "/admin/users/students";
        if ($roleName === 'expert') $actionUrl = "/admin/users/experts";
        if ($roleName === 'company') $actionUrl = "/admin/users/companies";
        if ($roleName === 'college') $actionUrl = "/admin/users/colleges";

        Notification::send($admins, new AdminAlertNotification($title, $message, $actionUrl, 'Users'));
    }
}
