<?php

namespace App\Helpers;

use Pusher\Pusher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PusherBroadcastHelper
{
    private static $pusherInstance = null;
    private static $maxRetries = 3;
    private static $retryDelay = 1; // seconds

    /**
     * Get or create Pusher instance with connection pooling
     */
    public static function getPusherInstance(): ?Pusher
    {
        if (self::$pusherInstance !== null) {
            return self::$pusherInstance;
        }

        try {
            $pusherKey = config('broadcasting.connections.pusher.key');
            $pusherSecret = config('broadcasting.connections.pusher.secret');
            $pusherAppId = config('broadcasting.connections.pusher.app_id');
            $pusherCluster = config('broadcasting.connections.pusher.options.cluster');

            if (empty($pusherKey) || empty($pusherSecret) || empty($pusherAppId)) {
                Log::warning('Pusher configuration is incomplete', [
                    'key_exists' => !empty($pusherKey),
                    'secret_exists' => !empty($pusherSecret),
                    'app_id_exists' => !empty($pusherAppId),
                ]);
                return null;
            }

            self::$pusherInstance = new Pusher(
                $pusherKey,
                $pusherSecret,
                $pusherAppId,
                [
                    'cluster' => $pusherCluster ?: 'eu',
                    'useTLS' => true,
                    'timeout' => 30,
                    'curl_options' => [
                        CURLOPT_SSL_VERIFYHOST => 2,
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_DNS_CACHE_TIMEOUT => 300,
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_MAXREDIRS => 3,
                    ]
                ]
            );

            Log::debug('Pusher instance created successfully');
            return self::$pusherInstance;

        } catch (\Exception $e) {
            Log::error('Failed to create Pusher instance', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return null;
        }
    }

    /**
     * Broadcast event with retry logic and fallback
     */
    public static function broadcastWithRetry(
        string $channel,
        string $event,
        array $data,
        array $context = []
    ): bool {
        // Check if Pusher is temporarily disabled due to repeated failures
        if (Cache::has('pusher_disabled')) {
            Log::warning('Pusher broadcasting is temporarily disabled', $context);
            return false;
        }

        $attempt = 0;
        $lastError = null;

        while ($attempt < self::$maxRetries) {
            $attempt++;

            try {
                $pusher = self::getPusherInstance();

                if ($pusher === null) {
                    Log::warning('Pusher instance not available', $context);
                    return false;
                }

                Log::debug("Broadcasting attempt {$attempt}/" . self::$maxRetries, [
                    'channel' => $channel,
                    'event' => $event,
                    'context' => $context
                ]);

                $result = $pusher->trigger($channel, $event, $data);

                Log::info('Pusher broadcast successful', array_merge($context, [
                    'channel' => $channel,
                    'event' => $event,
                    'attempt' => $attempt,
                    'result' => $result
                ]));

                // Clear any previous failure cache
                Cache::forget('pusher_failure_count');

                return true;

            } catch (\Pusher\PusherException $e) {
                $lastError = $e;

                // Check if it's a DNS or connection error
                $isDnsError = strpos($e->getMessage(), 'Could not resolve host') !== false;
                $isConnectionError = strpos($e->getMessage(), 'Connection') !== false ||
                                   $e->getCode() === 6 || $e->getCode() === 7;

                Log::warning("Pusher broadcast attempt {$attempt} failed", [
                    'channel' => $channel,
                    'event' => $event,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'is_dns_error' => $isDnsError,
                    'is_connection_error' => $isConnectionError,
                    'context' => $context
                ]);

                // If DNS or connection error and not last attempt, wait and retry
                if (($isDnsError || $isConnectionError) && $attempt < self::$maxRetries) {
                    Log::info("Waiting " . self::$retryDelay . " second(s) before retry...");
                    sleep(self::$retryDelay);

                    // Reset Pusher instance to force reconnection
                    self::$pusherInstance = null;
                    continue;
                }

                // For other errors, don't retry
                if (!$isDnsError && !$isConnectionError) {
                    break;
                }

            } catch (\Exception $e) {
                $lastError = $e;

                Log::error("Pusher broadcast unexpected error on attempt {$attempt}", [
                    'channel' => $channel,
                    'event' => $event,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'context' => $context
                ]);

                // Don't retry on unexpected errors
                break;
            }
        }

        // All retries failed
        Log::error('Pusher broadcast failed after all retries', [
            'channel' => $channel,
            'event' => $event,
            'attempts' => $attempt,
            'last_error' => $lastError ? $lastError->getMessage() : 'Unknown',
            'context' => $context
        ]);

        // Track consecutive failures
        $failureCount = Cache::increment('pusher_failure_count', 1);

        // If too many consecutive failures, disable Pusher temporarily
        if ($failureCount >= 10) {
            Cache::put('pusher_disabled', true, 300); // 5 minutes
            Log::critical('Pusher temporarily disabled due to repeated failures', [
                'failure_count' => $failureCount,
                'disabled_for_seconds' => 300
            ]);
        }

        return false;
    }

    /**
     * Broadcast call event with standard formatting
     */
    public static function broadcastCallEvent(
        int $conversationId,
        string $eventName,
        array $callData,
        array $context = []
    ): bool {
        $channel = "conversation.{$conversationId}";
        $event = "call.{$eventName}";

        return self::broadcastWithRetry($channel, $event, $callData, array_merge($context, [
            'conversation_id' => $conversationId,
            'call_id' => $callData['call_id'] ?? null
        ]));
    }

    /**
     * Test Pusher connectivity
     */
    public static function testConnection(): array
    {
        try {
            $pusher = self::getPusherInstance();

            if ($pusher === null) {
                return [
                    'success' => false,
                    'message' => 'Pusher instance creation failed',
                    'config_valid' => false
                ];
            }

            // Try to trigger a test event
            $testChannel = 'test-channel-' . time();
            $result = $pusher->trigger($testChannel, 'test-event', ['test' => true]);

            return [
                'success' => true,
                'message' => 'Pusher connection successful',
                'config_valid' => true,
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'result' => $result
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Pusher connection failed: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
                'config_valid' => true
            ];
        }
    }

    /**
     * Reset Pusher instance (useful after configuration changes)
     */
    public static function resetInstance(): void
    {
        self::$pusherInstance = null;
        Cache::forget('pusher_disabled');
        Cache::forget('pusher_failure_count');
        Log::info('Pusher instance reset');
    }

    /**
     * Get Pusher status
     */
    public static function getStatus(): array
    {
        return [
            'instance_created' => self::$pusherInstance !== null,
            'disabled' => Cache::has('pusher_disabled'),
            'failure_count' => Cache::get('pusher_failure_count', 0),
            'config' => [
                'key' => !empty(config('broadcasting.connections.pusher.key')),
                'secret' => !empty(config('broadcasting.connections.pusher.secret')),
                'app_id' => !empty(config('broadcasting.connections.pusher.app_id')),
                'cluster' => config('broadcasting.connections.pusher.options.cluster')
            ]
        ];
    }
}
