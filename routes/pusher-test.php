<?php

use Illuminate\Support\Facades\Route;
use App\Helpers\PusherBroadcastHelper;

Route::get('/test-pusher', function () {
    $connectionTest = PusherBroadcastHelper::testConnection();
    $status = PusherBroadcastHelper::getStatus();

    return response()->json([
        'connection_test' => $connectionTest,
        'status' => $status,
        'timestamp' => now()->toDateTimeString()
    ]);
})->name('test.pusher');

Route::get('/reset-pusher', function () {
    PusherBroadcastHelper::resetInstance();

    return response()->json([
        'message' => 'Pusher instance and cache reset successfully',
        'status' => PusherBroadcastHelper::getStatus(),
        'timestamp' => now()->toDateTimeString()
    ]);
})->name('reset.pusher');
