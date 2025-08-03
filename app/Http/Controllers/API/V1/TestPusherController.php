<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestPusherController extends Controller
{
    /**
     * Test Pusher broadcasting
     */
    public function testPusher(Request $request)
    {
        try {
            // Test data
            $testData = [
                'message' => 'Test message from Laravel',
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $request->user()->id ?? 'anonymous'
            ];

            // Broadcast to a test channel
            broadcast(new \App\Events\TestPusherEvent($testData));

            return response()->json([
                'status' => 'success',
                'message' => 'Test broadcast sent successfully',
                'pusher_config' => [
                    'app_id' => config('broadcasting.connections.pusher.app_id'),
                    'key' => config('broadcasting.connections.pusher.key'),
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'encrypted' => config('broadcasting.connections.pusher.options.encrypted')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Pusher test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send test broadcast',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
