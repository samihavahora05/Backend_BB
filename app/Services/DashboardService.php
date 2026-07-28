<?php

namespace App\Services;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    protected DashboardRepositoryInterface $repository;

    public function __construct(DashboardRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get platform summary with 15-minute caching to prevent DB overload.
     */
    public function getPlatformSummary(): array
    {
        return Cache::remember('dashboard.summary', now()->addMinutes(15), function () {
            return $this->repository->getPlatformSummary();
        });
    }

    /**
     * Get charts data with 15-minute caching.
     */
    public function getChartsData(): array
    {
        return Cache::remember('dashboard.charts', now()->addMinutes(15), function () {
            return [
                'revenue' => $this->repository->getRevenueChartData(),
                'registrations' => $this->repository->getRegistrationChartData(),
            ];
        });
    }

    /**
     * Get live activity feed (bypassing cache for real-time updates).
     */
    public function getLiveActivity(int $limit = 10): array
    {
        return $this->repository->getLatestActivity($limit);
    }

    /**
     * Get top lists with 1-hour caching (rarely changes minute-by-minute).
     */
    public function getTopLists(string $module): array
    {
        return Cache::remember("dashboard.top.{$module}", now()->addHours(1), function () use ($module) {
            return $this->repository->getTopLists($module);
        });
    }

    /**
     * Get recent data (live).
     */
    public function getRecentData(string $module): array
    {
        return $this->repository->getRecentData($module);
    }
}
