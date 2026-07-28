<?php

namespace App\Repositories\Contracts;

interface DashboardRepositoryInterface
{
    /**
     * Get aggregate counts for all major entities.
     */
    public function getPlatformSummary(): array;

    /**
     * Get revenue data for charts.
     */
    public function getRevenueChartData(string $period = 'monthly'): array;

    /**
     * Get student registration data for charts.
     */
    public function getRegistrationChartData(string $period = 'monthly'): array;

    /**
     * Get latest activity feed across the platform.
     */
    public function getLatestActivity(int $limit = 10): array;

    /**
     * Get recent specific data (enrollments, applications).
     */
    public function getRecentData(string $module, int $limit = 5): array;

    /**
     * Get top performing entities (courses, instructors).
     */
    public function getTopLists(string $module, int $limit = 5): array;
}
