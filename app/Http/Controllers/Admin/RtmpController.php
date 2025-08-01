<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stream;
use App\Models\RtmpStream;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RtmpController extends Controller
{
    /**
     * Get RTMP connection details for a stream
     */
    public function getStreamRtmpDetails($streamId): JsonResponse
    {
        try {
            $stream = Stream::findOrFail($streamId);

            // Create or get existing RTMP stream configuration
            $rtmpStream = $stream->rtmpStream;

            if (!$rtmpStream) {
                $rtmpStream = $stream->createRtmpStream();
            }

            // Generate fresh stream key if needed
            if (!$rtmpStream->stream_key || !$rtmpStream->is_active) {
                $rtmpStream->update([
                    'stream_key' => $stream->id . '_' . bin2hex(random_bytes(16)),
                    'is_active' => true,
                    'last_heartbeat' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'rtmp_url' => $rtmpStream->rtmp_url,
                    'stream_key' => $rtmpStream->stream_key,
                    'full_rtmp_url' => $rtmpStream->getFullRtmpUrl(),
                    'recommended_settings' => [
                        'resolution' => $rtmpStream->resolution,
                        'bitrate' => $rtmpStream->bitrate . ' kbps',
                        'fps' => $rtmpStream->fps,
                        'keyframe_interval' => 2,
                        'audio_bitrate' => '128 kbps',
                        'audio_sample_rate' => '44100 Hz'
                    ],
                    'software_guides' => [
                        'manycam' => [
                            'name' => 'ManyCam',
                            'steps' => [
                                'Open ManyCam and go to Settings',
                                'Click on "Streaming" tab',
                                'Select "Custom RTMP"',
                                'Enter RTMP URL: ' . $rtmpStream->rtmp_url,
                                'Enter Stream Key: ' . $rtmpStream->stream_key,
                                'Set Resolution: ' . $rtmpStream->resolution,
                                'Set Bitrate: ' . $rtmpStream->bitrate . ' kbps',
                                'Click "Start Streaming"'
                            ]
                        ],
                        'splitcam' => [
                            'name' => 'SplitCam',
                            'steps' => [
                                'Open SplitCam',
                                'Click "Share & Record" → "Media Server"',
                                'Select "Custom RTMP Server"',
                                'Server: ' . $rtmpStream->rtmp_url,
                                'Stream Key: ' . $rtmpStream->stream_key,
                                'Video Quality: High (1080p)',
                                'Audio Quality: High',
                                'Click "Start Broadcasting"'
                            ]
                        ],
                        'obs' => [
                            'name' => 'OBS Studio',
                            'steps' => [
                                'Open OBS Studio',
                                'Go to Settings → Stream',
                                'Service: Custom',
                                'Server: ' . $rtmpStream->rtmp_url,
                                'Stream Key: ' . $rtmpStream->stream_key,
                                'Click "OK" and "Start Streaming"'
                            ]
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get RTMP details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update RTMP stream settings
     */
    public function updateRtmpSettings($streamId, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'software_type' => 'required|in:manycam,splitcam,obs,xsplit,other',
                'resolution' => 'nullable|string',
                'bitrate' => 'nullable|integer|min:500|max:10000',
                'fps' => 'nullable|integer|min:15|max:60'
            ]);

            $stream = Stream::findOrFail($streamId);
            $rtmpStream = $stream->rtmpStream ?? $stream->createRtmpStream();

            $rtmpStream->update([
                'software_type' => $request->software_type,
                'resolution' => $request->resolution ?? $rtmpStream->resolution,
                'bitrate' => $request->bitrate ?? $rtmpStream->bitrate,
                'fps' => $request->fps ?? $rtmpStream->fps,
                'metadata' => array_merge($rtmpStream->metadata ?? [], [
                    'updated_at' => now()->toISOString(),
                    'updated_by' => auth()->user()->name ?? 'Admin'
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'RTMP settings updated successfully',
                'data' => $rtmpStream->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update RTMP settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check RTMP stream status
     */
    public function checkRtmpStatus($streamId): JsonResponse
    {
        try {
            $stream = Stream::findOrFail($streamId);
            $rtmpStream = $stream->rtmpStream;

            if (!$rtmpStream) {
                return response()->json([
                    'success' => true,
                    'status' => 'not_configured',
                    'message' => 'RTMP stream not configured'
                ]);
            }

            // Check if stream is active (heartbeat within last 30 seconds)
            $isActive = $rtmpStream->last_heartbeat &&
                       $rtmpStream->last_heartbeat->gt(now()->subSeconds(30));

            return response()->json([
                'success' => true,
                'status' => $isActive ? 'active' : 'inactive',
                'data' => [
                    'is_active' => $isActive,
                    'software_type' => $rtmpStream->software_type,
                    'last_heartbeat' => $rtmpStream->last_heartbeat?->toISOString(),
                    'uptime' => $isActive && $rtmpStream->last_heartbeat
                        ? now()->diffInSeconds($rtmpStream->last_heartbeat)
                        : 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check RTMP status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * RTMP heartbeat endpoint (called by RTMP server)
     */
    public function rtmpHeartbeat(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'stream_key' => 'required|string'
            ]);

            $rtmpStream = RtmpStream::where('stream_key', $request->stream_key)->first();

            if (!$rtmpStream) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid stream key'
                ], 404);
            }

            // Update heartbeat
            $rtmpStream->update([
                'last_heartbeat' => now(),
                'is_active' => true,
                'metadata' => array_merge($rtmpStream->metadata ?? [], [
                    'client_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'last_heartbeat' => now()->toISOString()
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Heartbeat recorded'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record heartbeat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop RTMP stream
     */
    public function stopRtmpStream($streamId): JsonResponse
    {
        try {
            $stream = Stream::findOrFail($streamId);
            $rtmpStream = $stream->rtmpStream;

            if ($rtmpStream) {
                $rtmpStream->update([
                    'is_active' => false,
                    'last_heartbeat' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'RTMP stream stopped'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop RTMP stream: ' . $e->getMessage()
            ], 500);
        }
    }
}
