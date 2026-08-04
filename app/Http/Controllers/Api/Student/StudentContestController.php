<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContestRegistration;

class StudentContestController extends Controller
{
    /**
     * Get the student's contest registrations.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $registrations = ContestRegistration::with('contest')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($reg) {
                return [
                    'id' => $reg->id,
                    'status' => $reg->status,
                    'score' => $reg->score,
                    'rank' => $reg->rank,
                    'contest' => $reg->contest,
                    'registered_at' => $reg->created_at->toIso8601String(),
                ];
            });

        $active = $registrations->filter(function ($reg) {
            return optional($reg['contest'])->status === 'active' || optional($reg['contest'])->status === 'upcoming';
        })->values();

        $past = $registrations->filter(function ($reg) {
            return optional($reg['contest'])->status === 'completed' || optional($reg['contest'])->status === 'cancelled';
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'active' => $active,
                'past' => $past,
                'total_participated' => $registrations->count()
            ]
        ]);
    }
}
