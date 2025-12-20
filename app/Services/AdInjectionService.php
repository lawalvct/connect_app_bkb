<?php

namespace App\Services;

use App\Models\Stream;
use App\Models\Advertisement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdInjectionService
{
    /**
     * Ad break configuration
     */
    private const AD_INTERVAL_MINUTES = 10; // Show ad every 10 minutes
    private const AD_DURATION_SECONDS = 30; // 30 second ads

    /**
     * Check if it's time to show an ad
     */
    public function shouldShowAd(Stream $stream): bool
    {
        if (!$stream->started_at) {
            return false;
        }

        // Get last ad time
        $lastAdTime = $stream->last_ad_shown_at ?? $stream->started_at;

        // Check if enough time has passed
        $minutesSinceLastAd = Carbon::now()->diffInMinutes($lastAdTime);

        return $minutesSinceLastAd >= self::AD_INTERVAL_MINUTES;
    }

    /**
     * Get next ad to show
     */
    public function getNextAd(Stream $stream)
    {
        // Get active ads that haven't been shown recently
        $ad = Advertisement::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->whereDoesntHave('streams', function ($query) use ($stream) {
                $query->where('stream_id', $stream->id)
                    ->where('shown_at', '>', now()->subHours(1));
            })
            ->inRandomOrder()
            ->first();

        if ($ad) {
            // Log that ad was shown
            $ad->streams()->attach($stream->id, [
                'shown_at' => now(),
                'stream_position' => $stream->current_position_seconds ?? 0
            ]);

            // Update stream's last ad time
            $stream->update([
                'last_ad_shown_at' => now()
            ]);

            Log::info('Ad scheduled for stream', [
                'stream_id' => $stream->id,
                'ad_id' => $ad->id,
                'ad_title' => $ad->title
            ]);
        }

        return $ad;
    }

    /**
     * Trigger ad break for stream
     */
    public function triggerAdBreak(Stream $stream): array
    {
        if (!$this->shouldShowAd($stream)) {
            return [
                'should_show' => false,
                'message' => 'Not time for ad yet'
            ];
        }

        $ad = $this->getNextAd($stream);

        if (!$ad) {
            return [
                'should_show' => false,
                'message' => 'No available ads'
            ];
        }

        // Broadcast ad to viewers
        broadcast(new \App\Events\AdBreakStarted($stream, $ad));

        return [
            'should_show' => true,
            'ad' => [
                'id' => $ad->id,
                'title' => $ad->title,
                'video_url' => $ad->video_url,
                'duration' => $ad->duration_seconds ?? self::AD_DURATION_SECONDS,
                'skip_after' => $ad->skip_after_seconds ?? null,
                'click_url' => $ad->click_url ?? null
            ]
        ];
    }

    /**
     * Manual ad trigger (for broadcaster control)
     */
    public function showAdNow(Stream $stream, ?int $adId = null): array
    {
        if ($adId) {
            $ad = Advertisement::find($adId);
        } else {
            $ad = $this->getNextAd($stream);
        }

        if (!$ad) {
            return [
                'success' => false,
                'message' => 'Ad not found'
            ];
        }

        // Broadcast ad immediately
        broadcast(new \App\Events\AdBreakStarted($stream, $ad));

        return [
            'success' => true,
            'ad' => [
                'id' => $ad->id,
                'title' => $ad->title,
                'video_url' => $ad->video_url,
                'duration' => $ad->duration_seconds
            ]
        ];
    }

    /**
     * Get ad statistics for stream
     */
    public function getAdStats(Stream $stream): array
    {
        $totalAds = $stream->advertisements()->count();
        $recentAds = $stream->advertisements()
            ->wherePivot('shown_at', '>', now()->subHour())
            ->count();

        return [
            'total_ads_shown' => $totalAds,
            'ads_last_hour' => $recentAds,
            'last_ad_at' => $stream->last_ad_shown_at?->toISOString(),
            'next_ad_in' => $this->timeUntilNextAd($stream)
        ];
    }

    /**
     * Calculate time until next ad
     */
    private function timeUntilNextAd(Stream $stream): ?int
    {
        if (!$stream->last_ad_shown_at) {
            return 0;
        }

        $minutesSinceLastAd = Carbon::now()->diffInMinutes($stream->last_ad_shown_at);
        $minutesRemaining = self::AD_INTERVAL_MINUTES - $minutesSinceLastAd;

        return max(0, $minutesRemaining * 60); // Return seconds
    }
}
