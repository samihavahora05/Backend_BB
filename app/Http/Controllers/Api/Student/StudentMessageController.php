<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MessageThread;

class StudentMessageController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Fetch threads where the user is a recipient
        $threads = MessageThread::whereHas('recipients', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->with(['messages' => function($q) {
            $q->latest()->limit(1);
        }, 'recipients.user' => function($q) {
            $q->select('id', 'first_name', 'last_name', 'email');
        }])
        ->latest('updated_at')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $threads
        ]);
    }
}
