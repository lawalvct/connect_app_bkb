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

            // Get summary statistics
            $stats = $query->selectRaw('
                COUNT(*) as total_ads,
                COUNT(CASE WHEN status = "active" THEN 1 END) as active_ads,
                COUNT(CASE WHEN status = "pending_review" THEN 1 END) as pending_ads,
                COUNT(CASE WHEN admin_status = "rejected" THEN 1 END) as rejected_ads,
                SUM(current_impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                SUM(total_spent) as total_spent,
                SUM(budget) as total_budget,
                AVG(CASE WHEN current_impressions > 0 THEN (clicks / current_impressions) * 100 ELSE 0 END) as avg_ctr
            ')->first();

            // Get recent ads
            $recentAds = $query->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Manually load social circles for each ad
            foreach ($recentAds as $ad) {
                if (!empty($ad->ad_placement)) {
                    $ad->social_circles = SocialCircle::whereIn('id', $ad->ad_placement)->get();
                } else {
                    $ad->social_circles = collect();
                }
            }

            // Get performance by social circle
            $socialCirclePerformance = [];
            $userSocialCircles = $user->socialCircles;

            if ($userSocialCircles && $userSocialCircles->count() > 0) {
                foreach ($userSocialCircles as $circle) {
                    $circleStats = $this->getAdPerformanceBySocialCircle($circle->id, $dateFrom);
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

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'summary' => [
                        'total_ads' => (int) $stats->total_ads,
                        'active_ads' => (int) $stats->active_ads,
                        'pending_ads' => (int) $stats->pending_ads,
                        'rejected_ads' => (int) $stats->rejected_ads,
                        'total_impressions' => (int) $stats->total_impressions,
                        'total_clicks' => (int) $stats->total_clicks,
                        'total_conversions' => (int) $stats->total_conversions,
                        'total_spent' => (float) $stats->total_spent,
                        'total_budget' => (float) $stats->total_budget,
                        'avg_ctr' => round((float) $stats->avg_ctr, 2),
                        'budget_utilization' => $stats->total_budget > 0 ?
                            round((($stats->total_spent / $stats->total_budget) * 100), 2) : 0
                    ],
                    'recent_ads' => $recentAds->map(function ($ad) {
                        return [
                            'id' => $ad->id,
                            'ad_name' => $ad->ad_name,
                            'type' => $ad->type,
                            'status' => $ad->status,
                            'admin_status' => $ad->admin_status,
                            'budget' => (float) $ad->budget,
                            'total_spent' => (float) $ad->total_spent,
                            'current_impressions' => (int) $ad->current_impressions,
                            'clicks' => (int) $ad->clicks,
                            'ctr' => $ad->ctr,
                            'progress_percentage' => $ad->progress_percentage,
                            'social_circles' => $ad->social_circles->map(function ($circle) {
                                return [
                                    'id' => $circle->id,
                                    'name' => $circle->name,
                                    'color' => $circle->color ?? '#3498db'
                                ];
                            }),
                         'created_at' => $ad->created_at ? $ad->created_at->toISOString() : null
                        ];
                    }),
                    'social_circle_performance' => $socialCirclePerformance,
                    'period' => $period
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to get ad performance by social circle
     */
    private function getAdPerformanceBySocialCircle($socialCircleId, $dateFrom = null)
    {
        try {
            $query = Ad::whereJsonContains('ad_placement', $socialCircleId)
                ->where('deleted_flag', 'N');

            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }

            return $query->selectRaw('
                COUNT(*) as total_ads,
                SUM(current_impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                SUM(total_spent) as total_spent,
                AVG(CASE WHEN current_impressions > 0 THEN (clicks / current_impressions) * 100 ELSE 0 END) as avg_ctr
            ')->first();
        } catch (\Exception $e) {
            \Log::error('Error getting ad performance by social circle: ' . $e->getMessage());
            return null;
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

            // Manually load social circles for each ad
            foreach ($ads as $ad) {
                if (!empty($ad->ad_placement)) {
                    $ad->social_circles = SocialCircle::whereIn('id', $ad->ad_placement)->get();
                } else {
                    $ad->social_circles = collect();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Advertisements retrieved successfully',
                'data' => [
                    'ads' => $ads->map(function ($ad) {
                        return [
                            'id' => $ad->id,
                            'ad_name' => $ad->ad_name,
                            'type' => $ad->type,
                            'description' => $ad->description,
                            'media_files' => $ad->media_files,
                            'call_to_action' => $ad->call_to_action,
                            'destination_url' => $ad->destination_url,
                            'start_date' => $ad->start_date->format('Y-m-d'),
                            'end_date' => $ad->end_date->format('Y-m-d'),
                            'budget' => (float) $ad->budget,
                            'daily_budget' => (float) $ad->daily_budget,
                            'target_impressions' => (int) $ad->target_impressions,
                            'current_impressions' => (int) $ad->current_impressions,
                            'clicks' => (int) $ad->clicks,
                            'conversions' => (int) $ad->conversions,
                            'total_spent' => (float) $ad->total_spent,
                            'status' => $ad->status,
                            'admin_status' => $ad->admin_status,
                            'admin_comments' => $ad->admin_comments,
                            'progress_percentage' => $ad->progress_percentage,
                            'ctr' => $ad->ctr,
                            'days_remaining' => $ad->days_remaining,
                            'social_circles' => $ad->social_circles->map(function ($circle) {
                                return [
                                    'id' => $circle->id,
                                    'name' => $circle->name,
                                    'color' => $circle->color ?? '#3498db'
                                ];
                            }),
                            'created_at' => $ad->created_at->toISOString(),
                            'updated_at' => $ad->updated_at->toISOString()
                        ];
                    }),
                    'pagination' => [
                        'total' => $ads->total(),
                        'per_page' => $ads->perPage(),
                        'current_page' => $ads->currentPage(),
                        'last_page' => $ads->lastPage()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve advertisements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ads",
     *     summary="Create a new advertisement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="ad_name", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="call_to_action", type="string"),
     *             @OA\Property(property="destination_url", type="string"),
     *             @OA\Property(property="ad_placement", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="budget", type="number"),
     *             @OA\Property(property="daily_budget", type="number"),
     *             @OA\Property(property="target_impressions", type="integer"),
     *             @OA\Property(property="target_audience", type="object")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Advertisement created successfully")
     * )
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            // Validate request
            $validated = $request->validate([
                'ad_name' => 'required|string|max:255',
                'type' => 'required|string|in:banner,video,text,carousel',
                'description' => 'required|string',
                'call_to_action' => 'required|string|max:50',
                'destination_url' => 'required|url',
                'ad_placement' => 'required|array|min:1',
                'ad_placement.*' => 'integer|exists:social_circles,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'budget' => 'required|numeric|min:10',
                'daily_budget' => 'required|numeric',
                'target_impressions' => 'required|integer|min:1000',
                'target_audience' => 'required|array',
                'target_audience.age_min' => 'required|integer|min:13|max:100',
                'target_audience.age_max' => 'required|integer|min:13|max:100|gte:target_audience.age_min',
                'target_audience.gender' => 'required|string|in:male,female,all',
                'target_audience.locations' => 'required|array',
                'target_audience.interests' => 'required|array',
                'media_files' => 'nullable|array'
            ]);

            // Handle media files if provided
            $mediaFiles = [];
            if ($request->hasFile('media_files')) {
                foreach ($request->file('media_files') as $file) {
                    $path = $file->store('ads/' . $user->id, 'public');
                    $mediaFiles[] = [
                        'path' => $path,
                        'url' => asset('storage/' . $path),
                        'type' => $file->getClientMimeType(),
                        'size' => $file->getSize()
                    ];
                }
            }

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
                'daily_budget' => $validated['daily_budget'],
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

            // Load social circles
            $ad->social_circles = SocialCircle::whereIn('id', $ad->ad_placement)->get();

            return response()->json([
                'success' => true,
                'message' => 'Advertisement created successfully',
                'data' => [
                    'id' => $ad->id,
                    'ad_name' => $ad->ad_name,
                    'type' => $ad->type,
                    'description' => $ad->description,
                    'media_files' => $ad->media_files,
                    'call_to_action' => $ad->call_to_action,
                    'destination_url' => $ad->destination_url,
                    'start_date' => $ad->start_date->format('Y-m-d'),
                    'end_date' => $ad->end_date->format('Y-m-d'),
                    'budget' => (float) $ad->budget,
                    'daily_budget' => (float) $ad->daily_budget,
                    'target_impressions' => (int) $ad->target_impressions,
                    'status' => $ad->status,
                    'admin_status' => $ad->admin_status,
                    'social_circles' => $ad->social_circles->map(function ($circle) {
                        return [
                            'id' => $circle->id,
                            'name' => $circle->name,
                            'color' => $circle->color ?? '#3498db'
                        ];
                    }),
                    'created_at' => $ad->created_at->toISOString()
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create advertisement: ' . $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found'
                ], 404);
            }

            // Load social circles
            $ad->social_circles = SocialCircle::whereIn('id', $ad->ad_placement ?? [])->get();

            return response()->json([
                'success' => true,
                'message' => 'Advertisement details retrieved successfully',
                'data' => [
                    'id' => $ad->id,
                    'ad_name' => $ad->ad_name,
                    'type' => $ad->type,
                    'description' => $ad->description,
                    'media_files' => $ad->media_files,
                    'call_to_action' => $ad->call_to_action,
                    'destination_url' => $ad->destination_url,
                    'ad_placement' => $ad->ad_placement,
                    'start_date' => $ad->start_date->format('Y-m-d'),
                    'end_date' => $ad->end_date->format('Y-m-d'),
                    'target_audience' => $ad->target_audience,
                    'budget' => (float) $ad->budget,
                    'daily_budget' => (float) $ad->daily_budget,
                    'target_impressions' => (int) $ad->target_impressions,
                    'current_impressions' => (int) $ad->current_impressions,
                    'clicks' => (int) $ad->clicks,
                    'conversions' => (int) $ad->conversions,
                    'cost_per_click' => (float) $ad->cost_per_click,
                    'total_spent' => (float) $ad->total_spent,
                    'status' => $ad->status,
                    'admin_status' => $ad->admin_status,
                    'admin_comments' => $ad->admin_comments,
                    'progress_percentage' => $ad->progress_percentage,
                    'ctr' => $ad->ctr,
                    'days_remaining' => $ad->days_remaining,
                    'is_active' => $ad->is_active,
                    'can_be_edited' => $ad->can_be_edited,
                    'social_circles' => $ad->social_circles->map(function ($circle) {
                        return [
                            'id' => $circle->id,
                            'name' => $circle->name,
                            'color' => $circle->color ?? '#3498db',
                            'icon' => $circle->icon ?? null
                        ];
                    }),
                    'created_at' => $ad->created_at->toISOString(),
                    'updated_at' => $ad->updated_at->toISOString(),
                    'reviewed_at' => $ad->reviewed_at ? $ad->reviewed_at->toISOString() : null,
                    'activated_at' => $ad->activated_at ? $ad->activated_at->toISOString() : null,
                    'paused_at' => $ad->paused_at ? $ad->paused_at->toISOString() : null,
                    'stopped_at' => $ad->stopped_at ? $ad->stopped_at->toISOString() : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve advertisement details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/ads/{id}",
     *     summary="Update an advertisement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="ad_name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="call_to_action", type="string"),
     *             @OA\Property(property="destination_url", type="string"),
     *             @OA\Property(property="ad_placement", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="budget", type="number"),
     *             @OA\Property(property="daily_budget", type="number"),
     *             @OA\Property(property="target_impressions", type="integer"),
     *             @OA\Property(property="target_audience", type="object")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Advertisement updated successfully")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            $ad = Ad::where('id', $id)
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found'
                ], 404);
            }

            // Check if ad can be edited
            if (!$ad->can_be_edited) {
                return response()->json([
                    'success' => false,
                    'message' => 'This advertisement cannot be edited in its current state'
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'ad_name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'call_to_action' => 'sometimes|string|max:50',
                'destination_url' => 'sometimes|url',
                'ad_placement' => 'sometimes|array|min:1',
                'ad_placement.*' => 'integer|exists:social_circles,id',
                'start_date' => 'sometimes|date|after_or_equal:today',
                'end_date' => 'sometimes|date|after:start_date',
                'budget' => 'sometimes|numeric|min:10',
                'daily_budget' => 'sometimes|numeric',
                'target_impressions' => 'sometimes|integer|min:1000',
                'target_audience' => 'sometimes|array',
                'target_audience.age_min' => 'required_with:target_audience|integer|min:13|max:100',
                'target_audience.age_max' => 'required_with:target_audience|integer|min:13|max:100|gte:target_audience.age_min',
                'target_audience.gender' => 'required_with:target_audience|string|in:male,female,all',
                'target_audience.locations' => 'required_with:target_audience|array',
                'target_audience.interests' => 'required_with:target_audience|array',
                'media_files' => 'sometimes|array'
            ]);

            // Handle media files if provided
            if ($request->hasFile('media_files')) {
                $mediaFiles = [];
                foreach ($request->file('media_files') as $file) {
                    $path = $file->store('ads/' . $user->id, 'public');
                    $mediaFiles[] = [
                        'path' => $path,
                        'url' => asset('storage/' . $path),
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

            // Load social circles
            $ad->refresh();
            $ad->social_circles = SocialCircle::whereIn('id', $ad->ad_placement ?? [])->get();

            return response()->json([
                'success' => true,
                'message' => 'Advertisement updated successfully',
                'data' => [
                    'id' => $ad->id,
                    'ad_name' => $ad->ad_name,
                    'type' => $ad->type,
                    'description' => $ad->description,
                    'media_files' => $ad->media_files,
                    'call_to_action' => $ad->call_to_action,
                    'destination_url' => $ad->destination_url,
                    'ad_placement' => $ad->ad_placement,
                    'start_date' => $ad->start_date->format('Y-m-d'),
                    'end_date' => $ad->end_date->format('Y-m-d'),
                    'target_audience' => $ad->target_audience,
                    'budget' => (float) $ad->budget,
                    'daily_budget' => (float) $ad->daily_budget,
                    'target_impressions' => (int) $ad->target_impressions,
                    'status' => $ad->status,
                    'admin_status' => $ad->admin_status,
                    'social_circles' => $ad->social_circles->map(function ($circle) {
                        return [
                            'id' => $circle->id,
                            'name' => $circle->name,
                            'color' => $circle->color ?? '#3498db'
                        ];
                    }),
                    'updated_at' => $ad->updated_at->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update advertisement: ' . $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found'
                ], 404);
            }

            // Check if ad can be deleted
            if (!$ad->canBeDeleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This advertisement cannot be deleted in its current state'
                ], 403);
            }

            // Soft delete
            $ad->update([
                'deleted_flag' => 'Y',
                'updated_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Advertisement deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete advertisement: ' . $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found'
                ], 404);
            }

            if (!$ad->canBePaused()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This advertisement cannot be paused in its current state'
                ], 403);
            }

            $ad->pause();

            return response()->json([
                'success' => true,
                'message' => 'Advertisement paused successfully',
                'data' => [
                    'id' => $ad->id,
                    'status' => $ad->status,
                    'paused_at' => $ad->paused_at->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause advertisement: ' . $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found'
                ], 404);
            }

            if ($ad->status !== 'paused') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only paused advertisements can be resumed'
                ], 403);
            }

            $ad->resume();

            return response()->json([
                'success' => true,
                'message' => 'Advertisement resumed successfully',
                'data' => [
                    'id' => $ad->id,
                    'status' => $ad->status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume advertisement: ' . $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found'
                ], 404);
            }

            if (!$ad->canBeStopped()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This advertisement cannot be stopped in its current state'
                ], 403);
            }

            $ad->stop();

            return response()->json([
                'success' => true,
                'message' => 'Advertisement stopped successfully',
                'data' => [
                    'id' => $ad->id,
                    'status' => $ad->status,
                    'stopped_at' => $ad->stopped_at->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop advertisement: ' . $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found'
                ], 404);
            }

            // Define date range based on period
            $dateFrom = match($period) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                default => null
            };

            // Get daily analytics data
            $dailyData = $this->getAdDailyAnalytics($ad->id, $dateFrom);

            // Get social circle performance
            $socialCirclePerformance = [];
            if (!empty($ad->ad_placement)) {
                foreach ($ad->ad_placement as $circleId) {
                    $circle = SocialCircle::find($circleId);
                    if ($circle) {
                        // Here you would get actual analytics per social circle
                        // This is a placeholder - in a real app, you'd have a table tracking impressions/clicks per social circle
                        $socialCirclePerformance[] = [
                            'social_circle' => [
                                'id' => $circle->id,
                                'name' => $circle->name,
                                'color' => $circle->color ?? '#3498db'
                            ],
                            'stats' => [
                                'impressions' => rand(100, 1000), // Placeholder
                                'clicks' => rand(10, 100),        // Placeholder
                                'ctr' => rand(1, 10) . '%'        // Placeholder
                            ]
                        ];
                    }
                }
            }

            // Get audience demographics
            // This would be real data in a production app
            $demographics = [
                'age_groups' => [
                    ['range' => '18-24', 'percentage' => 25],
                    ['range' => '25-34', 'percentage' => 40],
                    ['range' => '35-44', 'percentage' => 20],
                    ['range' => '45-54', 'percentage' => 10],
                    ['range' => '55+', 'percentage' => 5]
                ],
                'gender' => [
                    ['type' => 'male', 'percentage' => 55],
                    ['type' => 'female', 'percentage' => 45]
                ],
                'locations' => [
                    ['name' => 'United States', 'percentage' => 40],
                    ['name' => 'United Kingdom', 'percentage' => 20],
                    ['name' => 'Canada', 'percentage' => 15],
                    ['name' => 'Australia', 'percentage' => 10],
                    ['name' => 'Other', 'percentage' => 15]
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Advertisement analytics retrieved successfully',
                'data' => [
                    'summary' => [
                        'impressions' => (int) $ad->current_impressions,
                        'clicks' => (int) $ad->clicks,
                        'conversions' => (int) $ad->conversions,
                        'ctr' => $ad->ctr,
                        'total_spent' => (float) $ad->total_spent,
                        'budget_utilization' => $ad->budget > 0 ?
                            round((($ad->total_spent / $ad->budget) * 100), 2) : 0,
                        'progress_percentage' => $ad->progress_percentage,
                        'days_remaining' => $ad->days_remaining
                    ],
                    'daily_data' => $dailyData,
                    'social_circle_performance' => $socialCirclePerformance,
                    'demographics' => $demographics,
                    'period' => $period
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve advertisement analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to get daily analytics for an ad
     */
    private function getAdDailyAnalytics($adId, $dateFrom = null)
    {
        // In a real app, this would query an ad_analytics table
        // For this example, we'll generate placeholder data

        $result = [];
        $startDate = $dateFrom ?? now()->subDays(30);
        $endDate = now();

        for ($date = clone $startDate; $date <= $endDate; $date->addDay()) {
            $result[] = [
                'date' => $date->format('Y-m-d'),
                'impressions' => rand(50, 500),
                'clicks' => rand(5, 50),
                'conversions' => rand(0, 5),
                'spent' => round(rand(5, 50) / 10, 2)
            ];
        }

        return $result;
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
            $user = $request->user();

            // Get all active social circles
            $socialCircles = SocialCircle::active()->ordered()->get();

            return response()->json([
                'success' => true,
                'message' => 'Social circles retrieved successfully',
                'data' => $socialCircles->map(function ($circle) {
                    return [
                        'id' => $circle->id,
                        'name' => $circle->name,
                        'description' => $circle->description,
                        'color' => $circle->color ?? '#3498db',
                        'icon' => $circle->icon ?? null,
                        'logo_url' => $circle->logo_full_url,
                        'is_default' => $circle->is_default,
                        'is_private' => $circle->is_private
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve social circles: ' . $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found or not active'
                ], 404);
            }

            // Increment clicks
            $ad->increment('clicks');

            // Update cost_per_click and total_spent
            if ($ad->current_impressions > 0) {
                $ad->update([
                    'cost_per_click' => $ad->budget / $ad->clicks,
                    'total_spent' => min($ad->budget, $ad->clicks * ($ad->budget / $ad->target_impressions))
                ]);
            }

            // In a real app, you would also log the click with more details
            // such as user agent, IP, referrer, etc.

            return response()->json([
                'success' => true,
                'message' => 'Click tracked successfully',
                'data' => [
                    'destination_url' => $ad->destination_url
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track click: ' . $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found or not active'
                ], 404);
            }

            // Increment impressions
            $ad->increment('current_impressions');

            // Update total_spent based on impressions
            $ad->update([
                'total_spent' => min($ad->budget, $ad->current_impressions * ($ad->budget / $ad->target_impressions))
            ]);

            // In a real app, you would also log the impression with more details

            return response()->json([
                'success' => true,
                'message' => 'Impression tracked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track impression: ' . $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found or not active'
                ], 404);
            }

            // Increment conversions
            $ad->increment('conversions');

            // In a real app, you would also log the conversion with more details

            return response()->json([
                'success' => true,
                'message' => 'Conversion tracked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track conversion: ' . $e->getMessage()
            ], 500);
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

            $query = Ad::where('status', 'active')
                ->where('admin_status', 'approved')
                ->where('deleted_flag', 'N')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now());

            // Filter by social circle if provided
            if ($socialCircleId) {
                $query->whereJsonContains('ad_placement', $socialCircleId);
            } else {
                // Get user's social circles
                $userSocialCircleIds = $user->socialCircles()->pluck('social_id')->toArray();
                if (!empty($userSocialCircleIds)) {
                    $query->where(function ($q) use ($userSocialCircleIds) {
                        foreach ($userSocialCircleIds as $circleId) {
                            $q->orWhereJsonContains('ad_placement', $circleId);
                        }
                    });
                }
            }

            // Get random ads up to the limit
            $ads = $query->inRandomOrder()->limit($limit)->get();

            // Load social circles for each ad
            foreach ($ads as $ad) {
                $ad->social_circles = SocialCircle::whereIn('id', $ad->ad_placement ?? [])->get();
            }

            return response()->json([
                'success' => true,
                'message' => 'Ads retrieved successfully',
                'data' => $ads->map(function ($ad) {
                    return [
                        'id' => $ad->id,
                        'ad_name' => $ad->ad_name,
                        'type' => $ad->type,
                        'description' => $ad->description,
                        'media_files' => $ad->media_files,
                        'call_to_action' => $ad->call_to_action,
                        'destination_url' => $ad->destination_url,
                        'social_circles' => $ad->social_circles->map(function ($circle) {
                            return [
                                'id' => $circle->id,
                                'name' => $circle->name,
                                'color' => $circle->color ?? '#3498db'
                            ];
                        })
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ads: ' . $e->getMessage()
            ], 500);
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

            $stats = [
                'total_ads' => Ad::where('user_id', $user->id)->where('deleted_flag', 'N')->count(),
                'active_ads' => Ad::where('user_id', $user->id)->where('status', 'active')->where('deleted_flag', 'N')->count(),
                'pending_ads' => Ad::where('user_id', $user->id)->where('admin_status', 'pending')->where('deleted_flag', 'N')->count(),
                'rejected_ads' => Ad::where('user_id', $user->id)->where('admin_status', 'rejected')->where('deleted_flag', 'N')->count(),
                'total_impressions' => Ad::where('user_id', $user->id)->where('deleted_flag', 'N')->sum('current_impressions'),
                'total_clicks' => Ad::where('user_id', $user->id)->where('deleted_flag', 'N')->sum('clicks'),
                'total_conversions' => Ad::where('user_id', $user->id)->where('deleted_flag', 'N')->sum('conversions'),
                'total_spent' => (float) Ad::where('user_id', $user->id)->where('deleted_flag', 'N')->sum('total_spent'),
                'total_budget' => (float) Ad::where('user_id', $user->id)->where('deleted_flag', 'N')->sum('budget')
            ];

            // Calculate average CTR
            if ($stats['total_impressions'] > 0) {
                $stats['avg_ctr'] = round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2);
            } else {
                $stats['avg_ctr'] = 0;
            }

            // Calculate budget utilization
            if ($stats['total_budget'] > 0) {
                $stats['budget_utilization'] = round(($stats['total_spent'] / $stats['total_budget']) * 100, 2);
            } else {
                $stats['budget_utilization'] = 0;
            }

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
 * @OA\Get(
 *     path="/api/v1/ads/export",
 *     summary="Export ads data",
 *     tags={"Advertising"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="format", in="query", @OA\Schema(type="string", enum={"excel", "pdf"})),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active", "paused", "completed", "all"})),
 *     @OA\Response(response=200, description="Data exported successfully")
 * )
 */
public function export(Request $request)
{
    try {
        $user = $request->user();
        $format = $request->input('format', 'excel');
        $status = $request->input('status', 'all');

        // Build query
        $query = Ad::where('user_id', $user->id)
            ->where('deleted_flag', 'N');

        // Filter by status if provided
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $ads = $query->get();

        // Export date for the report
        $export_date = now()->format('Y-m-d H:i:s');

        if ($format === 'pdf') {
            // Generate PDF using the ads-pdf.blade.php view
            $pdf = Pdf::loadView('exports.ads-pdf', [
                'ads' => $ads,
                'user' => $user,
                'export_date' => $export_date,
                'status' => $status
            ]);

            return $pdf->download('ads-report-' . date('Y-m-d') . '.pdf');
        } else {
            // For Excel export
            return Excel::download(
                new \App\Exports\AdsExport($ads, $user),
                'ads-report-' . date('Y-m-d') . '.xlsx'
            );
        }
    } catch (\Exception $e) {
        return $this->sendError('Failed to export data', $e->getMessage(), 500);
    }
}

/**
 * @OA\Post(
 *     path="/api/v1/ads/preview",
 *     summary="Preview an advertisement before creating",
 *     tags={"Advertising"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="ad_name", type="string"),
 *             @OA\Property(property="type", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="call_to_action", type="string"),
 *             @OA\Property(property="destination_url", type="string"),
 *             @OA\Property(property="ad_placement", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="media_files", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="target_audience", type="object")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Advertisement preview generated successfully")
 * )
 */
public function preview(Request $request)
{
    try {
        $user = $request->user();

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'ad_name' => 'required|string|max:255',
            'type' => 'required|string|in:banner,video,text,carousel',
            'description' => 'required|string',
            'call_to_action' => 'required|string|max:50',
            'destination_url' => 'required|url',
            'ad_placement' => 'required|array|min:1',
            'ad_placement.*' => 'integer|exists:social_circles,id',
            'media_files' => 'nullable|array',
            'target_audience' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        // Get social circles for preview
        $socialCircles = [];
        if (!empty($request->ad_placement)) {
            $socialCircles = SocialCircle::whereIn('id', $request->ad_placement)->get();
        }

        // Create a temporary ad object for preview
        $previewAd = new Ad([
            'ad_name' => $request->ad_name,
            'type' => $request->type,
            'description' => $request->description,
            'call_to_action' => $request->call_to_action,
            'destination_url' => $request->destination_url,
            'ad_placement' => $request->ad_placement,
            'media_files' => $request->media_files,
            'target_audience' => $request->target_audience,
        ]);

        // Generate preview data
        $previewData = [
            'ad' => [
                'ad_name' => $previewAd->ad_name,
                'type' => $previewAd->type,
                'description' => $previewAd->description,
                'call_to_action' => $previewAd->call_to_action,
                'destination_url' => $previewAd->destination_url,
                'media_files' => $previewAd->media_files,
                'target_audience' => $previewAd->target_audience,
            ],
            'social_circles' => $socialCircles->map(function ($circle) {
                return [
                    'id' => $circle->id,
                    'name' => $circle->name,
                    'color' => $circle->color ?? '#3498db',
                    'icon' => $circle->icon ?? null
                ];
            }),
            'preview_timestamp' => now()->toISOString(),
            'estimated_reach' => AdHelper::estimateAdReach($request->ad_placement, $request->target_audience),
        ];

        return $this->sendResponse('Advertisement preview generated successfully', $previewData);
    } catch (\Exception $e) {
        return $this->sendError('Failed to generate advertisement preview', $e->getMessage(), 500);
    }
}



/**
 * @OA\Get(
 *     path="/api/v1/ads/social-circle/{id}",
 *     summary="Get ads for a specific social circle",
 *     tags={"Advertising"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=3)),
 *     @OA\Response(response=200, description="Ads retrieved successfully")
 * )
 */
public function getAdsForSocialCircle(Request $request, $socialCircleId)
{
    try {
        $limit = $request->input('limit', 3);

        // Get ads for the specified social circle using AdHelper
        $ads = AdHelper::getAdsForSocialCircle($socialCircleId, $limit);

        // Load social circles for each ad
        foreach ($ads as $ad) {
            $ad->social_circles = SocialCircle::whereIn('id', $ad->ad_placement ?? [])->get();
        }

        return $this->sendResponse('Ads retrieved successfully', [
            'ads' => $ads->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'ad_name' => $ad->ad_name,
                    'type' => $ad->type,
                    'description' => $ad->description,
                    'media_files' => $ad->media_files,
                    'call_to_action' => $ad->call_to_action,
                    'destination_url' => $ad->destination_url,
                    'social_circles' => $ad->social_circles->map(function ($circle) {
                        return [
                            'id' => $circle->id,
                            'name' => $circle->name,
                            'color' => $circle->color ?? '#3498db'
                        ];
                    })
                ];
            })
        ]);
    } catch (\Exception $e) {
        return $this->sendError('Failed to retrieve ads', $e->getMessage(), 500);
    }
}

/**
 * @OA\Post(
 *     path="/api/v1/ads/social-circles",
 *     summary="Get ads for multiple social circles",
 *     tags={"Advertising"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="social_circle_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="limit", type="integer", default=5)
 *         )
 *     ),
 *     @OA\Response(response=200, description="Ads retrieved successfully")
 * )
 */
public function getAdsForSocialCircles(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'social_circle_ids' => 'required|array',
            'social_circle_ids.*' => 'integer|exists:social_circles,id',
            'limit' => 'nullable|integer|min:1|max:10'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $socialCircleIds = $request->input('social_circle_ids', []);
        $limit = $request->input('limit', 5);

        // Get ads for the specified social circles using AdHelper
        $ads = AdHelper::getAdsForSocialCircles($socialCircleIds, $limit);

        // Load social circles for each ad
        foreach ($ads as $ad) {
            $ad->social_circles = SocialCircle::whereIn('id', $ad->ad_placement ?? [])->get();
        }

        return $this->sendResponse('Ads retrieved successfully', [
            'ads' => $ads->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'ad_name' => $ad->ad_name,
                    'type' => $ad->type,
                    'description' => $ad->description,
                    'media_files' => $ad->media_files,
                    'call_to_action' => $ad->call_to_action,
                    'destination_url' => $ad->destination_url,
                    'social_circles' => $ad->social_circles->map(function ($circle) {
                        return [
                            'id' => $circle->id,
                            'name' => $circle->name,
                            'color' => $circle->color ?? '#3498db'
                        ];
                    })
                ];
            })
        ]);
    } catch (\Exception $e) {
        return $this->sendError('Failed to retrieve ads', $e->getMessage(), 500);
    }
}





}
