<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    /**
     * Get platform summary metrics
     */
    public function getSummary(): JsonResponse
    {
        $summary = $this->service->getPlatformSummary();
        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => 'Dashboard summary retrieved successfully.'
        ]);
    }

    /**
     * Get chart data
     */
    public function getCharts(): JsonResponse
    {
        $charts = $this->service->getChartsData();
        return response()->json([
            'success' => true,
            'data' => $charts,
            'message' => 'Dashboard charts retrieved successfully.'
        ]);
    }

    /**
     * Get activity feed
     */
    public function getActivityFeed(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $feed = $this->service->getLiveActivity($limit);
        
        return response()->json([
            'success' => true,
            'data' => $feed,
            'message' => 'Activity feed retrieved successfully.'
        ]);
    }

    /**
     * Get recent specific module data
     */
    public function getRecentData(Request $request, string $module): JsonResponse
    {
        $data = $this->service->getRecentData($module);
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => "Recent {$module} retrieved successfully."
        ]);
    }

    /**
     * Get top specific module data
     */
    public function getTopLists(Request $request, string $module): JsonResponse
    {
        $data = $this->service->getTopLists($module);
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => "Top {$module} retrieved successfully."
        ]);
    }
}
