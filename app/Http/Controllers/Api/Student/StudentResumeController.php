<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentDocument;

class StudentResumeController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $user = auth()->user();

        if ($request->hasFile('resume')) {
            $path = $request->file('resume')->store('resumes', 'public');

            $document = StudentDocument::updateOrCreate(
                ['user_id' => $user->id, 'type' => 'resume'],
                ['file_path' => $path, 'title' => 'My Resume']
            );

            return response()->json([
                'success' => true,
                'message' => 'Resume uploaded successfully!',
                'data' => [
                    'resume_url' => asset('storage/' . $path)
                ]
            ], 201);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
    }
}
