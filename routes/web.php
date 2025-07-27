<?php

use App\Http\Controllers\API\V1\AdController;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fallback login route (redirects to admin login)
Route::get('/login', function () {
    return redirect()->route('admin.auth.login');
})->name('login');

// Test route for live streaming functionality
Route::get('/test-streaming', function () {
    return view('test-streaming');
});

// Simple test route (no external CDNs)
Route::get('/simple-streaming-test', function () {
    return view('simple-streaming-test');
});

// Debug route
Route::get('/debug-streaming', function () {
    return view('debug-streaming');
});

// Public API endpoint for stream data (bypasses API middleware)
Route::get('/api/streams/latest', function () {
    try {
        $controller = new \App\Http\Controllers\API\V1\StreamController();
        $request = request();
        return $controller->latest($request);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching streams: ' . $e->getMessage()
        ], 500);
    }
});

// Public API endpoint for viewer count (bypasses API middleware)
// Note: Temporarily disabled due to routing conflicts
// Route::get('/api/streams/{id}/viewers', function ($id) {
//     try {
//         $controller = new \App\Http\Controllers\API\V1\StreamController();
//         $request = request();
//         return $controller->viewers($request, $id);
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Error fetching viewers: ' . $e->getMessage()
//         ], 500);
//     }
// });

// Public API endpoint for getting viewer tokens (bypasses API middleware)
Route::post('/api/streams/viewer-token', function () {
    try {
        $channelName = request('channel_name');
        $uid = request('uid', null);

        if (!$channelName) {
            return response()->json([
                'success' => false,
                'message' => 'Channel name is required'
            ], 400);
        }

        // Generate a random UID if not provided
        if (!$uid) {
            $uid = rand(100000, 999999);
        }

        // Initialize AgoraHelper
        \App\Helpers\AgoraHelper::init();

        // Generate token for viewer (subscriber role)
        $token = \App\Helpers\AgoraHelper::generateRtcToken($channelName, (int)$uid, 3600, 'subscriber');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate token'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'app_id' => \App\Helpers\AgoraHelper::getAppId(),
                'channel_name' => $channelName,
                'uid' => $uid,
                'expires_in' => 3600
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error generating token: ' . $e->getMessage()
        ], 500);
    }
});

// Watch live stream by user ID
Route::get('/watch/{userId}', function ($userId) {
    return view('watch-stream', compact('userId'));
});

Route::get('/test-mail', function () {
    try {
        Mail::raw('Test email from ConnectApp', function ($message) {
            $message->to('lawalthb@gmail.com')
                    ->subject('Test Email');
        });
        return 'Email sent successfully!';
    } catch (\Exception $e) {
        return 'Email error: ' . $e->getMessage();
    }
});
// Google OAuth routes - moved to web middleware
Route::get('api/v1/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('api/v1/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);





Route::get('/payment/callback', [SubscriptionController::class, 'handleNombaCallbackWeb'])->name('payment.callback.web');


//for ads redirection
    Route::get('/payment/{payment}/success', [AdController::class, 'paymentSuccess'])
        ->name('ads.payment.success');
    Route::get('/payment/{payment}/cancel', [AdController::class, 'paymentCancel'])
        ->name('ads.payment.cancel');
