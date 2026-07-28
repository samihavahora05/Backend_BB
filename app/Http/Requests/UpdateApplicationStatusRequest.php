<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage internships');
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:applied,under_review,shortlisted,interview,rejected,selected,offer_sent,joined,completed',
            'internal_notes' => 'nullable|string'
        ];
    }
}
