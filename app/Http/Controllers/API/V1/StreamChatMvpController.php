<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Stream;
use App\Models\StreamChat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StreamChatMvpController extends Controller
{
    /**
     * Get chat messages for MVP testing
     */
    public function index($streamId)
    {
        try {
            $stream = Stream::findOrFail($streamId);

            $query = StreamChat::where('stream_id', $streamId)
                ->orderBy('created_at', 'desc');

            // Handle pagination with before_id and after_id
            if (request('before_id')) {
                $query->where('id', '<', request('before_id'));
            }

            if (request('after_id')) {
                $query->where('id', '>', request('after_id'))
                      ->orderBy('created_at', 'asc');
            }

            $limit = min(request('limit', 20), 50); // Max 50 messages
            $messages = $query->limit($limit)->get();

            // If using after_id, we need to reverse to get chronological order
            if (request('after_id')) {
                $messages = $messages->reverse()->values();
            }

            return response()->json([
                'success' => true,
                'messages' => $messages->map(function ($chat) {
                    return [
                        'id' => $chat->id,
                        'message' => $chat->message,
                        'username' => $chat->username ?? 'Anonymous',
                        'user_profile_url' => $chat->user_profile_url,
                        'is_admin' => $chat->is_admin ?? false,
                        'created_at' => $chat->created_at->toISOString(),
                        'user' => [
                            'id' => $chat->user_id ?? 0,
                            'name' => $chat->username ?? 'Anonymous'
                        ]
                    ];
                }),
                'has_more' => $messages->count() === $limit
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new chat message for MVP testing
     */
    public function store(Request $request, $streamId)
    {
        try {
            $stream = Stream::findOrFail($streamId);

            // Basic validation
            $message = trim($request->input('message'));
            if (empty($message) || strlen($message) > 500) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message is required and must be less than 500 characters'
                ], 400);
            }

            // For MVP: use provided user info or defaults
            $userId = $request->input('user_id', 0);
            $username = $request->input('username', 'Anonymous');

            // Create chat message
            $chat = StreamChat::create([
                'stream_id' => $streamId,
                'user_id' => $userId,
                'username' => $username,
                'message' => $message,
                'user_profile_url' => null,
                'is_admin' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'id' => $chat->id,
                    'message' => $chat->message,
                    'username' => $chat->username,
                    'user_profile_url' => null,
                    'is_admin' => false,
                    'created_at' => $chat->created_at->toISOString(),
                    'user' => [
                        'id' => $userId,
                        'name' => $username
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }
}