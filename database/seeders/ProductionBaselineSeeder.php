<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ProductionBaselineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Safely executes idempotent baseline data synchronization.
     */
    public function run(): void
    {
        $this->command->info('🚀 Running Production Baseline Seeder (Safe & Non-Destructive)...');
        Artisan::call('app:sync-baseline-data', ['--force' => true]);
        $this->command->info(Artisan::output());
    }
}
