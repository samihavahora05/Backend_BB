<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage students');
    }

    public function rules(): array
    {
        $userId = $this->route('student'); // ID from URL

        return [
            'first_name' => 'sometimes|string|max:50',
            'last_name' => 'sometimes|string|max:50',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'status' => 'nullable|string|in:active,inactive,suspended,blocked,archived',
            
            // Profile fields
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female,Other,male,female,other',
            'student_type' => 'nullable|string',
            'company_name' => 'nullable|string',
            'job_title' => 'nullable|string',
            'identification_number' => 'nullable|string',
            'address_line_1' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'pin' => 'nullable|string',
            'bio' => 'nullable|string',

            // Academic fields
            'course' => 'nullable|string',
            'department' => 'nullable|string',
            'semester' => 'nullable|integer',
            'college_name' => 'nullable|string',
            
            // Social fields
            'github_url' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'portfolio_url' => 'nullable|url',
            
            // Skills
            'skills' => 'nullable|string', // JSON string

            // Files
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'resume' => 'nullable|mimes:pdf,doc,docx|max:5120',
        ];
    }
}
