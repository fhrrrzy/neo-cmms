<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    private string $secretKey;
    private string $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct()
    {
        $this->secretKey = config('services.turnstile.secret_key', '0x4AAAAAAB7x0o5K56GqDyNT5OsnHZlryzk');
    }

    /**
     * Verify the Turnstile token
     *
     * @param string $token
     * @param string|null $remoteIp
     * @return bool
     */
    public function verify(string $token, ?string $remoteIp = null): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->post($this->verifyUrl, [
                'secret' => $this->secretKey,
                'response' => $token,
                'remoteip' => $remoteIp ?? request()->ip(),
            ]);

            if (!$response->successful()) {
                Log::warning('Turnstile verification failed: HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                Log::warning('Turnstile verification failed', [
                    'errors' => $data['error-codes'] ?? [],
                    'token' => substr($token, 0, 10) . '...',
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Turnstile verification exception', [
                'message' => $e->getMessage(),
                'token' => substr($token, 0, 10) . '...',
            ]);
            return false;
        }
    }
}
