<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ExpertProfile;

class FixExpertProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-expert-profiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates missing expert profiles for users with the expert role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding expert users missing profiles...');
        
        $expertUsers = User::role('expert')->whereDoesntHave('expertProfile')->get();
        
        if ($expertUsers->isEmpty()) {
            $this->info('No missing profiles found.');
            return;
        }

        foreach ($expertUsers as $user) {
            ExpertProfile::create([
                'user_id' => $user->id,
                'designation' => 'Expert',
                'company' => 'Independent',
                'hourly_rate' => 0,
                'is_available' => true,
                'is_verified' => false
            ]);
            $this->info("Created profile for user ID: {$user->id} ({$user->email})");
        }
        
        $this->info('Finished fixing expert profiles.');
    }
}
