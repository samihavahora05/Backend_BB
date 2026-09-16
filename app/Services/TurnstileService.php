<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    private string $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.turnstile.secret_key') ?: env('TURNSTILE_SECRET_KEY', '1x0000000000000000000000000000000AA');
    }

    /**
     * Verify Cloudflare Turnstile response token with Cloudflare's siteverify API.
     *
     * @param string|null $token
     * @param string|null $ip
     * @return array{success: bool, message: string, errors: array}
     */
    public function verify(?string $token, ?string $ip = null): array
    {
        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Human verification failed. Please complete the captcha.',
                'errors' => ['missing-input-response']
            ];
        }

        try {
            $payload = [
                'secret' => $this->secretKey,
                'response' => $token,
            ];

            if (!empty($ip)) {
                $payload['remoteip'] = $ip;
            }

            $response = Http::asForm()
                ->timeout(10)
                ->post($this->verifyUrl, $payload);

            if (!$response->successful()) {
                Log::warning('Cloudflare Turnstile verification HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'Human verification failed. Please try again.',
                    'errors' => ['http-error-' . $response->status()]
                ];
            }

            $data = $response->json();
            $isSuccess = (bool) ($data['success'] ?? false);

            if (!$isSuccess) {
                Log::info('Cloudflare Turnstile verification rejected', [
                    'error_codes' => $data['error-codes'] ?? [],
                    'ip' => $ip
                ]);

                return [
                    'success' => false,
                    'message' => 'Human verification failed. Please try again.',
                    'errors' => $data['error-codes'] ?? ['verification-failed']
                ];
            }

            return [
                'success' => true,
                'message' => 'Verification successful.',
                'errors' => []
            ];
        } catch (\Throwable $e) {
            Log::error('Cloudflare Turnstile verification exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Unable to complete human verification due to network issue. Please try again.',
                'errors' => ['exception']
            ];
        }
    }
}
