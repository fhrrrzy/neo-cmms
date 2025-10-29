<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateWebhookApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Webhook-Key') ?? $request->input('api_key');
        $validApiKey = config('webhook.api_key');

        // If no API key is configured, log warning and allow (for backward compatibility)
        if (empty($validApiKey)) {
            \Illuminate\Support\Facades\Log::warning('Webhook API key not configured - webhook is publicly accessible');
            return $next($request);
        }

        // Check if API key matches
        if ($apiKey !== $validApiKey) {
            \Illuminate\Support\Facades\Log::warning('Invalid webhook API key attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Invalid API key',
            ], 401);
        }

        return $next($request);
    }
}
