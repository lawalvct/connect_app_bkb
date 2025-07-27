<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stream;
use App\Models\StreamChat;
use App\Models\StreamViewer;
use App\Models\User;
use App\Helpers\AgoraHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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

            $stream->update([
                'status' => 'live',
                'started_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stream started successfully',
                'data' => $stream->fresh()
            ]);

        } catch (\Exception $e) {
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

            return response()->json([
                'success' => true,
                'message' => 'Stream ended successfully',
                'data' => $stream->fresh()
            ]);

        } catch (\Exception $e) {
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
            $stream = Stream::findOrFail($id);
            $admin = auth('admin')->user();

            // Generate unique UID for admin broadcaster
            $agoraUid = StreamViewer::generateAgoraUid();

            // Generate token using AgoraHelper
            $token = AgoraHelper::generateRtcToken(
                $stream->channel_name,
                (int)$agoraUid,
                3600, // 1 hour expiry
                'publisher' // Admin is publisher/broadcaster
            );

            if (!$token) {
                throw new \Exception('Failed to generate Agora token');
            }

            return response()->json([
                'success' => true,
                'token' => $token,
                'uid' => $agoraUid,
                'channel_name' => $stream->channel_name,
                'app_id' => AgoraHelper::getAppId(),
                'expires_at' => now()->addHour()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate token: ' . $e->getMessage()
            ], 500);
        }
    }
}
