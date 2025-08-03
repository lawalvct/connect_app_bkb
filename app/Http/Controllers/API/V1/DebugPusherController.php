<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use Pusher\Pusher;

class DebugPusherController extends BaseController
{
    public function testDirectPusher()
    {
        try {
            // Test direct Pusher connection
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                [
                    'cluster' => env('PUSHER_APP_CLUSTER'),
                    'useTLS' => true
                ]
            );

            $data = [
                'message' => 'Direct Pusher test message',
                'timestamp' => (string) now(),
                'test_id' => uniqid()
            ];

            $result = $pusher->trigger('test-pusher', 'test.message', $data);

            Log::info('Direct Pusher test result:', ['result' => $result]);

            return response()->json([
                'status' => 'success',
                'message' => 'Direct Pusher test sent',
                'pusher_result' => $result,
                'data_sent' => $data,
                'config' => [
                    'app_id' => env('PUSHER_APP_ID'),
                    'key' => env('PUSHER_APP_KEY'),
                    'cluster' => env('PUSHER_APP_CLUSTER')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Direct Pusher test failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Direct Pusher test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testBroadcastSync()
    {
        try {
            // Test with sync queue (immediate broadcast)
            $testMessage = new \stdClass();
            $testMessage->id = 999999;
            $testMessage->conversation_id = 1;
            $testMessage->user_id = 1;
            $testMessage->message = 'Test broadcast sync message';
            $testMessage->type = 'text';
            $testMessage->created_at = now();
            $testMessage->updated_at = now();

            // Convert to Message model for testing
            $message = new Message();
            $message->id = $testMessage->id;
            $message->conversation_id = $testMessage->conversation_id;
            $message->user_id = $testMessage->user_id;
            $message->message = $testMessage->message;
            $message->type = $testMessage->type;
            $message->created_at = $testMessage->created_at;
            $message->updated_at = $testMessage->updated_at;

            // Force sync broadcast (no queue)
            broadcast(new MessageSent($message))->toOthers();

            Log::info('Sync broadcast test completed for message:', ['message_id' => $message->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Sync broadcast test completed',
                'test_data' => $testMessage
            ]);

        } catch (\Exception $e) {
            Log::error('Sync broadcast test failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Sync broadcast test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkConfig()
    {
        $config = [
            'broadcast_driver' => config('broadcasting.default'),
            'queue_connection' => config('queue.default'),
            'pusher_config' => config('broadcasting.connections.pusher'),
            'env_values' => [
                'BROADCAST_CONNECTION' => env('BROADCAST_CONNECTION'),
                'QUEUE_CONNECTION' => env('QUEUE_CONNECTION'),
                'PUSHER_APP_ID' => env('PUSHER_APP_ID'),
                'PUSHER_APP_KEY' => env('PUSHER_APP_KEY'),
                'PUSHER_APP_CLUSTER' => env('PUSHER_APP_CLUSTER'),
            ]
        ];

        return response()->json([
            'status' => 'success',
            'config' => $config
        ]);
    }
}
