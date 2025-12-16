<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\UserOnlineStatusChanged;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActivity
{
    /**
     * Handle an incoming request.
     * Updates the user's last_activity_at timestamp and is_online status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Update last activity after the request is processed
        if (Auth::guard('sanctum')->check()) {
            try {
                $user = Auth::guard('sanctum')->user();

                // Only update if more than 1 minute since last update (throttle updates)
                if (!$user->last_activity_at || $user->last_activity_at->lt(now()->subMinute())) {
                    // Check if user was previously offline (inactive for more than 5 minutes)
                    $wasOffline = !$user->last_activity_at || $user->last_activity_at->lt(now()->subMinutes(5));

                    $user->timestamps = false; // Don't update updated_at
                    $user->update([
                        'last_activity_at' => now(),
                        'is_online' => true,
                    ]);
                    $user->timestamps = true;

                    // Broadcast online status if user just came online
                    if ($wasOffline) {
                        try {
                            event(new UserOnlineStatusChanged($user->fresh(), true));
                        } catch (\Exception $e) {
                            // Don't let broadcast errors affect the response
                            Log::warning('Failed to broadcast online status', [
                                'user_id' => $user->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Don't let activity tracking errors affect the response
                Log::error('Failed to update last activity', [
                    'user_id' => Auth::guard('sanctum')->id(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $response;
    }
}
