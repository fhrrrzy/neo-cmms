<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\TurnstileService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];

        // Only require Turnstile in production
        if (app()->environment('production')) {
            $rules['cf-turnstile-response'] = ['required', 'string'];
        }

        return $rules;
    }

    /**
     * Validate the request's credentials and return the user without logging them in.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateCredentials(): User
    {
        $this->ensureIsNotRateLimited();
        $this->validateTurnstile();

        /** @var User|null $user */
        $user = Auth::getProvider()->retrieveByCredentials($this->only('email', 'password'));

        if (! $user || ! Auth::getProvider()->validateCredentials($user, $this->only('password'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * Validate the Turnstile token
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateTurnstile(): void
    {
        // Only validate Turnstile in production
        if (!app()->environment('production')) {
            return;
        }

        $turnstileService = app(TurnstileService::class);
        $token = $this->input('cf-turnstile-response');

        if (!$turnstileService->verify($token, $this->ip())) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Turnstile verification failed. Please try again.',
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate-limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return $this->string('email')
            ->lower()
            ->append('|' . $this->ip())
            ->transliterate()
            ->value();
    }
}
