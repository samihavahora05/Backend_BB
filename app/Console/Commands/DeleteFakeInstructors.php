<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ExpertProfile;

class DeleteFakeInstructors extends Command
{
    protected $signature = 'instructors:delete-fake';
    protected $description = 'Delete all fake seeded instructor records (example.net / example.org / example.com emails)';

    public function handle()
    {
        // 1. Delete orphaned expert profiles (user was deleted but profile remains)
        $orphaned = \App\Models\ExpertProfile::withTrashed()
            ->whereDoesntHave('user')
            ->get();

        foreach ($orphaned as $profile) {
            $this->line("Deleting orphaned profile ID: {$profile->id}");
            $profile->forceDelete();
        }
        $this->info("✅ Deleted {$orphaned->count()} orphaned profile(s).");

        // 2. Find all expert users with fake emails
        $fakeUsers = User::role('expert')
            ->where(function($q) {
                $q->where('email', 'like', '%@example.net')
                  ->orWhere('email', 'like', '%@example.org')
                  ->orWhere('email', 'like', '%@example.com')
                  ->orWhere('email', 'like', '%@faker%')
                  ->orWhere('first_name', 'Unknown')
                  ->orWhere('email', 'instructor@blueboxx.com'); // demo seeded one
            })->get();

        $count = $fakeUsers->count();

        if ($count === 0) {
            $this->info('No fake instructors found.');
            return 0;
        }

        foreach ($fakeUsers as $user) {
            $this->line("Deleting: {$user->first_name} {$user->last_name} <{$user->email}>");
            // Force delete so they don't appear with soft-delete either
            \App\Models\ExpertProfile::withTrashed()->where('user_id', $user->id)->forceDelete();
            $user->forceDelete();
        }

        $this->info("✅ Deleted {$count} fake instructor(s) successfully.");
        return 0;
    }
}
