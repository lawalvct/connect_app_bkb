<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Ad;

class AdOwnerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $adId = $request->route('id');
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $ad = Ad::find($adId);

        if (!$ad) {
            return response()->json(['error' => 'Advertisement not found'], 404);
        }

        if ($ad->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
