<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // This expects the resource to be an array or object containing aggregated data
        return [
            'metrics' => $this['metrics'] ?? null,
            'courses' => $this['courses'] ?? [],
            'internships' => $this['internships'] ?? [],
            'jobs' => $this['jobs'] ?? [],
            'attendance' => $this['attendance'] ?? [],
        ];
    }
}
