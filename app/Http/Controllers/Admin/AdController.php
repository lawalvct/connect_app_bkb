<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stream;
use App\Models\Advertisement;
use App\Services\AdInjectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdController extends Controller
{
    protected $adService;

    public function __construct(AdInjectionService $adService)
    {
        $this->adService = $adService;
    }

    /**
     * Get available ads for manual insertion
     */
    public function getAvailableAds(): JsonResponse
    {
        $ads = Advertisement::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->select('id', 'title', 'thumbnail_url', 'duration_seconds')
            ->get();

        return response()->json([
            'success' => true,
            'ads' => $ads
        ]);
    }

    /**
     * Manually trigger ad break
     */
    public function triggerAdBreak($streamId, Request $request): JsonResponse
    {
        try {
            $stream = Stream::findOrFail($streamId);

            $adId = $request->input('ad_id');
            $result = $this->adService->showAdNow($stream, $adId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger ad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if ad should be shown (for automatic insertion)
     */
    public function checkAdTiming($streamId): JsonResponse
    {
        try {
            $stream = Stream::findOrFail($streamId);

            $shouldShow = $this->adService->shouldShowAd($stream);

            if ($shouldShow) {
                $result = $this->adService->triggerAdBreak($stream);
                return response()->json($result);
            }

            return response()->json([
                'should_show' => false,
                'next_ad_in' => $this->adService->getAdStats($stream)['next_ad_in']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ad statistics for a stream
     */
    public function getStreamAdStats($streamId): JsonResponse
    {
        try {
            $stream = Stream::findOrFail($streamId);
            $stats = $this->adService->getAdStats($stream);

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record ad interaction (view, click, skip)
     */
    public function recordAdInteraction(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ad_id' => 'required|exists:advertisements,id',
                'stream_id' => 'required|exists:streams,id',
                'action' => 'required|in:view,click,skip'
            ]);

            $ad = Advertisement::find($request->ad_id);

            switch ($request->action) {
                case 'view':
                    $ad->recordImpression();
                    break;
                case 'click':
                    $ad->recordClick($request->stream_id);
                    break;
                case 'skip':
                    $ad->recordSkip($request->stream_id);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Interaction recorded'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
