<?php

namespace App\Observers;

use App\Models\Internship;
use App\Models\User;
use App\Notifications\AdminAlertNotification;
use Illuminate\Support\Facades\Notification;

class InternshipObserver
{
    /**
     * Handle the Internship "created" event.
     */
    public function created(Internship $internship): void
    {
        $admins = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['super_admin', 'super-admin', 'admin']);
        })->get();
        $companyName = $internship->company->name ?? 'A company';

        $title = "New Internship Posting Pending Approval";
        $message = "{$companyName} posted a new internship: {$internship->title}.";
        $actionUrl = "/admin/internships";

        Notification::send($admins, new AdminAlertNotification($title, $message, $actionUrl, 'Internships'));
    }
}
