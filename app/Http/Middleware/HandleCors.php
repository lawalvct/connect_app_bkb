<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For browser Postman compatibility, allow all origins in development
        $allowOrigin = '*';

        // Define allowed origins for production
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:8080',
            'http://localhost:8081',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8080',
            'https://stg.connectinc.app',  // Production frontend
            'https://dick-connect-app-1zqh.vercel.app',   // Staging frontend
            '*', // Allow all origins for development/testing
        ];

        // Get the origin from the request
        $origin = $request->headers->get('Origin');

        // In development, allow all origins for browser Postman
        if (app()->environment('local') || in_array('*', $allowedOrigins)) {
            $allowOrigin = $origin ?: '*';
        } else {
            // In production, check allowed origins
            if (in_array($origin, $allowedOrigins)) {
                $allowOrigin = $origin;
            }
        }

        // Handle preflight OPTIONS requests immediately
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $allowOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD')
                ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Cache-Control, Pragma')
                ->header('Access-Control-Expose-Headers', 'Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN')
                ->header('Access-Control-Max-Age', '86400')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Vary', 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers');
        }

        $response = $next($request);

        // Add CORS headers to actual requests
        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Cache-Control, Pragma');
        $response->headers->set('Access-Control-Expose-Headers', 'Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Vary', 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers');

        return $response;
    }
}
