<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Test Pusher broadcasting
Route::get('/test-pusher', [App\Http\Controllers\API\V1\TestPusherController::class, 'testPusher']);

// Debug Pusher
Route::get('/debug-pusher-direct', [App\Http\Controllers\API\V1\DebugPusherController::class, 'testDirectPusher']);
Route::get('/debug-pusher-sync', [App\Http\Controllers\API\V1\DebugPusherController::class, 'testBroadcastSync']);
Route::get('/debug-pusher-config', [App\Http\Controllers\API\V1\DebugPusherController::class, 'checkConfig']);

// Test message broadcasting
Route::post('/test-message-broadcast', function (Request $request) {
    try {
        // Simulate message broadcasting like in MessageController
        $pusher = new \Pusher\Pusher(
            '0e0b5123273171ff212d',  // key
            '770b5206be41b096e258',  // secret
            '1471502',  // app_id
            [
                'cluster' => 'eu',
                'useTLS' => true
            ]
        );

        $testData = [
            'message' => [
                'id' => 999,
                'message' => $request->input('message', 'Test broadcast message'),
                'user' => [
                    'id' => 1,
                    'name' => 'Test User',
                    'avatar' => null
                ],
                'type' => 'text',
                'created_at' => date('Y-m-d H:i:s'),
                'conversation_id' => $request->input('conversation_id', 1)
            ]
        ];

        $conversationId = $request->input('conversation_id', 1);
        $result = $pusher->trigger('private-conversation.' . $conversationId, 'message.sent', $testData);

        return response()->json([
            'status' => 'success',
            'message' => 'Test message broadcasted successfully',
            'data' => $testData,
            'pusher_result' => $result,
            'channel' => 'private-conversation.' . $conversationId
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to broadcast test message',
            'error' => $e->getMessage()
        ], 500);
    }
});
