<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\AgoraHelper;
use App\Http\Controllers\API\BaseController;
use App\Models\Stream;
use App\Models\StreamChat;
use App\Models\StreamViewer;
use App\Models\StreamInteraction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class StreamController extends BaseController
{
    /**
     * Create/Schedule a new stream (Admin only)
     */
    public function store(Request $request)
    {
        try {
            // Check if user has admin role
            // if (!$request->user()->hasRole('admin')) {
            //     return $this->sendError('Unauthorized. Only admins can create streams.', null, 403);
            // }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:51200', // 50MB max
                'scheduled_at' => 'nullable|date|after:now',
                'is_paid' => 'boolean',
                'price' => 'required_if:is_paid,true|numeric|min:0',
                'currency' => 'string|in:USD,NGN|max:3',
                'max_viewers' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $data = $validator->validated();
            $data['user_id'] = $request->user()->id;
            $data['channel_name'] = 'stream_' . time() . '_' . Str::random(8);
            $data['currency'] = $data['currency'] ?? 'USD';

            // Handle banner image upload
            if ($request->hasFile('banner_image')) {
                $path = $request->file('banner_image')->store('stream-banners', 'public');
                $data['banner_image'] = $path;
                $data['banner_image_url'] = Storage::url($path);
            }

            $stream = Stream::create($data);

            return $this->sendResponse('Stream created successfully', [
                'stream' => $this->formatStreamResponse($stream->load('user'))
            ], 201);

        } catch (\Exception $e) {
            return $this->sendError('Failed to create stream', $e->getMessage(), 500);
        }
    }

    /**
     * Start a stream (Admin only)
     */
    public function start(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            // Check if user is the stream owner
            if ($stream->user_id !== $request->user()->id) {
                return $this->sendError('Unauthorized. You can only start your own streams.', null, 403);
            }

            if ($stream->status !== 'upcoming') {
                return $this->sendError('Stream cannot be started. Current status: ' . $stream->status, null, 400);
            }

            // Generate Agora token for the streamer
            $agoraUid = StreamViewer::generateAgoraUid();
            $agoraToken = AgoraHelper::generateRtcToken($stream->channel_name, (int)$agoraUid, 7200, 'publisher'); // 2 hours

            if (!$agoraToken) {
                return $this->sendError('Failed to generate streaming token', null, 500);
            }

            $stream->start();

            return $this->sendResponse('Stream started successfully', [
                'stream' => $this->formatStreamResponse($stream->load('user')),
                'agora_config' => [
                    'app_id' => AgoraHelper::getAppId(),
                    'channel_name' => $stream->channel_name,
                    'agora_uid' => $agoraUid,
                    'token' => $agoraToken,
                    'role' => 'publisher'
                ]
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to start stream', $e->getMessage(), 500);
        }
    }

    /**
     * End a stream (Admin only)
     */
    public function end(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            // Check if user is the stream owner
            if ($stream->user_id !== $request->user()->id) {
                return $this->sendError('Unauthorized. You can only end your own streams.', null, 403);
            }

            if ($stream->status !== 'live') {
                return $this->sendError('Stream is not currently live', null, 400);
            }

            $stream->end();

            return $this->sendResponse('Stream ended successfully', [
                'stream' => $this->formatStreamResponse($stream->load('user'))
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to end stream', $e->getMessage(), 500);
        }
    }

    /**
     * Join a stream (Any authenticated user)
     */
    public function join(Request $request, $id)
    {
        try {
            $stream = Stream::with('user')->find($id);
            $user = $request->user();

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            if (!$stream->canUserJoin($user)) {
                if ($stream->status !== 'live') {
                    return $this->sendError('Stream is not currently live', null, 400);
                }
                if ($stream->is_paid && !$stream->hasUserPaid($user)) {
                    return $this->sendError('Payment required to join this stream', null, 402);
                }
            }

            // Check if user has exceeded free minutes
            if ($stream->is_paid && $stream->hasUserExceededFreeMinutes($user)) {
                return $this->sendError('Your free viewing time has expired. Please make a payment to continue watching.', [
                    'free_minutes_expired' => true,
                    'free_minutes' => $stream->free_minutes,
                    'requires_payment' => true
                ], 402);
            }

            // Generate Agora token for viewer
            $agoraUid = StreamViewer::generateAgoraUid();
            $agoraToken = AgoraHelper::generateRtcToken($stream->channel_name, (int)$agoraUid, 3600, 'subscriber'); // 1 hour

            if (!$agoraToken) {
                return $this->sendError('Failed to generate viewing token', null, 500);
            }

            // Add user as viewer
            $viewer = $stream->addViewer($user, $agoraUid, $agoraToken);

            // Calculate remaining free minutes if applicable
            $freeMinutesInfo = null;
            if ($stream->is_paid && $stream->free_minutes > 0 && !$stream->hasUserPaid($user)) {
                $firstViewer = $stream->viewers()->where('user_id', $user->id)->orderBy('joined_at', 'asc')->first();
                $minutesWatched = $firstViewer && $firstViewer->joined_at ? $firstViewer->joined_at->diffInMinutes(now()) : 0;
                $remainingMinutes = max(0, $stream->free_minutes - $minutesWatched);

                $freeMinutesInfo = [
                    'total_free_minutes' => $stream->free_minutes,
                    'minutes_watched' => $minutesWatched,
                    'remaining_minutes' => $remainingMinutes,
                    'has_paid' => false,
                    'requires_payment_after_free_period' => true
                ];
            }

            $response = [
                'stream' => $this->formatStreamResponse($stream, $user),
                'agora_config' => [
                    'app_id' => AgoraHelper::getAppId(),
                    'channel_name' => $stream->channel_name,
                    'agora_uid' => $agoraUid,
                    'token' => $agoraToken,
                    'role' => 'subscriber'
                ],
                'viewer' => [
                    'id' => $viewer->id,
                    'joined_at' => $viewer->joined_at
                ]
            ];

            if ($freeMinutesInfo) {
                $response['free_minutes_info'] = $freeMinutesInfo;
            }

            return $this->sendResponse('Joined stream successfully', $response);

        } catch (\Exception $e) {
            return $this->sendError('Failed to join stream', $e->getMessage(), 500);
        }
    }

    /**
     * Leave a stream
     */
    public function leave(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);
            $user = $request->user();

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            $left = $stream->removeViewer($user);

            if (!$left) {
                return $this->sendError('You are not currently viewing this stream', null, 400);
            }

            return $this->sendResponse('Left stream successfully', [
                'stream_id' => $stream->id
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to leave stream', $e->getMessage(), 500);
        }
    }

    /**
     * Get stream details
     */
    public function show(Request $request, $id)
    {

        try {
            $stream = Stream::with(['user', 'activeViewers.user'])->find($id);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            return $this->sendResponse('Stream details retrieved successfully', [
                'stream' => $this->formatStreamResponse($stream, $request->user())
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve stream details', $e->getMessage(), 500);
        }
    }

    /**
     * Check stream status
     */
    public function status(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            return $this->sendResponse('Stream status retrieved successfully', [
                'stream_id' => $stream->id,
                'status' => $stream->status,
                'is_live' => $stream->is_live,
                'current_viewers' => $stream->current_viewers,
                'started_at' => $stream->started_at,
                'ended_at' => $stream->ended_at,
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve stream status', $e->getMessage(), 500);
        }
    }

    /**
     * Get latest live streams
     */
    public function latest(Request $request)
    {
        try {
            $query = Stream::with(['user'])

                ->where('status', 'live')
                ->orderBy('created_at', 'desc');

            // Make sure to select all necessary fields including channel_name
            $streams = $query->select([
                'id',
                'title',
                'description',
                'banner_image_url',
                'status',
                'user_id',
                'channel_name',  // Make sure this is included
                'created_at',
                'updated_at'
            ])->paginate(1);

            // Transform the data to ensure channel_name is available
            $transformedStreams = $streams->map(function ($stream) {
                $streamData = $stream->toArray();

                // Ensure channel_name exists, generate if missing
                if (empty($streamData['channel_name'])) {
                    $streamData['channel_name'] = $stream->channel_name ?? "stream_{$stream->id}_" . time();
                }

                // Add streamer info
                $streamData['streamer'] = [
                    'id' => $stream->user->id ?? null,
                    'name' => $stream->user->name ?? null,
                    'username' => $stream->user->username ?? null,
                ];

                return $streamData;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'streams' => $transformedStreams,
                    'total' => $transformedStreams->count()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching latest streams: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch streams'
            ], 500);
        }
    }

    /**
     * Get upcoming streams
     */
    public function upcoming(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $streams = Stream::with('user')
                ->upcoming()
                ->where('scheduled_at', '>', now())
                ->orderBy('scheduled_at', 'asc')
                ->limit($limit)
                ->get();

            return $this->sendResponse('Upcoming streams retrieved successfully', [
                'streams' => $streams->map(function ($stream) {
                    return $this->formatStreamResponse($stream);
                })
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve upcoming streams', $e->getMessage(), 500);
        }
    }

    /**
     * Get stream viewers
     */
    public function viewers(Request $request, $id)
    {

        try {
            $stream = Stream::find($id);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            $viewers = $stream->activeViewers()
                ->with('user:id,username,name,profile,profile_url')
                ->orderBy('joined_at', 'desc')
                ->get();

            return $this->sendResponse('Stream viewers retrieved successfully', [
                'stream_id' => $stream->id,
                'total_viewers' => $viewers->count(),
                'viewers' => $viewers->map(function ($viewer) {
                    return [
                        'id' => $viewer->id,
                        'user' => [
                            'id' => $viewer->user->id,
                            'username' => $viewer->user->username,
                            'name' => $viewer->user->name,
                            'profile_picture' => $viewer->user->profile_url.'/'.$viewer->user->profile,
                        ],
                        'joined_at' => $viewer->joined_at,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve stream viewers', $e->getMessage(), 500);
        }
    }

    /**
     * Get stream chat messages
     */
    public function getChat(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            $limit = $request->get('limit', 50);
            $afterId = $request->get('after_id');
            $beforeId = $request->get('before_id');

            $query = $stream->chats();

            if ($afterId) {
                $query->after($afterId);
            } elseif ($beforeId) {
                $query->before($beforeId);
            }

            $messages = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->reverse()
                ->values();

            return $this->sendResponse('Stream chat retrieved successfully', [
                'stream_id' => $stream->id,
                'messages' => $messages,
                'has_more' => $messages->count() >= $limit,
                'last_message_id' => $messages->last()?->id,
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve stream chat', $e->getMessage(), 500);
        }
    }

    /**
     * Send chat message
     */
    public function sendChat(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);
            $user = $request->user();

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            if ($stream->status !== 'live') {
                return $this->sendError('Cannot send message to non-live stream', null, 400);
            }

            // Check if user is viewing the stream or is the stream owner
            $isViewer = $stream->activeViewers()->where('user_id', $user->id)->exists();
            $isOwner = $stream->user_id === $user->id;

            if (!$isViewer && !$isOwner) {
                return $this->sendError('You must be viewing the stream to send messages', null, 403);
            }

            // Check if user has exceeded free minutes for paid streams
            if ($stream->is_paid && !$isOwner && $stream->hasUserExceededFreeMinutes($user)) {
                return $this->sendError('Your free viewing time has expired. Please make a payment to continue participating in chat.', [
                    'free_minutes_expired' => true,
                    'free_minutes' => $stream->free_minutes,
                    'requires_payment' => true
                ], 402);
            }

            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $message = StreamChat::create([
                'stream_id' => $stream->id,
                'user_id' => $user->id,
                'username' => $user->username,
                'message' => $request->message,
                'user_profile_url' => $user->profile_picture,
                'is_admin' => $user->hasRole('admin'),
            ]);

            return $this->sendResponse('Message sent successfully', [
                'message' => $message
            ], 201);

        } catch (\Exception $e) {
            return $this->sendError('Failed to send message', $e->getMessage(), 500);
        }
    }

    /**
     * Get user's streams (for admins)
     */
    public function myStreams(Request $request)
    {

        try {
            // if (!$request->user()->hasRole('admin')) {
            //     return $this->sendError('Unauthorized. Only admins can view their streams.', null, 403);
            // }

            $streams = Stream::byUser($request->user()->id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendResponse('Your streams retrieved successfully', [
                'streams' => $streams->map(function ($stream) {
                    return $this->formatStreamResponse($stream);
                })
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve your streams', $e->getMessage(), 500);
        }
    }

    /**
     * Update stream details (Admin only)
     */
    public function update(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            // Check if user is the stream owner
            if ($stream->user_id !== $request->user()->id) {
                return $this->sendError('Unauthorized. You can only update your own streams.', null, 403);
            }

            // Cannot update live or ended streams
            if ($stream->status !== 'upcoming') {
                return $this->sendError('Cannot update stream that is not in upcoming status', null, 400);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'string|max:255',
                'description' => 'nullable|string|max:1000',
                'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'scheduled_at' => 'nullable|date|after:now',
                'is_paid' => 'boolean',
                'price' => 'required_if:is_paid,true|numeric|min:0',
                'currency' => 'string|in:USD,NGN|max:3',
                'max_viewers' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $data = $validator->validated();

            // Handle banner image upload
            if ($request->hasFile('banner_image')) {
                // Delete old banner if exists
                if ($stream->banner_image) {
                    Storage::disk('public')->delete($stream->banner_image);
                }

                $path = $request->file('banner_image')->store('stream-banners', 'public');
                $data['banner_image'] = $path;
                $data['banner_image_url'] = Storage::url($path);
            }

            $stream->update($data);

            return $this->sendResponse('Stream updated successfully', [
                'stream' => $this->formatStreamResponse($stream->load('user'))
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to update stream', $e->getMessage(), 500);
        }
    }

    /**
     * Delete stream (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            // Check if user is the stream owner
            if ($stream->user_id !== $request->user()->id) {
                return $this->sendError('Unauthorized. You can only delete your own streams.', null, 403);
            }

            // Cannot delete live streams
            if ($stream->status === 'live') {
                return $this->sendError('Cannot delete a live stream. End the stream first.', null, 400);
            }

            // Delete banner image if exists
            if ($stream->banner_image) {
                Storage::disk('public')->delete($stream->banner_image);
            }

            $stream->delete();

            return $this->sendResponse('Stream deleted successfully');

        } catch (\Exception $e) {
            return $this->sendError('Failed to delete stream', $e->getMessage(), 500);
        }
    }

    /**
     * Format stream response
     */
    private function formatStreamResponse(Stream $stream, $user = null): array
    {
        $response = [
            'id' => $stream->id,
            'title' => $stream->title,
            'description' => $stream->description,
            'banner_image_url' => $stream->banner_image_url,
            'status' => $stream->status,
            'is_live' => $stream->is_live,
            'is_paid' => $stream->is_paid,
            'price' => $stream->price,
            'currency' => $stream->currency,
            'max_viewers' => $stream->max_viewers,
            'current_viewers' => $stream->current_viewers,
            'likes_count' => $stream->likes_count ?? 0,
            'dislikes_count' => $stream->dislikes_count ?? 0,
            'shares_count' => $stream->shares_count ?? 0,
            'duration' => $stream->duration,
            'scheduled_at' => $stream->scheduled_at,
            'started_at' => $stream->started_at,
            'ended_at' => $stream->ended_at,
            'created_at' => $stream->created_at,
            'updated_at' => $stream->updated_at,
            'streamer' => [
                'id' => $stream->user->id,
                'username' => $stream->user->username,
                'name' => $stream->user->name,
                'profile_picture' => $stream->user->profile_picture,
            ],
        ];

        // Add user-specific interaction data if user is provided
        if ($user) {
            $response['user_interaction'] = $stream->getUserInteraction($user);
            $response['has_liked'] = $stream->hasUserLiked($user);
            $response['has_disliked'] = $stream->hasUserDisliked($user);
        }

        return $response;
    }


    /**
     * Check user's watch duration for a stream and enforce free_minutes for paid streams
     */
    public function checkWatchDuration(Request $request, $id)
    {
        $user = $request->user();
        $stream = \App\Models\Stream::findOrFail($id);

        // Only enforce for paid streams with free minutes
        if (!$stream->is_paid || $stream->free_minutes <= 0) {
            return response()->json([
                'success' => true,
                'can_watch' => true,
                'message' => 'No restriction for this stream.'
            ]);
        }

        // Find viewer record
        $viewer = $stream->viewers()->where('user_id', $user->id)->first();
        if (!$viewer) {
            return response()->json([
                'success' => false,
                'can_watch' => false,
                'message' => 'Viewer record not found.'
            ], 404);
        }

        // Calculate minutes watched
        $joinedAt = $viewer->joined_at;
        $now = now();
        $minutesWatched = $joinedAt ? $joinedAt->diffInMinutes($now) : 0;

        // Check if user has paid (adjust this logic as needed)
        $hasPaid = $stream->payments()->where('user_id', $user->id)->where('status', 'completed')->exists();

        if ($minutesWatched >= $stream->free_minutes && !$hasPaid) {
            return response()->json([
                'success' => true,
                'can_watch' => false,
                'minutes_watched' => $minutesWatched,
                'free_minutes' => $stream->free_minutes,
                'message' => 'Free minutes used up. Please make payment to continue watching.'
            ]);
        }

        return response()->json([
            'success' => true,
            'can_watch' => true,
            'minutes_watched' => $minutesWatched,
            'free_minutes' => $stream->free_minutes,
            'message' => 'You can continue watching.'
        ]);
    }

    /**
     * Check if authenticated user has access to watch a stream
     */
    public function checkWatchAccess(Request $request, $id)
    {
        try {
            $stream = Stream::findOrFail($id);
            $user = $request->user();
            $hasAccess = false;
            $reason = '';
            $details = [];

            // Check stream status first
            if ($stream->status === 'ended') {
                $hasAccess = false;
                $reason = 'Stream has ended and is no longer available';
            } elseif ($stream->status === 'upcoming') {
                $hasAccess = false;
                $reason = 'Stream has not started yet';
            } elseif ($stream->status !== 'live') {
                $hasAccess = false;
                $reason = 'Stream is not currently live';
            } else {
                // Stream is live, check payment status
                if (!$stream->is_paid || $stream->price <= 0) {
                    $hasAccess = true;
                    $reason = 'Free stream access granted';
                } else {
                    // Check if user has paid for the stream
                    $hasAccess = $stream->hasUserPaid($user);
                    $reason = $hasAccess ? 'Premium stream access verified' : 'Payment required for premium stream';

                    if (!$hasAccess && $stream->free_minutes > 0) {
                        // Check if user can still watch free minutes
                        $viewer = $stream->viewers()->where('user_id', $user->id)->first();
                        if ($viewer && $viewer->joined_at) {
                            $minutesWatched = $viewer->joined_at->diffInMinutes(now());
                            if ($minutesWatched < $stream->free_minutes) {
                                $hasAccess = true;
                                $reason = 'Free minutes still available';
                                $details['free_minutes_remaining'] = $stream->free_minutes - $minutesWatched;
                                $details['minutes_watched'] = $minutesWatched;
                            } else {
                                $reason = 'Free minutes exhausted - payment required';
                                $details['minutes_watched'] = $minutesWatched;
                            }
                        } else {
                            $hasAccess = true;
                            $reason = 'Free minutes available for new viewer';
                            $details['free_minutes_available'] = $stream->free_minutes;
                        }
                    }
                }
            }

            // Get payment status details if it's a paid stream
            $paymentDetails = null;
            if ($stream->is_paid && $stream->price > 0) {
                $completedPayment = $stream->completedPayments()->where('user_id', $user->id)->first();
                $paymentDetails = [
                    'has_paid' => (bool) $completedPayment,
                    'payment_required' => !$completedPayment,
                    'stream_price' => $stream->price,
                    'currency' => $stream->currency,
                    'free_minutes' => $stream->free_minutes ?? 0,
                ];

                if ($completedPayment) {
                    $paymentDetails['payment_date'] = $completedPayment->paid_at;
                    $paymentDetails['payment_reference'] = $completedPayment->reference;
                }
            }

            return response()->json([
                'success' => true,
                'has_access' => $hasAccess,
                'reason' => $reason,
                'details' => $details,
                'stream_info' => [
                    'id' => $stream->id,
                    'title' => $stream->title,
                    'description' => $stream->description,
                    'status' => $stream->status,
                    'is_paid' => $stream->is_paid,
                    'price' => $stream->price,
                    'currency' => $stream->currency,
                    'free_minutes' => $stream->free_minutes ?? 0,
                    'current_viewers' => $stream->current_viewers,
                    'max_viewers' => $stream->max_viewers,
                    'started_at' => $stream->started_at,
                    'scheduled_at' => $stream->scheduled_at,
                    'streamer' => [
                        'id' => $stream->user->id,
                        'name' => $stream->user->name,
                        'username' => $stream->user->username ?? $stream->user->name,
                    ],
                ],
                'payment_info' => $paymentDetails,
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'authenticated' => true,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check watch access: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Like a stream
     */
    public function likeStream(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);
            $user = $request->user();

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            $result = $stream->toggleLike($user);

            return $this->sendResponse('Stream like toggled successfully', [
                'action' => $result['action'],
                'type' => $result['type'],
                'from' => $result['from'] ?? null,
                'interaction_stats' => $stream->getInteractionStats(),
                'user_interaction' => $stream->getUserInteraction($user),
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to like stream', $e->getMessage(), 500);
        }
    }

    /**
     * Dislike a stream
     */
    public function dislikeStream(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);
            $user = $request->user();

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            $result = $stream->toggleDislike($user);

            return $this->sendResponse('Stream dislike toggled successfully', [
                'action' => $result['action'],
                'type' => $result['type'],
                'from' => $result['from'] ?? null,
                'interaction_stats' => $stream->getInteractionStats(),
                'user_interaction' => $stream->getUserInteraction($user),
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to dislike stream', $e->getMessage(), 500);
        }
    }

    /**
     * Share a stream
     */
    public function shareStream(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);
            $user = $request->user();

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            $validator = Validator::make($request->all(), [
                'platform' => 'nullable|string|in:facebook,twitter,whatsapp,instagram,telegram,email,copy_link',
                'metadata' => 'nullable|array',
                'metadata.message' => 'nullable|string|max:1000',
                'metadata.recipients' => 'nullable|array',
                'metadata.url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $platform = $request->get('platform');
            $metadata = $request->get('metadata', []);

            // Add share timestamp and additional metadata
            $metadata['shared_at'] = now()->toISOString();
            $metadata['stream_title'] = $stream->title;
            $metadata['streamer_name'] = $stream->user->name;

            $shareInteraction = $stream->addShare($user, $platform, $metadata);

            return $this->sendResponse('Stream shared successfully', [
                'share_id' => $shareInteraction->id,
                'platform' => $platform,
                'shared_at' => $shareInteraction->created_at,
                'interaction_stats' => $stream->getInteractionStats(),
               // 'share_url' => url("/streams/{$stream->id}"),
                'share_url' =>'https://www.connectinc.app/livestream',
            ], 201);

        } catch (\Exception $e) {
            return $this->sendError('Failed to share stream', $e->getMessage(), 500);
        }
    }

    /**
     * Get stream interaction stats
     */
    public function getInteractionStats(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            $user = $request->user();
            $userInteraction = $user ? $stream->getUserInteraction($user) : null;

            return $this->sendResponse('Stream interaction stats retrieved successfully', [
                'stream_id' => $stream->id,
                'interaction_stats' => $stream->getInteractionStats(),
                'user_interaction' => $userInteraction,
                'has_liked' => $user ? $stream->hasUserLiked($user) : false,
                'has_disliked' => $user ? $stream->hasUserDisliked($user) : false,
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve interaction stats', $e->getMessage(), 500);
        }
    }

    /**
     * Get stream shares with details
     */
    public function getStreamShares(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            $limit = $request->get('limit', 20);
            $shares = $stream->shares()
                ->with('user:id,name,username')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($share) {
                    return [
                        'id' => $share->id,
                        'platform' => $share->share_platform,
                        'shared_at' => $share->created_at,
                        'user' => [
                            'id' => $share->user->id,
                            'name' => $share->user->name,
                            'username' => $share->user->username ?? $share->user->name,
                        ],
                        'metadata' => $share->share_metadata,
                    ];
                });

            return $this->sendResponse('Stream shares retrieved successfully', [
                'stream_id' => $stream->id,
                'shares_count' => $stream->shares_count,
                'shares' => $shares,
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve stream shares', $e->getMessage(), 500);
        }
    }

    /**
     * Remove user's interaction (unlike/undislike)
     */
    public function removeInteraction(Request $request, $id)
    {
        try {
            $stream = Stream::find($id);
            $user = $request->user();

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            $validator = Validator::make($request->all(), [
                'interaction_type' => 'required|string|in:like,dislike',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $interactionType = $request->get('interaction_type');

            $interaction = StreamInteraction::forStream($stream->id)
                ->forUser($user->id)
                ->where('interaction_type', $interactionType)
                ->first();

            if (!$interaction) {
                return $this->sendError("No {$interactionType} found to remove", null, 404);
            }

            $interaction->delete();
            StreamInteraction::updateStreamCounts($stream->id);

            // Refresh the stream to get updated counts
            $stream->refresh();

            return $this->sendResponse('Interaction removed successfully', [
                'removed_interaction' => $interactionType,
                'interaction_stats' => $stream->getInteractionStats(),
                'user_interaction' => $stream->getUserInteraction($user),
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to remove interaction', $e->getMessage(), 500);
        }
    }
}
