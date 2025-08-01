<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stream;
use App\Models\StreamChat;
use App\Models\StreamViewer;
use App\Models\StreamCamera;
use App\Models\CameraSwitch;
use App\Models\StreamMixerSetting;
use App\Models\User;
use App\Helpers\AgoraHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StreamManagementController extends Controller
{
    /**
     * Display streams listing page
     */
    public function index()
    {
        return view('admin.streams.index');
    }

    /**
     * Show create stream form
     */
    public function create()
    {
        return view('admin.streams.create');
    }

    /**
     * Store new stream
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|image|max:5120', // 5MB max
            'free_minutes' => 'required|integer|min:0',
            'price' => 'required_if:free_minutes,0|nullable|numeric|min:0',
            'currency' => 'required|string|in:USD,NGN,EUR,GBP',
            'max_viewers' => 'nullable|integer|min:1',
            'stream_type' => 'required|in:immediate,scheduled',
            'scheduled_at' => 'required_if:stream_type,scheduled|nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['user_id'] = auth('admin')->user()->id;
            $data['channel_name'] = 'admin_stream_' . time() . '_' . Str::random(8);
            $data['go_live_immediately'] = $request->stream_type === 'immediate';

            // Set payment status
            $data['is_paid'] = $data['free_minutes'] == 0 && $data['price'] > 0;

            // Handle banner image upload
            if ($request->hasFile('banner_image')) {
                $file = $request->file('banner_image');
                $filename = 'stream_banners/' . time() . '_' . $file->getClientOriginalName();
                Storage::disk('s3')->put($filename, file_get_contents($file));
                $data['banner_image'] = $filename;
                $data['banner_image_url'] = config('filesystems.disks.s3.url') . '/' . $filename;
            }

            // Set status based on stream type
            if ($data['stream_type'] === 'immediate' && $data['go_live_immediately']) {
                $data['status'] = 'live';
                $data['started_at'] = now();
            } else {
                $data['status'] = 'upcoming';
            }

            $stream = Stream::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Stream created successfully',
                'data' => $stream->load('user')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stream: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show stream details
     */
    public function show($id)
    {
        $stream = Stream::with(['user', 'viewers.user', 'chats.user', 'payments.user'])->findOrFail($id);
        return view('admin.streams.show', compact('stream'));
    }

    /**
     * Show broadcast page for streaming
     */
    public function broadcast($id)
    {
        $stream = Stream::findOrFail($id);

        // Only allow broadcasting for live or upcoming streams
        if (!in_array($stream->status, ['upcoming', 'live'])) {
            return redirect()->route('admin.streams.show', $stream)
                ->with('error', 'Cannot broadcast this stream. Stream must be upcoming or live.');
        }

        return view('admin.streams.broadcast', compact('stream'));
    }

    /**
     * Show camera management page for multi-camera streaming
     */
    public function cameraManagement($id)
    {
        $stream = Stream::findOrFail($id);

        // Only allow camera management for live or upcoming streams
        if (!in_array($stream->status, ['upcoming', 'live'])) {
            return redirect()->route('admin.streams.show', $stream)
                ->with('error', 'Cannot manage cameras for this stream. Stream must be upcoming or live.');
        }

        return view('admin.streams.camera-management', compact('stream'));
    }

    /**
     * Show edit stream form
     */
    public function edit($id)
    {
        $stream = Stream::findOrFail($id);
        return view('admin.streams.edit', compact('stream'));
    }

    /**
     * Update stream
     */
    public function update(Request $request, $id)
    {
        $stream = Stream::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|image|max:5120',
            'free_minutes' => 'required|integer|min:0',
            'price' => 'required_if:free_minutes,0|nullable|numeric|min:0',
            'currency' => 'required|string|in:USD,NGN,EUR,GBP',
            'max_viewers' => 'nullable|integer|min:1',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Handle banner image upload
            if ($request->hasFile('banner_image')) {
                // Delete old image
                if ($stream->banner_image) {
                    Storage::disk('s3')->delete($stream->banner_image);
                }

                $file = $request->file('banner_image');
                $filename = 'stream_banners/' . time() . '_' . $file->getClientOriginalName();
                Storage::disk('s3')->put($filename, file_get_contents($file));
                $data['banner_image'] = $filename;
                $data['banner_image_url'] = config('filesystems.disks.s3.url') . '/' . $filename;
            }

            // Set payment status
            $data['is_paid'] = $data['free_minutes'] == 0 && $data['price'] > 0;

            $stream->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Stream updated successfully',
                'data' => $stream->fresh()->load('user')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stream: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a stream
     */
    public function startStream($id)
    {
        try {
            $stream = Stream::findOrFail($id);

            if ($stream->status !== 'upcoming') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stream is not in upcoming status'
                ], 400);
            }

            // Update stream to live status
            $stream->update([
                'status' => 'live',
                'started_at' => now()
            ]);

            // Log the stream start for debugging
            \Log::info('Stream started successfully', [
                'stream_id' => $stream->id,
                'channel_name' => $stream->channel_name,
                'user_id' => $stream->user_id,
                'admin_id' => auth('admin')->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stream started successfully',
                'data' => $stream->fresh()
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to start stream', [
                'stream_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start stream: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * End a stream
     */
    public function endStream($id)
    {
        try {
            $stream = Stream::findOrFail($id);

            if ($stream->status !== 'live') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stream is not currently live'
                ], 400);
            }

            $stream->update([
                'status' => 'ended',
                'ended_at' => now(),
                'current_viewers' => 0
            ]);

            // Mark all viewers as inactive
            $stream->viewers()->where('is_active', true)->update([
                'is_active' => false,
                'left_at' => now()
            ]);

            // Log the stream end for debugging
            Log::info('Stream ended successfully', [
                'stream_id' => $stream->id,
                'channel_name' => $stream->channel_name,
                'user_id' => $stream->user_id,
                'admin_id' => auth('admin')->id(),
                'duration' => $stream->started_at ? now()->diffInMinutes($stream->started_at) : null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stream ended successfully',
                'data' => $stream->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to end stream', [
                'stream_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to end stream: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete stream
     */
    public function destroy($id)
    {
        try {
            $stream = Stream::findOrFail($id);

            // Delete banner image if exists
            if ($stream->banner_image) {
                Storage::disk('s3')->delete($stream->banner_image);
            }

            $stream->delete();

            return response()->json([
                'success' => true,
                'message' => 'Stream deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete stream: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get streams for DataTable
     */
    public function getStreams(Request $request)
    {
        $query = Stream::with(['user'])
            ->select('streams.*')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('stream_type')) {
            $query->where('stream_type', $request->stream_type);
        }

        if ($request->filled('date_range')) {
            $dates = explode(' to ', $request->date_range);
            if (count($dates) === 2) {
                $query->whereBetween('created_at', [
                    Carbon::parse($dates[0])->startOfDay(),
                    Carbon::parse($dates[1])->endOfDay()
                ]);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $streams = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $streams->items(),
            'pagination' => [
                'current_page' => $streams->currentPage(),
                'last_page' => $streams->lastPage(),
                'per_page' => $streams->perPage(),
                'total' => $streams->total()
            ]
        ]);
    }

    /**
     * Get stream statistics
     */
    public function getStats()
    {
        $stats = [
            'total_streams' => Stream::count(),
            'live_streams' => Stream::where('status', 'live')->count(),
            'upcoming_streams' => Stream::where('status', 'upcoming')->count(),
            'ended_streams' => Stream::where('status', 'ended')->count(),
            'total_viewers' => StreamViewer::count(),
            'active_viewers' => StreamViewer::where('is_active', true)->count(),
            'total_messages' => StreamChat::count(),
            'paid_streams' => Stream::where('is_paid', true)->count(),
            'free_streams' => Stream::where('is_paid', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get stream viewers
     */
    public function getViewers($id)
    {
        $stream = Stream::findOrFail($id);
        $viewers = $stream->viewers()->with('user')->orderBy('joined_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $viewers
        ]);
    }

    /**
     * Get stream chat messages
     */
    public function getChats($id, Request $request)
    {
        $stream = Stream::findOrFail($id);

        $query = $stream->chats()->with('user')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $chats = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $chats->items(),
            'pagination' => [
                'current_page' => $chats->currentPage(),
                'last_page' => $chats->lastPage(),
                'per_page' => $chats->perPage(),
                'total' => $chats->total()
            ]
        ]);
    }

    /**
     * Send admin message to stream chat
     */
    public function sendAdminMessage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stream = Stream::findOrFail($id);
            $admin = auth('admin')->user();

            $chat = StreamChat::create([
                'stream_id' => $stream->id,
                'user_id' => $admin->id,
                'username' => $admin->name . ' (Admin)',
                'message' => $request->message,
                'user_profile_url' => $admin->profile_image,
                'is_admin' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $chat->load('user')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete chat message
     */
    public function deleteChat($streamId, $chatId)
    {
        try {
            $chat = StreamChat::where('stream_id', $streamId)->findOrFail($chatId);
            $chat->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate Agora token for broadcasting
     */
    public function getStreamToken($id)
    {
        try {
            Log::info('Token request received for stream: ' . $id);

            $stream = Stream::findOrFail($id);
            $admin = auth('admin')->user();

            if (!$admin) {
                Log::error('Admin not authenticated for token request');
                return response()->json([
                    'success' => false,
                    'message' => 'Admin authentication required'
                ], 401);
            }

            Log::info('Admin authenticated: ' . $admin->id);

            // Generate unique UID for admin broadcaster
            $agoraUid = StreamViewer::generateAgoraUid();
            Log::info('Generated Agora UID: ' . $agoraUid);

            // Generate token using AgoraHelper
            $token = AgoraHelper::generateRtcToken(
                $stream->channel_name,
                (int)$agoraUid,
                3600, // 1 hour expiry
                'publisher' // Admin is publisher/broadcaster
            );

            if (!$token) {
                Log::error('Failed to generate Agora token');
                throw new \Exception('Failed to generate Agora token');
            }

            Log::info('Token generated successfully');

            $response = [
                'success' => true,
                'token' => $token,
                'uid' => $agoraUid,
                'channel_name' => $stream->channel_name,
                'app_id' => AgoraHelper::getAppId(),
                'expires_at' => now()->addHour()->toISOString()
            ];

            Log::info('Returning token response', ['has_token' => !empty($token), 'app_id' => $response['app_id']]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error generating token: ' . $e->getMessage(), [
                'stream_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate token: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== MULTI-CAMERA MANAGEMENT METHODS =====

    /**
     * Get cameras for a stream
     */
    public function getCameras($streamId)
    {
        try {
            $stream = Stream::findOrFail($streamId);
            $cameras = $stream->cameras()->orderBy('created_at', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $cameras->map(function ($camera) {
                    return [
                        'id' => $camera->id,
                        'camera_name' => $camera->camera_name,
                        'stream_key' => $camera->stream_key,
                        'device_type' => $camera->device_type,
                        'agora_uid' => $camera->agora_uid,
                        'is_active' => $camera->is_active,
                        'is_primary' => $camera->is_primary,
                        'resolution' => $camera->resolution,
                        'status' => $camera->status,
                        'last_seen_at' => $camera->last_seen_at,
                        'created_at' => $camera->created_at,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cameras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new camera to stream
     */
    public function addCamera(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'camera_name' => 'required|string|max:255',
            'device_type' => 'nullable|string|in:phone,laptop,camera,tablet,other',
            'resolution' => 'nullable|string|in:480p,720p,1080p,4K',
            'device_id' => 'nullable|string', // Allow device_id from frontend
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stream = Stream::findOrFail($streamId);

            $camera = $stream->addCamera(
                $request->camera_name,
                $request->device_type ?: 'other'
            );

            // Update additional fields
            $updateData = [];
            if ($request->resolution) {
                $updateData['resolution'] = $request->resolution;
            }
            if ($request->device_id) {
                $updateData['device_id'] = $request->device_id;
            }

            if (!empty($updateData)) {
                $camera->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Camera added successfully',
                'data' => [
                    'id' => $camera->id,
                    'camera_name' => $camera->camera_name,
                    'stream_key' => $camera->stream_key,
                    'device_type' => $camera->device_type,
                    'agora_uid' => $camera->agora_uid,
                    'is_active' => $camera->is_active,
                    'is_primary' => $camera->is_primary,
                    'resolution' => $camera->resolution,
                    'status' => $camera->status,
                    'device_id' => $camera->device_id ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add camera: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a camera from stream
     */
    public function removeCamera($streamId, $cameraId)
    {
        try {
            $stream = Stream::findOrFail($streamId);
            $camera = $stream->cameras()->findOrFail($cameraId);

            // Don't allow removing the primary camera if there are other cameras
            if ($camera->is_primary && $stream->cameras()->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove primary camera. Switch to another camera first.'
                ], 400);
            }

            $camera->delete();

            return response()->json([
                'success' => true,
                'message' => 'Camera removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove camera: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Switch to a different camera
     */
    public function switchCamera(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'camera_id' => 'required|exists:stream_cameras,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stream = Stream::findOrFail($streamId);
            $adminId = auth('admin')->user()->id;

            $success = $stream->switchToCamera($request->camera_id, $adminId);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Camera is not active or not found'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Camera switched successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to switch camera: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update camera status (connect/disconnect)
     */
    public function updateCameraStatus(Request $request, $streamId, $cameraId)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stream = Stream::findOrFail($streamId);
            $camera = $stream->cameras()->findOrFail($cameraId);

            if ($request->is_active) {
                $camera->connect();
            } else {
                $camera->disconnect();
            }

            return response()->json([
                'success' => true,
                'message' => 'Camera status updated successfully',
                'data' => [
                    'is_active' => $camera->is_active,
                    'status' => $camera->status,
                    'last_seen_at' => $camera->last_seen_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update camera status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get mixer settings for a stream
     */
    public function getMixerSettings($streamId)
    {
        try {
            $stream = Stream::findOrFail($streamId);
            $settings = $stream->initializeMixerSettings();

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get mixer settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update mixer settings
     */
    public function updateMixerSettings(Request $request, $streamId)
    {
        $validator = Validator::make($request->all(), [
            'layout_type' => 'nullable|in:single,picture_in_picture,split_screen,quad_view',
            'transition_effect' => 'nullable|in:fade,cut,slide,zoom',
            'transition_duration' => 'nullable|integer|min:100|max:5000',
            'mixer_config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $stream = Stream::findOrFail($streamId);
            $settings = $stream->initializeMixerSettings();

            $updateData = array_filter($validator->validated());
            $settings->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Mixer settings updated successfully',
                'data' => $settings->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update mixer settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get camera switching history
     */
    public function getCameraSwitchHistory($streamId)
    {
        try {
            $stream = Stream::findOrFail($streamId);
            $switches = $stream->cameraSwitches()
                ->with(['fromCamera', 'toCamera', 'switchedBy'])
                ->recent(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $switches->map(function ($switch) {
                    return [
                        'id' => $switch->id,
                        'from_camera' => $switch->fromCamera?->camera_name,
                        'to_camera' => $switch->toCamera->camera_name,
                        'switched_by' => $switch->switchedBy->name,
                        'switched_at' => $switch->switched_at,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get switch history: ' . $e->getMessage()
            ], 500);
        }
    }
}
