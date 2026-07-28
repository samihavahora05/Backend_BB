<?php

namespace App\Observers;

use App\Models\Job;
use App\Models\User;
use App\Notifications\AdminAlertNotification;
use Illuminate\Support\Facades\Notification;

class JobObserver
{
    /**
     * Handle the Job "created" event.
     */
    public function created(Job $job): void
    {
        $admins = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['super_admin', 'super-admin', 'admin']);
        })->get();
        $companyName = $job->company->name ?? 'A company';

        $title = "New Job Posting Pending Approval";
        $message = "{$companyName} posted a new job: {$job->title}.";
        $actionUrl = "/admin/jobs";

        Notification::send($admins, new AdminAlertNotification($title, $message, $actionUrl, 'Jobs'));
    }
}
