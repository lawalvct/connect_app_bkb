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
        // Get the origin from the request
        $origin = $request->headers->get('Origin');

        // Define allowed origins
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:8080',
            'http://localhost:8081',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8080',
            'https://stg.connectinc.app',  // Production frontend
        'https://dick-connect-app-1zqh.vercel.app',   // Staging frontend
        '*',
        ];

        // For development, you can temporarily use '*' for any origin
        // Comment out the above array and uncomment the line below for development
        // $allowedOrigins = ['*'];

        // Determine the origin to allow
        $allowOrigin = '*';
        if (in_array($origin, $allowedOrigins)) {
            $allowOrigin = $origin;
        } elseif (in_array('*', $allowedOrigins)) {
            $allowOrigin = '*';
        }

        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200, [
                'Access-Control-Allow-Origin' => $allowOrigin,
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD',
                'Access-Control-Allow-Headers' => 'Origin, Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Cache-Control, Pragma',
                'Access-Control-Expose-Headers' => 'Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN',
                'Access-Control-Max-Age' => '86400',
                'Access-Control-Allow-Credentials' => 'true',
                'Vary' => 'Origin',
            ]);
        }

        $response = $next($request);

        // Add CORS headers to actual requests
        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Cache-Control, Pragma');
        $response->headers->set('Access-Control-Expose-Headers', 'Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Vary', 'Origin');

        return $response;
    }
}
