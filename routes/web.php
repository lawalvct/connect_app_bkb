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
