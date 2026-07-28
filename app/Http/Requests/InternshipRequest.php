<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InternshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage internships');
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'mode' => 'nullable|in:Remote,Hybrid,Onsite',
            'duration_months' => 'nullable|integer',
            'duration' => 'nullable|string|max:50',
            'stipend' => 'nullable|numeric|min:0|max:9999999',
            'skills_required' => 'nullable|array|max:20',
            'skills_required.*' => 'string|max:50',
            'eligibility' => 'nullable|string',
            'description' => 'nullable|string|max:10000',
            'responsibilities' => 'nullable|string|max:10000',
            'learning_outcomes' => 'nullable|string|max:10000',
            'openings' => 'nullable|integer|min:1|max:1000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'application_deadline' => 'nullable|date',
            'status' => 'nullable|in:open,closed,draft,archived',
            'featured' => 'nullable|boolean',
        ];
    }
}
