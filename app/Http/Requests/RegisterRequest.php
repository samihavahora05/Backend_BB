<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

use App\Rules\TurnstileRule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Support alternative field names for Turnstile token
        if (!$this->has('turnstile_token')) {
            $altToken = $this->input('cf_turnstile_response') 
                ?? $this->input('cf-turnstile-response') 
                ?? $this->input('turnstileResponse');

            if ($altToken) {
                $this->merge(['turnstile_token' => $altToken]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role' => 'nullable|string|in:student,intern,job-seeker,jobseeker,company,college,expert',
            'turnstile_token' => ['nullable', 'string', new TurnstileRule()],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'turnstile_token.required' => 'Human verification is required. Please complete the captcha.',
        ];
    }
}