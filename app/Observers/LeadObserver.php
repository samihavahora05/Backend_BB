<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\AdminAlertNotification;
use Illuminate\Support\Facades\Notification;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        $admins = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['super_admin', 'super-admin', 'admin']);
        })->get();

        $title = "New {$lead->type} Lead";
        $message = "{$lead->name} submitted a new inquiry.";
        $actionUrl = "/admin/crm/" . \Illuminate\Support\Str::slug($lead->type);

        Notification::send($admins, new AdminAlertNotification($title, $message, $actionUrl, 'CRM'));
    }
}
