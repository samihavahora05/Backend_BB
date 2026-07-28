<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InternshipTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage internships');
    }

    public function rules(): array
    {
        return [
            'internship_id' => 'required|exists:internships,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:daily,weekly,project',
            'deadline' => 'nullable|date',
            'attachments' => 'nullable|array',
            'max_marks' => 'nullable|numeric|min:0',
        ];
    }
}
