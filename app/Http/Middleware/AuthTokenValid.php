<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthTokenValid
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check for token in cookie or Authorization header
        $token = $request->cookie('auth_token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Missing token.',
            ], 401);
        }

        // 2. Your existing token validation (keep this as-is)
        if (!$this->isValidToken($token)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Invalid token.',
            ], 401);
        }

        // 3. Get the response from the next middleware/controller
        $response = $next($request);

        // 4. ONLY ADD THIS PART - Adds success status to JSON responses
        if ($request->wantsJson() && $response->getStatusCode() === 200) {
            $originalData = json_decode($response->content(), true) ?? [];

            if (!isset($originalData['status'])) {
                $response->setData([
                    'status' => true,
                    'message' => 'Request successful',
                    'data' => $originalData
                ]);
            }
        }

        return $response;
    }

    // Keep your existing isValidToken() method
    protected function isValidToken(string $token): bool
    {
        // Your actual token validation logic here
        return true; // Replace with real implementation
    }
}
