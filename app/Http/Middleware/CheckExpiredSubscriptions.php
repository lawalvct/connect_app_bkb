<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\UserSubscriptionHelper;
use Illuminate\Support\Facades\Cache;

class CheckExpiredSubscriptions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            $userId = $request->user()->id;
            $cacheKey = "subscription_check_{$userId}";
            
            // Check only once every 5 minutes per user to avoid excessive DB queries
            if (!Cache::has($cacheKey)) {
                UserSubscriptionHelper::expireUserSubscriptions($userId);
                Cache::put($cacheKey, true, now()->addMinutes(5));
            }
        }

        return $next($request);
    }
}
