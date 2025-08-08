<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

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

// Test message store broadcast debugging
Route::post('/test-message-store-broadcast', function (Request $request) {
    try {
        Log::info('Testing message store broadcast');

        // Test Pusher connection first
        $pusher = new \Pusher\Pusher(
            '0e0b5123273171ff212d',  // key
            '770b5206be41b096e258',  // secret
            '1471502',  // app_id
            [
                'cluster' => 'eu',
                'useTLS' => true
            ]
        );

        // Simple test data
        $testData = [
            'test_message' => 'Message from store function test',
            'timestamp' => date('Y-m-d H:i:s'),
            'conversation_id' => $request->input('conversation_id', 1)
        ];

        $result = $pusher->trigger('private-conversation.' . $request->input('conversation_id', 1), 'message.sent', $testData);

        Log::info('Test broadcast result', ['result' => $result]);

        return response()->json([
            'status' => 'success',
            'message' => 'Test broadcast sent',
            'result' => $result
        ]);

    } catch (\Exception $e) {
        Log::error('Test broadcast failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Test call notification broadcasting
Route::post('/test-call-notification', function (Request $request) {
    try {
        Log::info('Testing call notification broadcast');

        $pusher = new \Pusher\Pusher(
            '0e0b5123273171ff212d',  // key
            '770b5206be41b096e258',  // secret
            '1471502',  // app_id
            [
                'cluster' => 'eu',
                'useTLS' => true
            ]
        );

        $callData = [
            'call_id' => 'test-call-' . time(),
            'call_type' => $request->input('call_type', 'voice'),
            'agora_channel_name' => 'test_channel_' . time(),
            'initiator' => [
                'id' => 1,
                'name' => 'Test Caller',
                'username' => 'testcaller',
                'profile_url' => null,
            ],
            'started_at' => now()->toISOString(),
        ];

        $conversationId = $request->input('conversation_id', 1);
        $channelName = 'private-conversation.' . $conversationId;

        $result = $pusher->trigger($channelName, 'call.initiated', $callData);

        Log::info('Test call notification result', ['result' => $result, 'channel' => $channelName]);

        return response()->json([
            'status' => 'success',
            'message' => 'Call notification test sent',
            'channel' => $channelName,
            'event' => 'call.initiated',
            'data' => $callData,
            'result' => $result
        ]);

    } catch (\Exception $e) {
        Log::error('Call notification test error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Call notification test failed: ' . $e->getMessage()
        ], 500);
    }
});

// Debug call finding
Route::get('/debug-call/{id}', function ($id) {
    try {
        $call = \App\Models\Call::findOrFail($id);
        return response()->json([
            'success' => true,
            'call' => [
                'id' => $call->id,
                'status' => $call->status,
                'call_type' => $call->call_type,
                'conversation_id' => $call->conversation_id,
                'initiated_by' => $call->initiated_by,
                'created_at' => $call->created_at->toISOString(),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Call not found: ' . $e->getMessage()
        ], 404);
    }
});
