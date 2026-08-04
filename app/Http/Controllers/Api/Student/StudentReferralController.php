<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReferralCode;
use App\Models\Referral;
use App\Models\ReferralEarning;
use Illuminate\Support\Str;

class StudentReferralController extends Controller
{
    /**
     * Get the student's referral dashboard stats and history.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get or create referral code
        $referralCode = ReferralCode::firstOrCreate(
            ['user_id' => $user->id],
            ['code' => strtoupper(Str::random(8))]
        );

        // Calculate stats
        $totalReferrals = Referral::where('referrer_id', $user->id)->count();
        $successfulReferrals = Referral::where('referrer_id', $user->id)->where('status', 'successful')->count();
        $pendingReferrals = Referral::where('referrer_id', $user->id)->where('status', 'pending')->count();
        $totalEarnings = ReferralEarning::where('user_id', $user->id)->sum('amount');

        // Get history
        $referrals = Referral::with('referredUser:id,name,email')
            ->where('referrer_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $referralCode->code,
                'total_clicks' => $referralCode->total_clicks,
                'stats' => [
                    'total' => $totalReferrals,
                    'successful' => $successfulReferrals,
                    'pending' => $pendingReferrals,
                    'earnings' => (float) $totalEarnings
                ],
                'history' => $referrals->map(function ($ref) {
                    return [
                        'id' => $ref->id,
                        'name' => $ref->referredUser->name ?? 'Unknown',
                        'email' => $ref->referredUser->email ?? '',
                        'status' => $ref->status,
                        'reward_amount' => (float) $ref->reward_amount,
                        'date' => $ref->created_at->format('M d, Y')
                    ];
                })
            ]
        ]);
    }

    /**
     * Track a click on the referral link.
     */
    public function trackClick(Request $request, $code)
    {
        $referralCode = ReferralCode::where('code', $code)->first();
        if ($referralCode) {
            $referralCode->increment('total_clicks');
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Code not found'], 404);
    }
}
