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
                        ],
                        'hardware_encoder' => [
                            'name' => 'Hardware Encoder (AVMatrix, Osee, etc.)',
                            'steps' => [
                                'Access your device settings (web UI or physical controls)',
                                'Navigate to Stream/RTMP settings',
                                'Set RTMP URL: ' . $rtmpStream->rtmp_url,
                                'Set Stream Key: ' . $rtmpStream->stream_key,
                                'Video: 1080p, 4000kbps, 30fps',
                                'Audio: 48kHz, 128kbps, Stereo',
                                'Keyframe Interval: 2 seconds',
                                'Start streaming/encoding'
                            ],
                            'notes' => [
                                'Hardware encoders provide better quality and reliability',
                                'Use higher bitrate (4000-5000 kbps) for professional quality',
                                'Ensure firmware is up to date',
                                'Check device supports H.264 codec'
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

    /**
     * Authenticate RTMP stream (called by NGINX-RTMP on_publish)
     * This validates the stream key when a broadcaster connects
     */
    public function authenticateStream(Request $request): JsonResponse
    {
        try {
            // NGINX-RTMP sends: name (stream key), app, addr, clientid
            $streamKey = $request->input('name');
            $clientIp = $request->input('addr', $request->ip());

            if (!$streamKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'No stream key provided'
                ], 403);
            }

            // Find the RTMP stream by key
            $rtmpStream = RtmpStream::where('stream_key', $streamKey)->first();

            if (!$rtmpStream) {
                \Log::warning('RTMP Auth Failed: Invalid stream key', [
                    'stream_key' => $streamKey,
                    'client_ip' => $clientIp
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid stream key'
                ], 403);
            }

            // Check if the associated stream exists and is valid
            $stream = $rtmpStream->stream;
            if (!$stream) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stream not found'
                ], 404);
            }

            // Activate the RTMP stream
            $rtmpStream->update([
                'is_active' => true,
                'last_heartbeat' => now(),
                'metadata' => array_merge($rtmpStream->metadata ?? [], [
                    'client_ip' => $clientIp,
                    'connected_at' => now()->toISOString(),
                    'nginx_app' => $request->input('app'),
                    'client_id' => $request->input('clientid')
                ])
            ]);

            // Update main stream status
            $stream->update([
                'status' => 'live',
                'started_at' => $stream->started_at ?? now()
            ]);

            \Log::info('RTMP Stream Authenticated', [
                'stream_id' => $stream->id,
                'stream_key' => $streamKey,
                'client_ip' => $clientIp
            ]);

            // Return 200 OK to allow the stream
            return response()->json([
                'success' => true,
                'message' => 'Stream authorized',
                'stream_id' => $stream->id
            ]);

        } catch (\Exception $e) {
            \Log::error('RTMP Auth Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle RTMP stream end (called by NGINX-RTMP on_publish_done)
     */
    public function streamEnded(Request $request): JsonResponse
    {
        try {
            $streamKey = $request->input('name');

            if (!$streamKey) {
                return response()->json(['success' => false], 400);
            }

            $rtmpStream = RtmpStream::where('stream_key', $streamKey)->first();

            if ($rtmpStream) {
                $rtmpStream->update([
                    'is_active' => false,
                    'metadata' => array_merge($rtmpStream->metadata ?? [], [
                        'disconnected_at' => now()->toISOString()
                    ])
                ]);

                // Optionally update main stream status
                if ($rtmpStream->stream) {
                    $rtmpStream->stream->update([
                        'status' => 'ended',
                        'ended_at' => now()
                    ]);
                }

                \Log::info('RTMP Stream Ended', [
                    'stream_key' => $streamKey
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stream ended'
            ]);

        } catch (\Exception $e) {
            \Log::error('RTMP End Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RTMP server status and configuration
     */
    public function getServerStatus(): JsonResponse
    {
        $rtmpUrl = config('streaming.rtmp.server_url');
        $port = 1935;

        // Parse host from RTMP URL
        $parsed = parse_url($rtmpUrl);
        $host = $parsed['host'] ?? 'localhost';

        // Check if RTMP port is accessible (basic check)
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        $isPortOpen = $connection !== false;

        if ($connection) {
            fclose($connection);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rtmp_url' => $rtmpUrl,
                'host' => $host,
                'port' => $port,
                'port_accessible' => $isPortOpen,
                'status' => $isPortOpen ? 'online' : 'offline',
                'message' => $isPortOpen
                    ? 'RTMP server is accessible'
                    : 'RTMP server port 1935 is not accessible. Please ensure NGINX-RTMP is running.',
                'active_streams' => RtmpStream::where('is_active', true)->count(),
                'total_configured' => RtmpStream::count()
            ]
        ]);
    }
}
