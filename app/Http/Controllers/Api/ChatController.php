<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // Mock conversations
    private $conversations = [
        [
            'id' => 'conv-1',
            'user' => ['name' => 'Acme Corp HR', 'role' => 'Company', 'avatar' => null],
            'last_message' => 'Are you available for a quick call tomorrow?',
            'unread' => 2,
            'updated_at' => '10:30 AM'
        ],
        [
            'id' => 'conv-2',
            'user' => ['name' => 'Dr. Smith', 'role' => 'Expert', 'avatar' => null],
            'last_message' => 'Looking forward to our session.',
            'unread' => 0,
            'updated_at' => 'Yesterday'
        ]
    ];

    // Mock messages for a specific conversation
    private $messages = [
        'conv-1' => [
            ['id' => 'm-1', 'sender' => 'them', 'text' => 'Hi! We reviewed your application.', 'time' => '10:00 AM'],
            ['id' => 'm-2', 'sender' => 'me', 'text' => 'Hello! Thank you for getting back to me.', 'time' => '10:15 AM'],
            ['id' => 'm-3', 'sender' => 'them', 'text' => 'Are you available for a quick call tomorrow?', 'time' => '10:30 AM'],
        ],
        'conv-2' => [
            ['id' => 'm-4', 'sender' => 'me', 'text' => 'Hi Dr. Smith, I have shared my portfolio.', 'time' => 'Yesterday'],
            ['id' => 'm-5', 'sender' => 'them', 'text' => 'Looking forward to our session.', 'time' => 'Yesterday'],
        ]
    ];

    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->conversations
        ]);
    }

    public function show(Request $request, $id)
    {
        return response()->json([
            'success' => true,
            'data' => $this->messages[$id] ?? []
        ]);
    }

    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'text' => 'required|string'
        ]);

        $newMessage = [
            'id' => 'm-' . time(),
            'sender' => 'me',
            'text' => $validated['text'],
            'time' => date('h:i A')
        ];

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $newMessage
        ], 201);
    }
}
