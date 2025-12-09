<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Helpers\AgoraHelper;
use App\Models\User;
use App\Models\Stream;
use App\Models\StreamChat;
use App\Models\StreamViewer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GuestStreamController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'force_guest' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            if ($existingUser->is_guest) {
                // Return existing guest token
                $token = $existingUser->createToken('guest-token')->plainTextToken;
                
                return response()->json([
                    'success' => true,
                    'guest_token' => $token,
                    'user' => [
                        'id' => $existingUser->id,
                        'name' => $existingUser->name,
                        'email' => $existingUser->email,
                        'is_guest' => true
                    ]
                ]);
            }

            // Registered user exists
            if (!$request->force_guest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email belongs to a registered account. Login recommended. To continue as guest, set force_guest=true.',
                    'registered_user' => true
                ], 409);
            }
        }

        // Create guest user
        $guestUser = User::create([
            'name' => $request->name,
            'email' => $email,
            'username' => 'guest_' . time() . '_' . Str::random(6),
            'password' => Hash::make(Str::random(32)),
            'is_guest' => true,
            'registration_step' => 0,
            'guest_expires_at' => Carbon::now()->addDays(30),
            'is_active' => true,
            'deleted_flag' => 'N'
        ]);

        $token = $guestUser->createToken('guest-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'guest_token' => $token,
            'user' => [
                'id' => $guestUser->id,
                'name' => $guestUser->name,
                'email' => $guestUser->email,
                'is_guest' => true
            ]
        ]);
    }

    public function getLiveStreams(Request $request)
    {
        $stream = Stream::where('status', 'live')
            ->orderBy('started_at', 'desc')
            ->first();

        if (!$stream) {
            return response()->json([
                'success' => false,
                'message' => 'No live streams available'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $stream->id,
                'title' => $stream->title,
                'description' => $stream->description,
                'banner_image_url' => $stream->banner_image_url,
                'is_paid' => $stream->is_paid,
                'price' => $stream->price,
                'currency' => $stream->currency,
                'free_minutes' => $stream->free_minutes,
                'status' => $stream->status,
                'current_viewers' => $stream->current_viewers,
                'started_at' => $stream->started_at
            ]
        ]);
    }

    public function getStreamDetails($streamId)
    {
        $stream = Stream::find($streamId);

        if (!$stream) {
            return response()->json([
                'success' => false,
                'message' => 'Stream not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'stream' => [
                'id' => $stream->id,
                'title' => $stream->title,
                'description' => $stream->description,
                'banner_image_url' => $stream->banner_image_url,
                'is_paid' => $stream->is_paid,
                'price' => $stream->price,
                'currency' => $stream->currency,
                'free_minutes' => $stream->free_minutes,
                'status' => $stream->status,
                'current_viewers' => $stream->current_viewers
            ]
        ]);
    }

    public function joinStream(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'guest_token' => 'required|string',
            'platform' => 'nullable|string|in:web,ios,android'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $guest = $request->user();
        $stream = Stream::find($streamId);

        if (!$stream) {
            return response()->json([
                'success' => false,
                'message' => 'Stream not found'
            ], 404);
        }

        if ($stream->status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'Stream is not live'
            ], 403);
        }

        if (!$stream->canUserJoin($guest)) {
            return response()->json([
                'success' => false,
                'message' => 'Payment required to join this stream'
            ], 402);
        }

        // Generate Agora credentials
        $agoraUid = StreamViewer::generateAgoraUid();
        $agoraToken = AgoraHelper::generateRtcToken($stream->channel_name, (int)$agoraUid, 3600, 'subscriber');

        if (!$agoraToken) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate viewing token'
            ], 500);
        }

        // Add viewer
        $viewer = $stream->addViewer($guest, $agoraUid, $agoraToken);

        return response()->json([
            'success' => true,
            'viewer' => [
                'id' => $viewer->id,
                'stream_id' => $stream->id,
                'user_id' => $guest->id
            ],
            'agora' => [
                'app_id' => AgoraHelper::getAppId(),
                'channel_name' => $stream->channel_name,
                'token' => $agoraToken,
                'uid' => $agoraUid,
                'role' => 'subscriber'
            ]
        ]);
    }

    public function leaveStream(Request $request, $streamId)
    {
        $guest = $request->user();
        $stream = Stream::find($streamId);

        if (!$stream) {
            return response()->json([
                'success' => false,
                'message' => 'Stream not found'
            ], 404);
        }

        $stream->removeViewer($guest);

        return response()->json([
            'success' => true,
            'message' => 'Left stream successfully'
        ]);
    }

    public function sendChatMessage(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $guest = $request->user();
        $stream = Stream::find($streamId);

        if (!$stream) {
            return response()->json([
                'success' => false,
                'message' => 'Stream not found'
            ], 404);
        }

        if ($stream->status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'Stream is not live'
            ], 403);
        }

        // Check if user has paid for paid streams
        if ($stream->is_paid && !$stream->hasUserPaid($guest)) {
            return response()->json([
                'success' => false,
                'message' => 'Payment required to chat in this stream'
            ], 402);
        }

        // Create chat message
        $chat = StreamChat::create([
            'stream_id' => $stream->id,
            'user_id' => $guest->id,
            'username' => $guest->name,
            'message' => $request->message,
            'is_admin' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => [
                'id' => $chat->id,
                'stream_id' => $chat->stream_id,
                'user_id' => $chat->user_id,
                'username' => $chat->username,
                'message' => $chat->message,
                'is_admin' => $chat->is_admin,
                'created_at' => $chat->created_at->toIso8601String()
            ]
        ]);
    }

    public function initializeStripePayment(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'guest_token' => 'required|string',
            'payment_currency' => 'required|string|in:USD,NGN',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $guest = $request->user();
        $stream = Stream::find($streamId);

        if (!$stream) {
            return response()->json([
                'success' => false,
                'message' => 'Stream not found'
            ], 404);
        }

        if (!$stream->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'This stream is free'
            ], 400);
        }

        // Use existing StreamPaymentController logic
        $controller = app(\App\Http\Controllers\API\V1\StreamPaymentController::class);
        return $controller->initializeStripePayment($request, $streamId);
    }

    public function initializeNombaPayment(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'guest_token' => 'required|string',
            'payment_currency' => 'required|string|in:USD,NGN'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $guest = $request->user();
        $stream = Stream::find($streamId);

        if (!$stream) {
            return response()->json([
                'success' => false,
                'message' => 'Stream not found'
            ], 404);
        }

        if (!$stream->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'This stream is free'
            ], 400);
        }

        // Map payment_currency to currency for StreamPaymentController
        $request->merge(['currency' => $request->payment_currency]);
        
        // Use existing StreamPaymentController logic
        $controller = app(\App\Http\Controllers\API\V1\StreamPaymentController::class);
        return $controller->initializeNombaPayment($request, $streamId);
    }

    public function getViewerCount($streamId)
    {
        $stream = Stream::find($streamId);

        if (!$stream) {
            return response()->json([
                'success' => false,
                'message' => 'Stream not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'stream_id' => $stream->id,
            'current_viewers' => $stream->current_viewers,
            'status' => $stream->status
        ]);
    }
}
