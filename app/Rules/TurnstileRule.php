<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Services\TurnstileService;

class TurnstileRule implements ValidationRule
{
    protected TurnstileService $turnstileService;

    public function __construct(?TurnstileService $turnstileService = null)
    {
        $this->turnstileService = $turnstileService ?? new TurnstileService();
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $token = is_string($value) ? trim($value) : null;
        if (empty($token)) {
            return; // Cleanly pass when no token is provided
        }

        $ip = request()->ip();
        $result = $this->turnstileService->verify($token, $ip);

        if (!$result['success']) {
            $fail($result['message'] ?: 'Human verification failed. Please try again.');
        }
    }
}
