<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Models\Call;
use App\Models\CallParticipant;
use App\Models\Conversation;
use App\Models\Message;
use App\Helpers\AgoraHelper;
use App\Events\CallInitiated;
use App\Events\CallAnswered;
use App\Events\CallEnded;
use App\Events\CallMissed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Pusher\Pusher;

class CallController extends BaseController
{
    /**
     * Initiate a call
     */
    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:conversations,id',
            'call_type' => 'required|in:audio,video',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $conversation = Conversation::with('users')->findOrFail($request->conversation_id);

            // Check if user is part of the conversation
            if (!$conversation->users->contains($user->id)) {
                return $this->sendError('You are not a participant in this conversation', null, 403);
            }

            // Check if there's already an active call
            if ($conversation->hasActiveCall()) {
                return $this->sendError('There is already an active call in this conversation', null, 409);
            }

            DB::beginTransaction();

            // Create call
            $call = Call::create([
                'conversation_id' => $conversation->id,
                'initiated_by' => $user->id,
                'call_type' => $request->call_type,
                'status' => 'initiated',
                'agora_channel_name' => Call::generateChannelName(),
                'started_at' => now(),
            ]);

            // Get all conversation participants except the caller
            $participants = $conversation->users->where('id', '!=', $user->id);
            $allParticipantIds = $conversation->users->pluck('id')->toArray();

            // Generate Agora tokens for all participants (including caller)
            $tokens = AgoraHelper::generateTokensForUsers($call->agora_channel_name, $allParticipantIds);

            // Save tokens to call
            $call->agora_tokens = $tokens;
            $call->save();

            // Create call participant records
            foreach ($conversation->users as $participant) {
                $participantStatus = $participant->id === $user->id ? 'joined' : 'invited';
                $tokenData = $tokens[$participant->id] ?? null;

                CallParticipant::create([
                    'call_id' => $call->id,
                    'user_id' => $participant->id,
                    'status' => $participantStatus,
                    'agora_token' => $tokenData['token'] ?? null,
                    'agora_uid' => $tokenData['agora_uid'] ?? null,
                    'invited_at' => now(),
                    'joined_at' => $participant->id === $user->id ? now() : null,
                ]);
            }

            // Create call message in conversation
            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'message' => 'Call started',
                'type' => 'call_started',
                'metadata' => [
                    'call_id' => $call->id,
                    'call_type' => $call->call_type,
                ],
            ]);

            DB::commit();

            // Broadcast call initiated event using direct Pusher
            try {
                Log::info('Starting CallInitiated Pusher broadcast', [
                    'conversation_id' => $conversation->id,
                    'call_id' => $call->id,
                    'channel' => 'conversation.' . $conversation->id
                ]);

                // Get Pusher configuration
                $pusherKey = config('broadcasting.connections.pusher.key');
                $pusherSecret = config('broadcasting.connections.pusher.secret');
                $pusherAppId = config('broadcasting.connections.pusher.app_id');
                $pusherCluster = config('broadcasting.connections.pusher.options.cluster');

                // Validate Pusher configuration
                if (empty($pusherKey) || empty($pusherSecret) || empty($pusherAppId)) {
                    Log::warning('Pusher configuration missing for CallInitiated, skipping broadcast', [
                        'key_exists' => !empty($pusherKey),
                        'secret_exists' => !empty($pusherSecret),
                        'app_id_exists' => !empty($pusherAppId),
                        'cluster' => $pusherCluster
                    ]);
                } else {
                    // Create direct Pusher instance
                    $pusher = new \Pusher\Pusher(
                        $pusherKey,
                        $pusherSecret,
                        $pusherAppId,
                        [
                            'cluster' => $pusherCluster ?: 'eu',
                            'useTLS' => true
                        ]
                    );

                    Log::info('CallInitiated Pusher instance created successfully');

                    // Prepare participants data with full profile URLs
                    $participantsData = $conversation->users->map(function ($participant) use ($user) {
                        return [
                            'id' => $participant->id,
                            'name' => $participant->name,
                            'username' => $participant->username,
                            'profile_image' => $participant->profile ? $participant->profile_url : null,
                            'avatar_url' => $participant->avatar_url,
                            'status' => $participant->id === $user->id ? 'joined' : 'invited'
                        ];
                    })->toArray();

                    // Create broadcast data
                    $broadcastData = [
                        'call_id' => $call->id,
                        'call_type' => $call->call_type,
                        'agora_channel_name' => $call->agora_channel_name,
                        'initiator' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'username' => $user->username,
                            'profile_image' => $user->profile ? $user->profile_url : null,
                            'avatar_url' => $user->avatar_url
                        ],
                        'conversation' => [
                            'id' => $conversation->id,
                            'type' => $conversation->type
                        ],
                        'participants' => $participantsData,
                        'started_at' => $call->started_at->toISOString()
                    ];

                    Log::info('CallInitiated broadcast data prepared', [
                        'data_structure' => array_keys($broadcastData)
                    ]);

                    $result = $pusher->trigger('private-conversation.' . $conversation->id, 'call.initiated', $broadcastData);

                    Log::info('CallInitiated broadcast successful', [
                        'call_id' => $call->id,
                        'conversation_id' => $conversation->id,
                        'channel' => 'private-conversation.' . $conversation->id,
                        'pusher_result' => $result
                    ]);
                }
            } catch (\Exception $broadcastException) {
                Log::error('Failed to broadcast CallInitiated via direct Pusher', [
                    'call_id' => $call->id ?? 'not_available',
                    'conversation_id' => $conversation->id ?? 'not_available',
                    'error_message' => $broadcastException->getMessage(),
                    'error_code' => $broadcastException->getCode(),
                    'error_file' => $broadcastException->getFile(),
                    'error_line' => $broadcastException->getLine()
                ]);
                // Don't fail the request if broadcast fails
            }

            return $this->sendResponse('Call initiated successfully', [
                'call' => $this->formatCallData($call->fresh(['participants.user', 'initiator'])),
                'agora_config' => [
                    'app_id' => AgoraHelper::getAppId(),
                    'channel_name' => $call->agora_channel_name,
                    'token' => $tokens[$user->id]['token'],
                    'uid' => $tokens[$user->id]['agora_uid'],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to initiate call', $e->getMessage(), 500);
        }
    }

    /**
     * Answer a call
     */
    public function answer(Request $request, Call $call)
    {
        try {
            $user = $request->user();
            $call->load(['participants.user', 'conversation.users']);

            // Check if user is a participant
            $participant = $call->participants->where('user_id', $user->id)->first();
            if (!$participant) {
                return $this->sendError('You are not a participant in this call', null, 403);
            }

            // Check if call is still active
            if (!$call->isActive()) {
                return $this->sendError('Call is no longer active', null, 409);
            }

            DB::beginTransaction();

            // Update participant status
            $participant->update([
                'status' => 'joined',
                'joined_at' => now(),
            ]);

            // Update call status to connected if this is the first answer
            if ($call->status === 'initiated') {
                $call->update([
                    'status' => 'connected',
                    'connected_at' => now(),
                ]);
            }

            DB::commit();

            // Broadcast call answered event using direct Pusher
            try {
                Log::info('Starting CallAnswered Pusher broadcast', [
                    'conversation_id' => $call->conversation_id,
                    'call_id' => $call->id,
                    'channel' => 'private-conversation.' . $call->conversation_id
                ]);

                // Get Pusher configuration
                $pusherKey = config('broadcasting.connections.pusher.key');
                $pusherSecret = config('broadcasting.connections.pusher.secret');
                $pusherAppId = config('broadcasting.connections.pusher.app_id');
                $pusherCluster = config('broadcasting.connections.pusher.options.cluster');

                // Validate Pusher configuration
                if (empty($pusherKey) || empty($pusherSecret) || empty($pusherAppId)) {
                    Log::warning('Pusher configuration missing for CallAnswered, skipping broadcast', [
                        'key_exists' => !empty($pusherKey),
                        'secret_exists' => !empty($pusherSecret),
                        'app_id_exists' => !empty($pusherAppId),
                        'cluster' => $pusherCluster
                    ]);
                } else {
                    // Create direct Pusher instance
                    $pusher = new \Pusher\Pusher(
                        $pusherKey,
                        $pusherSecret,
                        $pusherAppId,
                        [
                            'cluster' => $pusherCluster ?: 'eu',
                            'useTLS' => true
                        ]
                    );

                    Log::info('CallAnswered Pusher instance created successfully');

                    // Prepare participants data with full profile URLs
                    $participantsData = $call->conversation->users->map(function ($participant) use ($call) {
                        $callParticipant = $call->participants->where('user_id', $participant->id)->first();
                        return [
                            'id' => $participant->id,
                            'name' => $participant->name,
                            'username' => $participant->username,
                            'profile_image' => $participant->profile ? $participant->profile_url : null,
                            'avatar_url' => $participant->avatar_url,
                            'status' => $callParticipant ? $callParticipant->status : 'invited'
                        ];
                    })->toArray();

                    // Create broadcast data
                    $broadcastData = [
                        'call_id' => $call->id,
                        'call_type' => $call->call_type,
                        'agora_channel_name' => $call->agora_channel_name,
                        'answerer' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'username' => $user->username,
                            'profile_image' => $user->profile ? $user->profile_url : null,
                            'avatar_url' => $user->avatar_url
                        ],
                        'participants' => $participantsData,
                        'status' => $call->status,
                        'connected_at' => $call->connected_at ? $call->connected_at->toISOString() : null
                    ];

                    Log::info('CallAnswered broadcast data prepared', [
                        'data_structure' => array_keys($broadcastData)
                    ]);

                    $result = $pusher->trigger('private-conversation.' . $call->conversation_id, 'call.answered', $broadcastData);

                    Log::info('CallAnswered broadcast successful', [
                        'call_id' => $call->id,
                        'conversation_id' => $call->conversation_id,
                        'channel' => 'private-conversation.' . $call->conversation_id,
                        'pusher_result' => $result
                    ]);
                }
            } catch (\Exception $broadcastException) {
                Log::error('Failed to broadcast CallAnswered via direct Pusher', [
                    'call_id' => $call->id ?? 'not_available',
                    'conversation_id' => $call->conversation_id ?? 'not_available',
                    'error_message' => $broadcastException->getMessage(),
                    'error_code' => $broadcastException->getCode(),
                    'error_file' => $broadcastException->getFile(),
                    'error_line' => $broadcastException->getLine()
                ]);
                // Don't fail the request if broadcast fails
            }

            return $this->sendResponse('Call answered successfully', [
                'call' => $this->formatCallData($call->fresh(['participants.user', 'initiator'])),
                'agora_config' => [
                    'app_id' => AgoraHelper::getAppId(),
                    'channel_name' => $call->agora_channel_name,
                    'token' => $participant->agora_token,
                    'uid' => $participant->agora_uid,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to answer call', $e->getMessage(), 500);
        }
    }

    /**
     * End a call
     */
    public function end(Request $request, Call $call)
    {

        try {
            $user = $request->user();
            $call->load(['participants.user', 'conversation.users']);

            // Check if user is a participant
            $participant = $call->participants->where('user_id', $user->id)->first();
            if (!$participant) {
                return $this->sendError('You are not a participant in this call', null, 403);
            }

            DB::beginTransaction();

            $now = now();

            // Update participant
            if ($participant->status === 'joined' && !$participant->left_at) {
                $participant->update([
                    'status' => 'left',
                    'left_at' => $now,
                ]);
                $participant->updateDuration();
            }

            // End the call if initiated by caller or if all participants left
            $activeParticipants = $call->participants()->where('status', 'joined')->count();

            if ($call->initiated_by === $user->id || $activeParticipants <= 1) {
                $call->update([
                    'status' => 'ended',
                    'ended_at' => $now,
                    'end_reason' => 'ended_by_caller',
                ]);

                // Update all remaining participants
                $call->participants()->where('status', 'joined')->update([
                    'status' => 'left',
                    'left_at' => $now,
                ]);

                $call->updateDuration();

                // Create call ended message
                Message::create([
                    'conversation_id' => $call->conversation_id,
                    'user_id' => $user->id,
                    'message' => 'Call ended',
                    'type' => 'call_ended',
                    'metadata' => [
                        'call_id' => $call->id,
                        'call_type' => $call->call_type,
                        'duration' => $call->duration,
                        'formatted_duration' => $call->formatted_duration,
                    ],
                ]);
            }

            DB::commit();

            // Broadcast call ended event using direct Pusher
            try {
                Log::info('Starting CallEnded Pusher broadcast', [
                    'conversation_id' => $call->conversation_id,
                    'call_id' => $call->id,
                    'channel' => 'private-conversation.' . $call->conversation_id
                ]);

                // Get Pusher configuration
                $pusherKey = config('broadcasting.connections.pusher.key');
                $pusherSecret = config('broadcasting.connections.pusher.secret');
                $pusherAppId = config('broadcasting.connections.pusher.app_id');
                $pusherCluster = config('broadcasting.connections.pusher.options.cluster');

                // Validate Pusher configuration
                if (empty($pusherKey) || empty($pusherSecret) || empty($pusherAppId)) {
                    Log::warning('Pusher configuration missing for CallEnded, skipping broadcast', [
                        'key_exists' => !empty($pusherKey),
                        'secret_exists' => !empty($pusherSecret),
                        'app_id_exists' => !empty($pusherAppId),
                        'cluster' => $pusherCluster
                    ]);
                } else {
                    // Create direct Pusher instance
                    $pusher = new \Pusher\Pusher(
                        $pusherKey,
                        $pusherSecret,
                        $pusherAppId,
                        [
                            'cluster' => $pusherCluster ?: 'eu',
                            'useTLS' => true
                        ]
                    );

                    Log::info('CallEnded Pusher instance created successfully');

                    // Prepare participants data with full profile URLs
                    $participantsData = $call->conversation->users->map(function ($participant) use ($call) {
                        $callParticipant = $call->participants->where('user_id', $participant->id)->first();
                        return [
                            'id' => $participant->id,
                            'name' => $participant->name,
                            'username' => $participant->username,
                            'profile_image' => $participant->profile ? $participant->profile_url : null,
                            'avatar_url' => $participant->avatar_url,
                            'status' => $callParticipant ? $callParticipant->status : 'left'
                        ];
                    })->toArray();

                    // Create broadcast data
                    $broadcastData = [
                        'call_id' => $call->id,
                        'call_type' => $call->call_type,
                        'ended_by' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'username' => $user->username,
                            'profile_image' => $user->profile ? $user->profile_url : null,
                            'avatar_url' => $user->avatar_url
                        ],
                        'participants' => $participantsData,
                        'status' => $call->status,
                        'end_reason' => $call->end_reason,
                        'duration' => $call->duration,
                        'formatted_duration' => $call->formatted_duration,
                        'ended_at' => $call->ended_at ? $call->ended_at->toISOString() : null
                    ];

                    Log::info('CallEnded broadcast data prepared', [
                        'data_structure' => array_keys($broadcastData)
                    ]);

                    $result = $pusher->trigger('private-conversation.' . $call->conversation_id, 'call.ended', $broadcastData);

                    Log::info('CallEnded broadcast successful', [
                        'call_id' => $call->id,
                        'conversation_id' => $call->conversation_id,
                        'channel' => 'private-conversation.' . $call->conversation_id,
                        'pusher_result' => $result
                    ]);
                }
            } catch (\Exception $broadcastException) {
                Log::error('Failed to broadcast CallEnded via direct Pusher', [
                    'call_id' => $call->id ?? 'not_available',
                    'conversation_id' => $call->conversation_id ?? 'not_available',
                    'error_message' => $broadcastException->getMessage(),
                    'error_code' => $broadcastException->getCode(),
                    'error_file' => $broadcastException->getFile(),
                    'error_line' => $broadcastException->getLine()
                ]);
                // Don't fail the request if broadcast fails
            }

            return $this->sendResponse('Call ended successfully', [
                'call' => $this->formatCallData($call->fresh(['participants.user', 'initiator'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to end call', $e->getMessage(), 500);
        }
    }

    /**
     * Reject a call
     */
    public function reject(Request $request, Call $call)
    {
        try {
            $user = $request->user();
            $call->load(['participants.user', 'conversation.users']);

            // Check if user is a participant
            $participant = $call->participants->where('user_id', $user->id)->first();
            if (!$participant) {
                return $this->sendError('You are not a participant in this call', null, 403);
            }

            // Check if call can be rejected
            if (!in_array($call->status, ['initiated', 'ringing'])) {
                return $this->sendError('Call cannot be rejected at this time', null, 409);
            }

            DB::beginTransaction();

            // Update participant status
            $participant->update([
                'status' => 'rejected',
                'left_at' => now(),
            ]);

            // Check if all participants rejected (for group calls)
            $activeParticipants = $call->participants()
                ->whereNotIn('status', ['rejected', 'missed'])
                ->where('user_id', '!=', $call->initiated_by)
                ->count();

            if ($activeParticipants === 0) {
                $call->update([
                    'status' => 'missed',
                    'ended_at' => now(),
                    'end_reason' => 'rejected',
                ]);

                // Create missed call message
                Message::create([
                    'conversation_id' => $call->conversation_id,
                    'user_id' => $call->initiated_by,
                    'message' => 'Missed call',
                    'type' => 'call_missed',
                    'metadata' => [
                        'call_id' => $call->id,
                        'call_type' => $call->call_type,
                    ],
                ]);

                // Broadcast call missed event using direct Pusher
                try {
                    Log::info('Starting CallMissed Pusher broadcast', [
                        'conversation_id' => $call->conversation_id,
                        'call_id' => $call->id,
                        'channel' => 'private-conversation.' . $call->conversation_id
                    ]);

                    // Get Pusher configuration
                    $pusherKey = config('broadcasting.connections.pusher.key');
                    $pusherSecret = config('broadcasting.connections.pusher.secret');
                    $pusherAppId = config('broadcasting.connections.pusher.app_id');
                    $pusherCluster = config('broadcasting.connections.pusher.options.cluster');

                    // Validate Pusher configuration
                    if (empty($pusherKey) || empty($pusherSecret) || empty($pusherAppId)) {
                        Log::warning('Pusher configuration missing for CallMissed, skipping broadcast', [
                            'key_exists' => !empty($pusherKey),
                            'secret_exists' => !empty($pusherSecret),
                            'app_id_exists' => !empty($pusherAppId),
                            'cluster' => $pusherCluster
                        ]);
                    } else {
                        // Create direct Pusher instance
                        $pusher = new \Pusher\Pusher(
                            $pusherKey,
                            $pusherSecret,
                            $pusherAppId,
                            [
                                'cluster' => $pusherCluster ?: 'eu',
                                'useTLS' => true
                            ]
                        );

                        Log::info('CallMissed Pusher instance created successfully');

                        // Prepare participants data with full profile URLs
                        $participantsData = $call->conversation->users->map(function ($participant) use ($call) {
                            $callParticipant = $call->participants->where('user_id', $participant->id)->first();
                            return [
                                'id' => $participant->id,
                                'name' => $participant->name,
                                'username' => $participant->username,
                                'profile_image' => $participant->profile ? $participant->profile_url : null,
                                'avatar_url' => $participant->avatar_url,
                                'status' => $callParticipant ? $callParticipant->status : 'missed'
                            ];
                        })->toArray();

                        // Create broadcast data
                        $broadcastData = [
                            'call_id' => $call->id,
                            'call_type' => $call->call_type,
                            'participants' => $participantsData,
                            'status' => $call->status,
                            'end_reason' => $call->end_reason,
                            'ended_at' => $call->ended_at ? $call->ended_at->toISOString() : null
                        ];

                        Log::info('CallMissed broadcast data prepared', [
                            'data_structure' => array_keys($broadcastData)
                        ]);

                        $result = $pusher->trigger('private-conversation.' . $call->conversation_id, 'call.missed', $broadcastData);

                        Log::info('CallMissed broadcast successful', [
                            'call_id' => $call->id,
                            'conversation_id' => $call->conversation_id,
                            'channel' => 'private-conversation.' . $call->conversation_id,
                            'pusher_result' => $result
                        ]);
                    }
                } catch (\Exception $broadcastException) {
                    Log::error('Failed to broadcast CallMissed via direct Pusher', [
                        'call_id' => $call->id ?? 'not_available',
                        'conversation_id' => $call->conversation_id ?? 'not_available',
                        'error_message' => $broadcastException->getMessage(),
                        'error_code' => $broadcastException->getCode(),
                        'error_file' => $broadcastException->getFile(),
                        'error_line' => $broadcastException->getLine()
                    ]);
                    // Don't fail the request if broadcast fails
                }
            }

            DB::commit();

            return $this->sendResponse('Call rejected successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to reject call', $e->getMessage(), 500);
        }
    }

    /**
     * Get call history for a conversation
     */
    public function history(Request $request, $conversationId)
    {
        try {
            $user = $request->user();
            $conversation = Conversation::findOrFail($conversationId);

            // Check if user is part of the conversation
            if (!$conversation->users->contains($user->id)) {
                return $this->sendError('You are not a participant in this conversation', null, 403);
            }

            $calls = $conversation->calls()
                ->with(['participants.user', 'initiator'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return $this->sendResponse('Call history retrieved successfully', [
                'calls' => $calls->items(),
                'pagination' => [
                    'current_page' => $calls->currentPage(),
                    'total_pages' => $calls->lastPage(),
                    'total_items' => $calls->total(),
                    'per_page' => $calls->perPage(),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve call history', $e->getMessage(), 500);
        }
    }

    /**
     * Get user's recent calls
     */
    public function recentCalls(Request $request)
    {
        try {
            $user = $request->user();

            $calls = Call::whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['participants.user', 'initiator', 'conversation'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

            return $this->sendResponse('Recent calls retrieved successfully', [
                'calls' => $calls->items(),
                'pagination' => [
                    'current_page' => $calls->currentPage(),
                    'total_pages' => $calls->lastPage(),
                    'total_items' => $calls->total(),
                    'per_page' => $calls->perPage(),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve recent calls', $e->getMessage(), 500);
        }
    }

    /**
     * Format call data for API response
     */
    private function formatCallData(Call $call): array
    {
        return [
            'id' => $call->id,
            'conversation_id' => $call->conversation_id,
            'call_type' => $call->call_type,
            'status' => $call->status,
            'duration' => $call->duration,
            'formatted_duration' => $call->formatted_duration,
            'started_at' => $call->started_at?->toISOString(),
            'connected_at' => $call->connected_at?->toISOString(),
            'ended_at' => $call->ended_at?->toISOString(),
            'end_reason' => $call->end_reason,
            'initiator' => [
                'id' => $call->initiator->id,
                'name' => $call->initiator->name,
                'username' => $call->initiator->username,
                'profile_url' => $call->initiator->profile_url,
            ],
            'participants' => $call->participants->map(function ($participant) {
                return [
                    'user_id' => $participant->user_id,
                    'name' => $participant->user->name,
                    'username' => $participant->user->username,
                    'profile_url' => $participant->user->profile_url,
                    'status' => $participant->status,
                    'joined_at' => $participant->joined_at?->toISOString(),
                    'left_at' => $participant->left_at?->toISOString(),
                    'duration' => $participant->duration,
                ];
            }),
        ];
    }



    public function getUserCallHistory(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 20);

            $calls = Call::whereHas('participants', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with([
                    'conversation:id,name',
                    'initiator:id,name,username,profile,profile_url',
                    'participants.user:id,name,username,profile,profile_url'
                ])
                ->orderBy('started_at', 'desc')
                ->paginate($perPage);

            $formattedCalls = $calls->map(function ($call) use ($user) {
                $userParticipant = $call->participants->where('user_id', $user->id)->first();

                return [
                    'id' => $call->id,
                    'call_type' => $call->call_type,
                    'status' => $call->status,
                    'duration' => $call->duration,
                    'started_at' => $call->started_at?->toISOString(),
                    'ended_at' => $call->ended_at?->toISOString(),
                    'conversation' => [
                        'id' => $call->conversation->id,
                        'name' => $call->conversation->name ?? 'Unknown'
                    ],
                    'initiator' => [
                        'id' => $call->initiator->id,
                        'name' => $call->initiator->name,
                        'username' => $call->initiator->username,
                        'profile_url' => $call->initiator->profile_url,
                    ],
                    'user_status' => $userParticipant?->status ?? 'not_joined',
                    'participants_count' => $call->participants->count(),
                    'is_missed' => $call->status === 'missed' ||
                                  ($userParticipant && $userParticipant->status === 'invited' && $call->status === 'ended')
                ];
            });

            return $this->sendResponse('Call history retrieved successfully', [
                'calls' => $formattedCalls,
                'pagination' => [
                    'current_page' => $calls->currentPage(),
                    'last_page' => $calls->lastPage(),
                    'per_page' => $calls->perPage(),
                    'total' => $calls->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user call history', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Failed to retrieve call history', $e->getMessage(), 500);
        }
    }

    /**
     * Get call history for a specific conversation
     */
    public function getConversationCallHistory(Request $request, Conversation $conversation)
    {
        try {
            $user = $request->user();

            // Check if user is part of the conversation
            if (!$conversation->users->contains($user->id)) {
                return $this->sendError('You are not a participant in this conversation', null, 403);
            }

            $perPage = $request->get('per_page', 20);

            $calls = Call::where('conversation_id', $conversation->id)
                ->with([
                    'initiator:id,name,username,profile,profile_url',
                    'participants.user:id,name,username,profile,profile_url'
                ])
                ->orderBy('started_at', 'desc')
                ->paginate($perPage);

            $formattedCalls = $calls->map(function ($call) use ($user) {
                return $this->formatCallData($call, $user);
            });

            return $this->sendResponse('Conversation call history retrieved successfully', [
                'calls' => $formattedCalls,
                'pagination' => [
                    'current_page' => $calls->currentPage(),
                    'last_page' => $calls->lastPage(),
                    'per_page' => $calls->perPage(),
                    'total' => $calls->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get conversation call history', [
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return $this->sendError('Failed to retrieve conversation call history', $e->getMessage(), 500);
        }
    }

    /**
     * Get call participants
     */
    public function getCallParticipants(Request $request, Call $call)
    {
        try {
            $user = $request->user();

            // Check if user is part of the call
            $userParticipant = $call->participants()->where('user_id', $user->id)->first();
            if (!$userParticipant) {
                return $this->sendError('You are not a participant in this call', null, 403);
            }

            $participants = $call->participants()
                ->with('user:id,name,username,profile,profile_url')
                ->get()
                ->map(function ($participant) {
                    return [
                        'id' => $participant->id,
                        'user' => [
                            'id' => $participant->user->id,
                            'name' => $participant->user->name,
                            'username' => $participant->user->username,
                            'profile_url' => $participant->user->profile_url,
                        ],
                        'status' => $participant->status,
                        'agora_uid' => $participant->agora_uid,
                        'joined_at' => $participant->joined_at?->toISOString(),
                        'left_at' => $participant->left_at?->toISOString(),
                    ];
                });

            return $this->sendResponse('Call participants retrieved successfully', [
                'participants' => $participants
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get call participants', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);

            return $this->sendError('Failed to retrieve call participants', $e->getMessage(), 500);
        }
    }

    /**
     * Kick participant from call (for group calls)
     */
    public function kickParticipant(Request $request, Call $call, $userId)
    {
        try {
            $user = $request->user();

            // Check if user is the call initiator
            if ($call->initiated_by !== $user->id) {
                return $this->sendError('Only the call initiator can kick participants', null, 403);
            }

            // Check if call is active
            if (!in_array($call->status, ['initiated', 'connected'])) {
                return $this->sendError('Cannot kick participants from inactive call', null, 400);
            }

            $participant = CallParticipant::where('call_id', $call->id)
                ->where('user_id', $userId)
                ->first();

            if (!$participant) {
                return $this->sendError('Participant not found in this call', null, 404);
            }

            if ($participant->user_id === $user->id) {
                return $this->sendError('You cannot kick yourself from the call', null, 400);
            }

            // Update participant status
            $participant->update([
                'status' => 'kicked',
                'left_at' => now()
            ]);

            // Broadcast participant kicked event
            // broadcast(new ParticipantKicked($call, $participant))->toOthers();

            return $this->sendResponse('Participant kicked successfully', [
                'participant' => [
                    'user_id' => $participant->user_id,
                    'status' => $participant->status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to kick participant', [
                'call_id' => $call->id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return $this->sendError('Failed to kick participant', $e->getMessage(), 500);
        }
    }

    /**
     * Format call data for response
     */

}
