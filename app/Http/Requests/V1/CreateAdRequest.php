<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Helpers\S3UploadHelper;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Requests\V1\CreateAdRequest;
use App\Http\Requests\V1\UpdateAdRequest;
use App\Http\Resources\V1\AdResource;
use App\Helpers\AdHelper;
use App\Models\SocialCircle;

/**
 * @OA\Tag(
 *     name="Advertising",
 *     description="Advertisement management operations"
 * )
 */
class AdController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/ads/dashboard",
     *     summary="Get advertising dashboard data",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"today", "week", "month", "all"})),
     *     @OA\Response(response=200, description="Dashboard data retrieved successfully")
     * )
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            $period = $request->input('period', 'month');

            // Define date range based on period
            $dateFrom = match($period) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                default => null
            };

            $query = Ad::where('user_id', $user->id)->where('deleted_flag', 'N');

            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }

            // Get summary statistics using AdHelper
            $stats = AdHelper::getUserAdStats($user->id);

            // Get recent ads
            $recentAds = $query->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get performance by social circle using AdHelper
            $socialCirclePerformance = [];
            $userSocialCircles = $user->socialCircles;

            if ($userSocialCircles && $userSocialCircles->count() > 0) {
                foreach ($userSocialCircles as $circle) {
                    $circleStats = AdHelper::getAdPerformanceBySocialCircle($circle->id, $dateFrom);
                    if ($circleStats && $circleStats->total_ads > 0) {
                        $socialCirclePerformance[] = [
                            'social_circle' => [
                                'id' => $circle->id,
                                'name' => $circle->name,
                                'color' => $circle->color ?? '#3498db'
                            ],
                            'stats' => [
                                'total_ads' => (int) $circleStats->total_ads,
                                'total_impressions' => (int) $circleStats->total_impressions,
                                'total_clicks' => (int) $circleStats->total_clicks,
                                'total_conversions' => (int) $circleStats->total_conversions,
                                'total_spent' => (float) $circleStats->total_spent,
                                'avg_ctr' => round((float) $circleStats->avg_ctr, 2)
                            ]
                        ];
                    }
                }
            }

            return $this->sendResponse('Dashboard data retrieved successfully', [
                'summary' => $stats,
                'recent_ads' => AdResource::collection($recentAds),
                'social_circle_performance' => $socialCirclePerformance,
                'period' => $period
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve dashboard data', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ads",
     *     summary="Get user's advertisements with filtering",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Advertisements retrieved successfully")
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 10);

            $query = Ad::where('user_id', $user->id)
                ->where('deleted_flag', 'N');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('start_date')) {
                $query->where('start_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('end_date', '<=', $request->end_date);
            }

            // Order by
            $query->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'));

            // Get paginated results
            $ads = $query->paginate($perPage);

            return $this->sendResponse('Advertisements retrieved successfully', [
                'ads' => AdResource::collection($ads->items()),
                'pagination' => [
                    'total' => $ads->total(),
                    'per_page' => $ads->perPage(),
                    'current_page' => $ads->currentPage(),
                    'last_page' => $ads->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisements', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ads",
     *     summary="Create a new advertisement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=201, description="Advertisement created successfully")
     * )
     */
    public function store(CreateAdRequest $request)
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            // Check if user can create more ads
            if (!AdHelper::canUserCreateAd($user->id)) {
                return $this->sendError('You have reached the maximum limit of active advertisements', null, 403);
            }

            // Handle media files if provided
            $mediaFiles = [];
            if ($request->hasFile('media_files')) {
                foreach ($request->file('media_files') as $file) {
                    $uploadResult = S3UploadHelper::uploadFile($file, 'ads');
                    $mediaFiles[] = [
                        'path' => $uploadResult['path'],
                        'url' => $uploadResult['url'],
                        'filename' => $uploadResult['filename'],
                        'type' => $file->getClientMimeType(),
                        'size' => $file->getSize()
                    ];
                }
            }

            // Calculate estimated daily spend
            $estimatedDailySpend = AdHelper::calculateEstimatedDailySpend(
                $validated['budget'],
                $validated['start_date'],
                $validated['end_date']
            );

            // Create ad
            $ad = Ad::create([
                'user_id' => $user->id,
                'ad_name' => $validated['ad_name'],
                'type' => $validated['type'],
                'description' => $validated['description'],
                'media_files' => $mediaFiles,
                'call_to_action' => $validated['call_to_action'],
                'destination_url' => $validated['destination_url'],
                'ad_placement' => $validated['ad_placement'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'target_audience' => $validated['target_audience'],
                'budget' => $validated['budget'],
                'daily_budget' => $validated['daily_budget'] ?? $estimatedDailySpend,
                'target_impressions' => $validated['target_impressions'],
                'current_impressions' => 0,
                'clicks' => 0,
                'conversions' => 0,
                'cost_per_click' => 0,
                'total_spent' => 0,
                'status' => 'pending_review',
                'admin_status' => 'pending',
                'created_by' => $user->id,
                'deleted_flag' => 'N'
            ]);

            return $this->sendResponse('Advertisement created successfully', new AdResource($ad), 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to create advertisement', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ads/{id}",
     *     summary="Get advertisement details",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Advertisement details retrieved successfully")
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            $ad = Ad::where('id', $id)
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            return $this->sendResponse('Advertisement details retrieved successfully', new AdResource($ad));
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisement details', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/ads/{id}",
     *     summary="Update an advertisement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Advertisement updated successfully")
     * )
     */
    public function update(UpdateAdRequest $request, $id)
    {
        try {
            $user = $request->user();

            $ad = Ad::where('id', $id)
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            // Check if ad can be edited
            if (!$ad->can_be_edited) {
                return $this->sendError('This advertisement cannot be edited in its current state', null, 403);
            }

            $validated = $request->validated();

            // Handle media files if provided
            if ($request->hasFile('media_files')) {
                $mediaFiles = [];
                foreach ($request->file('media_files') as $file) {
                    $uploadResult = S3UploadHelper::uploadFile($file, 'ads');
                    $mediaFiles[] = [
                        'path' => $uploadResult['path'],
                        'url' => $uploadResult['url'],
                        'filename' => $uploadResult['filename'],
                        'type' => $file->getClientMimeType(),
                        'size' => $file->getSize()
                    ];
                }
                $validated['media_files'] = $mediaFiles;
            }

            // If ad was rejected and is being resubmitted, change status
            if ($ad->admin_status === 'rejected') {
                $validated['status'] = 'pending_review';
                $validated['admin_status'] = 'pending';
                $validated['admin_comments'] = null;
            }

            // Update ad
            $ad->update($validated);

            return $this->sendResponse('Advertisement updated successfully', new AdResource($ad->fresh()));
        } catch (\Exception $e) {
            return $this->sendError('Failed to update advertisement', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/ads/{id}",
     *     summary="Delete an advertisement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Advertisement deleted successfully")
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            $ad = Ad::where('id', $id)
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            // Check if ad can be deleted
            if (!$ad->canBeDeleted()) {
                return $this->sendError('This advertisement cannot be deleted in its current state', null, 403);
            }

            // Soft delete
            $ad->update([
                'deleted_flag' => 'Y',
                'updated_by' => $user->id
            ]);

            return $this->sendResponse('Advertisement deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete advertisement', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ads/{id}/pause",
     *     summary="Pause an advertisement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Advertisement paused successfully")
     * )
     */
    public function pause(Request $request, $id)
    {
        try {
            $user = $request->user();

            $ad = Ad::where('id', $id)
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            if (!$ad->canBePaused()) {
                return $this->sendError('This advertisement cannot be paused in its current state', null, 403);
            }

            AdHelper::pauseAd($ad->id);

            return $this->sendResponse('Advertisement paused successfully', new AdResource($ad->fresh()));
        } catch (\Exception $e) {
            return $this->sendError('Failed to pause advertisement', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ads/{id}/resume",
     *     summary="Resume a paused advertisement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Advertisement resumed successfully")
     * )
     */
    public function resume(Request $request, $id)
    {
        try {
            $user = $request->user();

            $ad = Ad::where('id', $id)
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            if ($ad->status !== 'paused') {
                return $this->sendError('Only paused advertisements can be resumed', null, 403);
            }

            AdHelper::resumeAd($ad->id);

            return $this->sendResponse('Advertisement resumed successfully', new AdResource($ad->fresh()));
        } catch (\Exception $e) {
            return $this->sendError('Failed to resume advertisement', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ads/{id}/stop",
     *     summary="Stop an advertisement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Advertisement stopped successfully")
     * )
     */
    public function stop(Request $request, $id)
    {
        try {
            $user = $request->user();

            $ad = Ad::where('id', $id)
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            if (!$ad->canBeStopped()) {
                return $this->sendError('This advertisement cannot be stopped in its current state', null, 403);
            }

            AdHelper::stopAd($ad->id);

            return $this->sendResponse('Advertisement stopped successfully', new AdResource($ad->fresh()));
        } catch (\Exception $e) {
            return $this->sendError('Failed to stop advertisement', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ads/{id}/analytics",
     *     summary="Get advertisement analytics",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"today", "week", "month", "all"})),
     *     @OA\Response(response=200, description="Advertisement analytics retrieved successfully")
     * )
     */
    public function analytics(Request $request, $id)
    {
        try {
            $user = $request->user();
            $period = $request->input('period', 'month');

            $ad = Ad::where('id', $id)
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            // Get analytics data using AdHelper
            $analytics = AdHelper::getAdAnalytics($ad->id, $period);

            return $this->sendResponse('Advertisement analytics retrieved successfully', $analytics);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisement analytics', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ads/feed",
     *     summary="Get ads for user feed",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="social_circle_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=2)),
     *     @OA\Response(response=200, description="Ads retrieved successfully")
     * )
     */
    public function getAdsForFeed(Request $request)
    {
        try {
            $user = $request->user();
            $socialCircleId = $request->input('social_circle_id');
            $limit = $request->input('limit', 2);

            // Get ads using AdHelper
            $ads = AdHelper::getAdsForUserFeed($user->id, $socialCircleId, $limit);

            return $this->sendResponse('Ads retrieved successfully', AdResource::collection($ads));
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve ads', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ads/social-circles",
     *     summary="Get social circles available for ad placement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Social circles retrieved successfully")
     * )
     */
    public function getAvailableSocialCircles(Request $request)
    {
        try {
            $socialCircles = SocialCircle::active()->ordered()->get();

            $formattedCircles = $socialCircles->map(function ($circle) {
                return [
                    'id' => $circle->id,
                    'name' => $circle->name,
                    'description' => $circle->description,
                    'color' => $circle->color ?? '#3498db',
                    'icon' => $circle->icon ?? null,
                    'logo_url' => $circle->logo_full_url ?? null,
                    'is_default' => $circle->is_default ?? false,
                    'is_private' => $circle->is_private ?? false
                ];
            });

            return $this->sendResponse('Social circles retrieved successfully', $formattedCircles);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve social circles', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ads/{id}/track/click",
     *     summary="Track ad click",
     *     tags={"Advertising"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Click tracked successfully")
     * )
     */
    public function trackClick(Request $request, $id)
    {
        try {
            $ad = Ad::where('id', $id)
                ->where('status', 'active')
                ->where('admin_status', 'approved')
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return $this->sendError('Advertisement not found or not active', null, 404);
            }

            // Track click using AdHelper
            AdHelper::trackAdClick($ad->id, $request->ip(), $request->userAgent());

            return $this->sendResponse('Click tracked successfully', [
                'destination_url' => $ad->destination_url
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to track click', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ads/{id}/track/impression",
     *     summary="Track ad impression",
     *     tags={"Advertising"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Impression tracked successfully")
     * )
     */
    public function trackImpression(Request $request, $id)
    {
        try {
            $ad = Ad::where('id', $id)
                ->where('status', 'active')
                ->where('admin_status', 'approved')
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return $this->sendError('Advertisement not found or not active', null, 404);
            }

            // Track impression using AdHelper
            AdHelper::trackAdImpression($ad->id, $request->ip(), $request->userAgent());

            return $this->sendResponse('Impression tracked successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to track impression', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ads/{id}/track/conversion",
     *     summary="Track ad conversion",
     *     tags={"Advertising"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Conversion tracked successfully")
     * )
     */
    public function trackConversion(Request $request, $id)
    {
        try {
            $ad = Ad::where('id', $id)
                ->where('status', 'active')
                ->where('admin_status', 'approved')
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return $this->sendError('Advertisement not found or not active', null, 404);
            }

            // Track conversion using AdHelper
            AdHelper::trackAdConversion($ad->id, $request->ip(), $request->userAgent());

            return $this->sendResponse('Conversion tracked successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to track conversion', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ads/stats",
     *     summary="Get advertising statistics",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Statistics retrieved successfully")
     * )
     */
    public function getStats(Request $request)
    {
        try {
            $user = $request->user();

            $stats = AdHelper::getUserAdStats($user->id);

            return $this->sendResponse('Statistics retrieved successfully', $stats);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve statistics', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ads/export",
     *     summary="Export ads data to Excel",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="format", in="query", @OA\Schema(type="string", enum={"excel", "pdf"})),
     *     @OA\Response(response=200, description="Data exported successfully")
     * )
     */
    public function export(Request $request)
    {
        try {
            $user = $request->user();
            $format = $request->input('format', 'excel');

            $ads = Ad::where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->get();

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('exports.ads-pdf', compact('ads', 'user'));
                return $pdf->download('ads-report-' . date('Y-m-d') . '.pdf');
            } else {
                return Excel::download(new \App\Exports\AdsExport($ads), 'ads-report-' . date('Y-m-d') . '.xlsx');
            }
        } catch (\Exception $e) {
            return $this->sendError('Failed to export data', $e->getMessage(), 500);
        }
    }
}
