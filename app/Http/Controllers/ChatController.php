<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'success' => true,
            'data' => null
        ]);
    }

    public function store(Request $request, $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully'
        ]);
    }
}
