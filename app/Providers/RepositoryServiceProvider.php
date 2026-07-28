<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Eloquent\DashboardRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(\App\Repositories\Contracts\InternshipRepositoryInterface::class, \App\Repositories\Eloquent\InternshipRepository::class);
        $this->app->bind(\App\Repositories\Contracts\StudentRepositoryInterface::class, \App\Repositories\Eloquent\StudentRepository::class);
        $this->app->bind(\App\Repositories\Contracts\JobRepositoryInterface::class, \App\Repositories\Eloquent\JobRepository::class);
        $this->app->bind(\App\Repositories\Contracts\InstructorRepositoryInterface::class, \App\Repositories\Eloquent\InstructorRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
