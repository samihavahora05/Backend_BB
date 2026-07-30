<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\CourseCategory;
use App\Models\CourseLevel;
use App\Models\Course;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class HealthController extends Controller
{
    public function health()
    {
        return response()->json(['status' => 'ok', 'timestamp' => now()]);
    }
}
