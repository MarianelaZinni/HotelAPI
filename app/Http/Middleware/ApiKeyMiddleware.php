<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $headerKey = $request->header('X-API-KEY');

        $apiKey = env('API_KEY');

        if (!$apiKey || !$headerKey || !hash_equals($apiKey, $headerKey)) {
            return response()->json([
                'message' => 'Unauthorized. Invalid or missing API Key.'
            ], 401);
        }

        return $next($request);
    }
}
