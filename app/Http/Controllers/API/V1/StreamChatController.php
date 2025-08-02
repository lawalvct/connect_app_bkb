<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StreamChat;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class StreamChatController extends Controller
{
    /**
     * Send a chat message to a stream
     *
     * @param Request $request
     * @param int $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            $stream = Stream::findOrFail($streamId);
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required to send messages'
                ], 401);
            }

            // Check if stream is live
            if ($stream->status !== 'live') {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only send messages to live streams'
                ], 400);
            }

            // Check if user has paid for premium streams
            if ($stream->price > 0 && !$stream->hasUserPaid($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment required to participate in chat'
                ], 403);
            }

            // Check if user is admin/creator of the stream
            $isAdmin = ($stream->user_id == $user->id);

            // Create chat message
            $chat = StreamChat::create([
                'stream_id' => $streamId,
                'user_id' => $user->id,
                'username' => $user->name,
                'message' => $request->message,
                'user_profile_url' => $user->profile_picture ?? null,
                'is_admin' => $isAdmin
            ]);

            // Load the user relationship for the response
            $chat->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $chat
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending chat message: ' . $e->getMessage(), [
                'stream_id' => $streamId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    /**
     * Get chat messages for a stream with pagination support
     *
     * @param Request $request
     * @param int $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'before_id' => 'nullable|integer|exists:stream_chats,id',
            'after_id' => 'nullable|integer|exists:stream_chats,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            $stream = Stream::findOrFail($streamId);
            $limit = $request->input('limit', 50);
            $beforeId = $request->input('before_id');
            $afterId = $request->input('after_id');

            $query = StreamChat::where('stream_id', $streamId)
                ->with('user:id,name,profile_picture');

            // Handle pagination with before_id and after_id
            if ($beforeId) {
                // Get messages before this ID (for loading older messages)
                $query->where('id', '<', $beforeId)
                      ->orderBy('id', 'desc');
            } elseif ($afterId) {
                // Get messages after this ID (for loading newer messages)
                $query->where('id', '>', $afterId)
                      ->orderBy('id', 'asc');
            } else {
                // Get latest messages (default behavior)
                $query->orderBy('id', 'desc');
            }

            $messages = $query->limit($limit)->get();

            // If we're getting messages before an ID, reverse them to maintain chronological order
            if ($beforeId) {
                $messages = $messages->reverse()->values();
            }

            // Format messages for frontend
            $formattedMessages = $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'stream_id' => $message->stream_id,
                    'user_id' => $message->user_id,
                    'username' => $message->username,
                    'message' => $message->message,
                    'user_profile_url' => $message->user_profile_url,
                    'is_admin' => $message->is_admin,
                    'created_at' => $message->created_at->toISOString(),
                    'user' => $message->user ? [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'profile_picture' => $message->user->profile_picture
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Messages retrieved successfully',
                'data' => $formattedMessages,
                'meta' => [
                    'count' => $messages->count(),
                    'has_more' => $messages->count() === $limit,
                    'oldest_id' => $messages->isNotEmpty() ? $messages->first()->id : null,
                    'newest_id' => $messages->isNotEmpty() ? $messages->last()->id : null,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving chat messages: ' . $e->getMessage(), [
                'stream_id' => $streamId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve messages'
            ], 500);
        }
    }

    /**
     * Get chat statistics for a stream
     *
     * @param int $streamId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatStats($streamId)
    {
        try {
            $stream = Stream::findOrFail($streamId);

            // Get total message count
            $totalMessages = StreamChat::where('stream_id', $streamId)->count();

            // Get unique chatters count
            $uniqueChatters = StreamChat::where('stream_id', $streamId)
                ->distinct('user_id')
                ->count('user_id');

            // Get most active chatters (top 5)
            $mostActiveChatters = StreamChat::where('stream_id', $streamId)
                ->selectRaw('user_id, username, COUNT(*) as message_count')
                ->groupBy('user_id', 'username')
                ->orderByDesc('message_count')
                ->limit(5)
                ->get();

            // Get recent activity (messages in last hour)
            $recentActivity = StreamChat::where('stream_id', $streamId)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Chat statistics retrieved successfully',
                'data' => [
                    'total_messages' => $totalMessages,
                    'unique_chatters' => $uniqueChatters,
                    'recent_activity' => $recentActivity,
                    'most_active_chatters' => $mostActiveChatters
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving chat statistics: ' . $e->getMessage(), [
                'stream_id' => $streamId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve chat statistics'
            ], 500);
        }
    }

    /**
     * Delete a chat message (admin only)
     *
     * @param int $streamId
     * @param int $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteMessage($streamId, $messageId)
    {
        try {
            $stream = Stream::findOrFail($streamId);
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $message = StreamChat::where('stream_id', $streamId)
                ->where('id', $messageId)
                ->firstOrFail();

            // Check if user can delete this message (stream owner, message author, or admin)
            $canDelete = $stream->user_id == $user->id ||
                        $message->user_id == $user->id ||
                        $user->hasRole('admin');

            if (!$canDelete) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission denied'
                ], 403);
            }

            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting chat message: ' . $e->getMessage(), [
                'stream_id' => $streamId,
                'message_id' => $messageId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message'
            ], 500);
        }
    }
}
