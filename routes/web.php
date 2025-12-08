<?php

use App\Http\Controllers\API\V1\AdController;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;
use App\Helpers\StorageUploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    //return view('welcome');
    return redirect()->route('admin.auth.login');
});

// Fallback login route (redirects to admin login)
Route::get('/login', function () {
    return redirect()->route('admin.auth.login');
})->name('login');

// Test route for live streaming functionality
Route::get('/test-streaming', function () {
    return view('test-streaming');
});

// Test route for conversation module
Route::get('/test-conversation', function () {
    return view('test-conversation');
});

// Test route for call functionality
Route::get('/test-calls', function () {
    return view('call-test');
});

// Modern call testing interface
Route::get('/test-calls-modern', function () {
    return view('call-test-modern');
});

// Firebase configuration test page
Route::get('/firebase-test', function () {
    return view('firebase-test');
});

// Firebase setup helper page
Route::get('/firebase-setup', function () {
    return view('firebase-setup-helper');
});

// Broadcasting authentication route for Pusher
Route::post('/broadcasting/auth', function (Request $request) {
    // This will handle Pusher channel authentication
    return \Illuminate\Support\Facades\Broadcast::auth($request);
})->middleware(['auth:sanctum']);

// Test route to show user credentials
Route::get('/test-users', function () {
    $users = \App\Models\User::take(5)->get(['id', 'name', 'email']);
    $output = '<h2>Test User Credentials (Password: 12345678 for all)</h2><ul>';

    foreach ($users as $user) {
        $output .= '<li>Email: ' . $user->email . ' | Name: ' . $user->name . ' | ID: ' . $user->id . '</li>';
    }

    $output .= '</ul>';
    $output .= '<br><h3>Quick Login Test:</h3>';
    $output .= '<form method="POST" action="/api/v1/login" style="margin: 20px 0;">';
    $output .= '<input type="email" name="email" placeholder="Email" required style="padding: 10px; margin: 5px;"><br>';
    $output .= '<input type="password" name="password" placeholder="Password (12345678)" required style="padding: 10px; margin: 5px;"><br>';
    $output .= '<button type="submit" style="padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; cursor: pointer;">Login & Get Token</button>';
    $output .= '</form>';
    $output .= '<p><a href="/test-calls" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Go to Call Testing Interface</a></p>';

    return $output;
});

// Debug route for file upload testing
Route::get('/test-upload', function () {
    return view('test-upload');
});

Route::post('/test-upload', function (Request $request) {
    try {
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');

            Log::info('Test upload started', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_valid' => $file->isValid()
            ]);

            $result = StorageUploadHelper::uploadFile($file, 'profiles');

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $result
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No file provided'
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Test upload failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
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

// Watch live stream (enhanced mobile version with user ID)
Route::get('/stream/{streamId}/watch/{userId}', function ($streamId, $userId) {
    $stream = \App\Models\Stream::with('user')->findOrFail($streamId);

    // Get user from database for authentication simulation
    $user = \App\Models\User::find($userId);

    $hasPaid = false;
    $canChat = false;

    if ($user) {
        $canChat = true; // User exists, can chat

        if ($stream->price > 0) {
            $hasPaid = $stream->hasUserPaid($user);
        } else {
            $hasPaid = true; // Free stream
        }
    } else {
        // Guest user - can watch free streams but can't chat
        $hasPaid = ($stream->price == 0);
        $canChat = false;
    }

    return view('stream.watch-mobile', compact('stream', 'hasPaid', 'canChat', 'user', 'userId'));
});

// Watch live stream (enhanced mobile version - fallback without user ID)
Route::get('/stream/{streamId}/watch', function ($streamId) {
    $stream = \App\Models\Stream::with('user')->findOrFail($streamId);

    $hasPaid = false;
    $canChat = false;
    $user = null;
    $userId = null;

    if (auth()->check()) {
        $user = auth()->user();
        $userId = $user->id;
        $canChat = true;

        if ($stream->price > 0) {
            $hasPaid = $stream->hasUserPaid($user);
        } else {
            $hasPaid = true;
        }
    } else {
        // Guest user - can only watch free streams
        $hasPaid = ($stream->price == 0);
    }

    return view('stream.watch-mobile', compact('stream', 'hasPaid', 'canChat', 'user', 'userId'));
});

// Original watch page (desktop version)
Route::get('/stream/{streamId}/watch-desktop', function ($streamId) {
    $stream = \App\Models\Stream::with('user')->findOrFail($streamId);

    $hasPaid = false;
    if (auth()->check() && $stream->price > 0) {
        $hasPaid = $stream->hasUserPaid(auth()->user());
    } elseif ($stream->price == 0) {
        $hasPaid = true;
    }

    return view('stream.watch', compact('stream', 'hasPaid'));
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

// Stripe subscription success callback
Route::get('/subscription-success', [SubscriptionController::class, 'handleStripeSuccess'])->name('subscription.success');
Route::get('/subscription-cancel', [SubscriptionController::class, 'handleStripeCancel'])->name('subscription.cancel');

// Stream payment callbacks (no auth needed)
Route::get('/stream-payment/success', [\App\Http\Controllers\API\V1\StreamPaymentController::class, 'handleStripeSuccess'])->name('api.v1.stream-payments.stripe.success');
Route::get('/stream-payment/cancel', [\App\Http\Controllers\API\V1\StreamPaymentController::class, 'handleStripeCancel'])->name('api.v1.stream-payments.stripe.cancel');
Route::get('/stream-payment/callback', [\App\Http\Controllers\API\V1\StreamPaymentController::class, 'handleNombaCallback'])->name('api.v1.stream-payments.nomba.callback');

// Firebase service worker with dynamic config
Route::get('/firebase-messaging-sw.js', function () {
    $config = [
        'apiKey' => config('services.firebase.api_key'),
        'authDomain' => config('services.firebase.auth_domain'),
        'projectId' => config('services.firebase.project_id'),
        'storageBucket' => config('services.firebase.storage_bucket'),
        'messagingSenderId' => config('services.firebase.messaging_sender_id'),
        'appId' => config('services.firebase.app_id'),
    ];

    $js = "
// firebase-messaging-sw.js

// Import Firebase scripts
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

// Firebase configuration
const firebaseConfig = " . json_encode($config) . ";

// Initialize Firebase
firebase.initializeApp(firebaseConfig);

// Retrieve Firebase Messaging object
const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);

    const notificationTitle = payload.notification?.title || payload.data?.title || 'Admin Notification';
    const notificationOptions = {
        body: payload.notification?.body || payload.data?.body || 'You have a new notification',
        icon: payload.notification?.icon || '/admin-assets/img/logo.png',
        badge: '/admin-assets/img/badge.png',
        tag: payload.data?.type || 'admin-notification',
        requireInteraction: true,
        data: {
            url: payload.data?.url || '/admin/dashboard',
            type: payload.data?.type || 'general',
            ...payload.data
        }
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification click
self.addEventListener('notificationclick', function(event) {
    console.log('[firebase-messaging-sw.js] Notification click received.');

    event.notification.close();

    // Get URL from notification data
    const url = event.notification.data?.url || '/admin/dashboard';

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(function(clientList) {
            // Check if admin panel is already open
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.includes('/admin') && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }

            // Open new window/tab if admin panel not found
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
";

    return response($js)
        ->header('Content-Type', 'application/javascript')
        ->header('Service-Worker-Allowed', '/');
});

//for ads redirection
    Route::get('/payment/{payment}/success', [AdController::class, 'paymentSuccess'])
        ->name('ads.payment.success');
    Route::get('/payment/{payment}/cancel', [AdController::class, 'paymentCancel'])
        ->name('ads.payment.cancel');
Route::get('/debug/web-push-check', function () {
    return response()->json([
        'vapid_public' => config('services.vapid.public_key'),
        'vapid_configured' => !empty(config('services.vapid.public_key')),
    ]);
});






Route::post('/streams2/viewer-token', function () {
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


// Simple login & push test page
Route::get('/login-push-test', function () {
    return view('login-push-test');
});
