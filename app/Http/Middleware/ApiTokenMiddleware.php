<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->bearerToken();

        // Hardcoded API token for simplicity
        $validToken = env('API_TOKEN', 'test-api-token');

        if (!$token || $token !== $validToken) {
            return response()->json([
                'message' => 'Unauthorized: Invalid API token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
