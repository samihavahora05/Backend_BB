<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OptimizeProd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize-prod';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fully optimize the application for production deployment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Production Optimization...');

        // 1. Clear existing caches
        $this->info('Clearing old caches...');
        Artisan::call('optimize:clear');

        // 2. Cache Configuration
        $this->info('Caching configuration...');
        Artisan::call('config:cache');

        // 3. Cache Routes
        $this->info('Caching routes...');
        Artisan::call('route:cache');

        // 4. Cache Events
        $this->info('Caching events...');
        Artisan::call('event:cache');

        // 5. Cache Views
        $this->info('Caching views...');
        Artisan::call('view:cache');

        $this->info('Application is now perfectly optimized for production! 🚀');
        return Command::SUCCESS;
    }
}
