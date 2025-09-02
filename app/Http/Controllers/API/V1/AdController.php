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
use App\Helpers\FileUploadHelper;
use App\Helpers\NombaPyamentHelper;
use App\Models\AdPayment;
use App\Models\SocialCircle;
use App\Models\Setting;

/**
 * @OA\Tag(
 *     name="Advertising",
 *     description="Advertisement management operations"
 * )
 */
class AdController extends BaseController
{

 /** @OA\Get(
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

        // Base query - ONLY for authenticated user's ads
        $query = Ad::where('user_id', $user->id)
            ->where('deleted_flag', 'N');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        // Get summary statistics - ONLY for authenticated user
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

        // Get recent ads - ONLY for authenticated user
        $recentAdsQuery = Ad::where('user_id', $user->id)
            ->where('deleted_flag', 'N');

        if ($dateFrom) {
            $recentAdsQuery->where('created_at', '>=', $dateFrom);
        }

        $recentAds = $recentAdsQuery->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Manually load social circles for each ad
        foreach ($recentAds as $ad) {
            if (!empty($ad->target_social_circles)) {
                $ad->social_circles = SocialCircle::whereIn('id', $ad->target_social_circles)->get();
            } else {
                $ad->social_circles = collect();
            }
        }

        // Get performance by social circle - ONLY for authenticated user's ads
        $socialCirclePerformance = [];
        $userSocialCircles = $user->socialCircles;

        if ($userSocialCircles && $userSocialCircles->count() > 0) {
            foreach ($userSocialCircles as $circle) {
                $circleStats = $this->getAdPerformanceBySocialCircle($circle->id, $dateFrom, $user->id);
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
                    'total_ads' => (int) ($stats->total_ads ?? 0),
                    'active_ads' => (int) ($stats->active_ads ?? 0),
                    'pending_ads' => (int) ($stats->pending_ads ?? 0),
                    'rejected_ads' => (int) ($stats->rejected_ads ?? 0),
                    'total_impressions' => (int) ($stats->total_impressions ?? 0),
                    'total_clicks' => (int) ($stats->total_clicks ?? 0),
                    'total_conversions' => (int) ($stats->total_conversions ?? 0),
                    'total_spent' => (float) ($stats->total_spent ?? 0),

                    'total_budget' => (float) ($stats->total_budget ?? 0),
                    'remaining_budget' => (float) ($stats->total_budget - $stats->total_spent ?? 0),
                    'avg_ctr' => round((float) ($stats->avg_ctr ?? 0), 2),
                    'budget_utilization' => ($stats->total_budget ?? 0) > 0 ?
                        round((($stats->total_spent ?? 0) / ($stats->total_budget ?? 0)) * 100, 2) : 0
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
                'period' => $period,
                'user_id' => $user->id // Add user ID for verification
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Dashboard data retrieval failed', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

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
            $query = Ad::whereJsonContains('target_social_circles', $socialCircleId)
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
     *     @OA\Parameter(name="active", in="query", @OA\Schema(type="boolean", description="Filter by active/inactive status")),
     *     @OA\Parameter(name="ad_name", in="query", @OA\Schema(type="string", description="Search by ad name (partial match)")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date", description="Filter ads starting from this date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date", description="Filter ads ending before this date")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Advertisements retrieved successfully")
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);

            $query = Ad::where('user_id', $user->id)
                ->where('deleted_flag', 'N');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Add support for active/inactive filter (maps to status)
            if ($request->has('active')) {
                if ($request->boolean('active')) {
                    $query->where('status', 'active');
                } else {
                    $query->whereIn('status', ['paused', 'stopped', 'completed', 'draft', 'pending_review', 'rejected']);
                }
            }

            // Add ad name search functionality
            if ($request->has('ad_name') && !empty($request->ad_name)) {
                $query->where('ad_name', 'LIKE', '%' . $request->ad_name . '%');
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
                if (!empty($ad->target_social_circles)) {
                    $ad->social_circles = SocialCircle::whereIn('id', $ad->target_social_circles)->get();
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
     *             @OA\Property(property="target_social_circles", type="array", @OA\Items(type="integer")),
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
 *             @OA\Property(property="target_social_circles", type="array", @OA\Items(type="integer")),
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

        $request->merge([
        'target_impressions' => 10000,
        'target_audience' => [
            'age_min' => 18,
            'age_max' => 65,
            'gender' => 'all',
            'locations' => ['US', 'UK', 'CA'],
            'interests' => ['social', 'networking'],
        ],
        'call_to_action' => 'Shop Now on Connect App',
    ]);

    try {
        $user = $request->user();

        // Get max file upload size from settings table (in KB), default to 20MB (20480 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 20480);

        // Validate request
        $validated = $request->validate([
            'ad_name' => 'required|string|max:255',
            'type' => 'required|string|in:banner,video,text,carousel',
            'description' => 'required|string',
            'call_to_action' => 'required|string|max:50',
            'destination_url' => 'nullable|url',
            'target_social_circles' => 'required|array|min:1',
            'target_social_circles.*' => 'integer|exists:social_circles,id',
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
            'media_files' => 'nullable|array',
            'media_files.*' => "file|mimes:jpeg,png,jpg,gif,mp4,avi,mov|max:{$maxFileSize}", // Dynamic max from settings
            'target_countries' => 'nullable|array',
            'ad_placement' => 'nullable|array',
            'target_countries.*' => 'integer|exists:countries,id',
        ]);

        // Create ad first without media files
        $ad = Ad::create([
            'user_id' => $user->id,
            'ad_name' => $validated['ad_name'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'call_to_action' => $validated['call_to_action'],
            'destination_url' => $validated['destination_url'] ?? null,
            'target_social_circles' => $validated['target_social_circles'],
            'ad_placement' => $validated['ad_placement'] ?? null,
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
            'status' => 'draft',
            'admin_status' => 'pending',
            'created_by' => $user->id,
            'deleted_flag' => 'N'
        ]);

        // Handle media files if provided
        $mediaFiles = [];
        if ($request->hasFile('media_files')) {
            foreach ($request->file('media_files') as $file) {
                // Upload file using FileUploadHelper
                $mediaFile = FileUploadHelper::uploadAdMedia($file, $user->id, $ad->id);
                $mediaFiles[] = $mediaFile;
            }

            // Update ad with media files
            $ad->update(['media_files' => $mediaFiles]);
        }

        // Load social circles
        $ad->social_circles = SocialCircle::whereIn('id', $ad->target_social_circles)->get();

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
        \Log::error('Ad creation failed', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

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
            $ad->social_circles = SocialCircle::whereIn('id', $ad->target_social_circles ?? [])->get();

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
                    'target_social_circles' => $ad->target_social_circles,
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
     *             @OA\Property(property="target_social_circles", type="array", @OA\Items(type="integer")),
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
                'target_social_circles' => 'sometimes|array|min:1',
                'target_social_circles.*' => 'integer|exists:social_circles,id',
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
            $ad->social_circles = SocialCircle::whereIn('id', $ad->target_social_circles ?? [])->get();

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
                    'target_social_circles' => $ad->target_social_circles,
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
            if (!empty($ad->target_social_circles)) {
                foreach ($ad->target_social_circles as $circleId) {
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
                $query->whereJsonContains('target_social_circles', $socialCircleId);
            } else {
                // Get user's social circles
                $userSocialCircleIds = $user->socialCircles()->pluck('social_id')->toArray();
                if (!empty($userSocialCircleIds)) {
                    $query->where(function ($q) use ($userSocialCircleIds) {
                        foreach ($userSocialCircleIds as $circleId) {
                            $q->orWhereJsonContains('target_social_circles', $circleId);
                        }
                    });
                }
            }

            // Get random ads up to the limit
            $ads = $query->inRandomOrder()->limit($limit)->get();

            // Load social circles for each ad
            foreach ($ads as $ad) {
                $ad->social_circles = SocialCircle::whereIn('id', $ad->target_social_circles ?? [])->get();
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
 *             @OA\Property(property="target_social_circles", type="array", @OA\Items(type="integer")),
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
            'target_social_circles' => 'required|array|min:1',
            'target_social_circles.*' => 'integer|exists:social_circles,id',
            'media_files' => 'nullable|array',
            'target_audience' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        // Get social circles for preview
        $socialCircles = [];
        if (!empty($request->target_social_circles)) {
            $socialCircles = SocialCircle::whereIn('id', $request->target_social_circles)->get();
        }

        // Create a temporary ad object for preview
        $previewAd = new Ad([
            'ad_name' => $request->ad_name,
            'type' => $request->type,
            'description' => $request->description,
            'call_to_action' => $request->call_to_action,
            'destination_url' => $request->destination_url,
            'target_social_circles' => $request->target_social_circles,
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
            'estimated_reach' => AdHelper::estimateAdReach($request->target_social_circles, $request->target_audience),
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
            $ad->social_circles = SocialCircle::whereIn('id', $ad->target_social_circles ?? [])->get();
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
            $ad->social_circles = SocialCircle::whereIn('id', $ad->target_social_circles ?? [])->get();
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
 * Initiate payment for an advertisement
 */
public function initiatePayment(Request $request, $id)
{
    try {
        $user = $request->user();

        // Validate request
        $validated = $request->validate([
            'payment_gateway' => 'required|string|in:nomba,stripe',
            'currency' => 'required|string|in:USD,NGN',
            'callback_url' => 'nullable|url' // Optional external callback URL
        ]);

        // Validate gateway-currency combination
        if ($validated['payment_gateway'] === 'nomba' && !in_array($validated['currency'], ['USD', 'NGN'])) {
            return response()->json([
                'success' => false,
                'message' => 'Nomba supports USD and NGN currencies only'
            ], 400);
        }

        if ($validated['payment_gateway'] === 'stripe' && $validated['currency'] !== 'USD') {
            return response()->json([
                'success' => false,
                'message' => 'Stripe supports USD currency only'
            ], 400);
        }

        // Get the ad
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

        // Check if ad can be paid for
        if (!$ad->canBePaidFor()) {
            return response()->json([
                'success' => false,
                'message' => 'This advertisement cannot be paid for in its current state. Current status: ' . $ad->status
            ], 400);
        }

        // Check for existing pending payment
        $existingPayment = AdPayment::where('ad_id', $ad->id)
            ->where('status', 'draft')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'A payment is already pending for this advertisement',
                'data' => [
                    'payment_id' => $existingPayment->id,
                    'payment_link' => $existingPayment->payment_link,
                    'expires_at' => $existingPayment->expires_at,
                    'amount' => $existingPayment->amount,
                    'currency' => $existingPayment->currency
                ]
            ], 400);
        }

        // Calculate payment amount
        $budgetUSD = $ad->budget;
        $currency = strtoupper($validated['currency']);
        $exchangeRate = 1;
        $amount = $budgetUSD;

        if ($currency === 'NGN') {
            $exchangeRate = 1500; // Your specified conversion rate
            $amount = $budgetUSD * $exchangeRate;
        }

        // Create payment record
        $payment = AdPayment::create([
            'user_id' => $user->id,
            'ad_id' => $ad->id,
            'amount' => $amount,
            'currency' => $currency,
            'amount_usd' => $budgetUSD,
            'exchange_rate' => $exchangeRate,
            'payment_gateway' => $validated['payment_gateway'],
            'status' => 'pending',
            'expires_at' => now()->addHours(24), // Payment expires in 24 hours
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Generate internal callback URLs
        $successCallbackUrl = route('ads.payment.success', ['payment' => $payment->id]);
        $cancelCallbackUrl = route('ads.payment.cancel', ['payment' => $payment->id]);

        // Store external callback URL if provided (for redirecting the user after internal processing)
        if (isset($validated['callback_url'])) {
            $payment->update([
                'external_callback_url' => $validated['callback_url']
            ]);
        }

        // Process payment based on gateway
        if ($validated['payment_gateway'] === 'nomba') {
            $result = $this->processNombaPayment($payment, $successCallbackUrl);
        } else {
            $result = $this->processStripePayment($payment, $successCallbackUrl, $cancelCallbackUrl);
        }

        if ($result['success']) {
            $payment->update([
                'gateway_reference' => $result['reference'],
                'payment_link' => $result['payment_link'] ?? null,
                'gateway_response' => $result['response'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_link' => $result['payment_link'],
                    'gateway_reference' => $result['reference'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'amount_usd' => $budgetUSD,
                    'exchange_rate' => $exchangeRate,
                    'expires_at' => $payment->expires_at,
                    'payment_gateway' => $validated['payment_gateway']
                ]
            ]);
        } else {
            $payment->markAsFailed($result['message']);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $result['message']
            ], 500);
        }

    } catch (\Exception $e) {
        \Log::error('Ad payment initiation failed', [
            'ad_id' => $id,
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to initiate payment: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Process Nomba payment
 */
private function processNombaPayment(AdPayment $payment, $callbackUrl)
{
    try {
        $nombaHelper = new NombaPyamentHelper();

        $paymentData = [
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'email' => $payment->user->email,
            'callback_url' => $callbackUrl, // Internal callback URL
            'reference' => 'AD_' . $payment->ad_id . '_' . $payment->id . '_' . time(),
        ];

        $result = $nombaHelper->initiatePayment($paymentData);

        if ($result['success']) {
            return [
                'success' => true,
                'payment_link' => $result['data']['payment_link'],
                'reference' => $result['data']['reference'],
                'response' => $result['data']
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Nomba payment processing failed'
        ];

    } catch (\Exception $e) {
        \Log::error('Nomba payment processing failed', [
            'payment_id' => $payment->id,
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
/**
 * Process Stripe payment
 */
private function processStripePayment(AdPayment $payment, $successUrl, $cancelUrl)
{
    try {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        // Create Stripe checkout session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'product_data' => [
                        'name' => 'Advertisement Payment - ' . $payment->ad->ad_name,
                        'description' => 'Payment for advertisement: ' . $payment->ad->description,
                    ],
                    'unit_amount' => $payment->amount * 100, // Stripe expects amount in cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl, // Internal success URL
            'cancel_url' => $cancelUrl, // Internal cancel URL
            'client_reference_id' => $payment->id,
            'metadata' => [
                'ad_id' => $payment->ad_id,
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'type' => 'advertisement_payment'
            ]
        ]);

        return [
            'success' => true,
            'payment_link' => $session->url,
            'reference' => $session->id,
            'response' => [
                'session_id' => $session->id,
                'payment_intent' => $session->payment_intent
            ]
        ];

    } catch (\Exception $e) {
        \Log::error('Stripe payment processing failed', [
            'payment_id' => $payment->id,
            'error' => $e->getMessage()
        ]);

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get payment status
 */
public function getPaymentStatus(Request $request, $id, $paymentId)
{
    try {
        $user = $request->user();

        $payment = AdPayment::with('ad')
            ->where('id', $paymentId)
            ->where('user_id', $user->id)
            ->whereHas('ad', function($query) use ($id) {
                $query->where('id', $id);
            })
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment status retrieved successfully',
            'data' => [
                'payment_id' => $payment->id,
                'ad_id' => $payment->ad_id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'amount_usd' => $payment->amount_usd,
                'payment_gateway' => $payment->payment_gateway,
                'gateway_reference' => $payment->gateway_reference,
                'paid_at' => $payment->paid_at,
                'expires_at' => $payment->expires_at,
                'failure_reason' => $payment->failure_reason,
                'ad_status' => $payment->ad->status
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve payment status: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Handle Nomba payment webhook (updated to use existing helper methods)
 */
public function handleNombaWebhook(Request $request)
{
    try {
        $nombaHelper = new NombaPyamentHelper();

        // Verify webhook signature using existing method
        if (!$nombaHelper->verifyWebhookSignatureFromRequest($request)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $data = $request->all();
        $reference = $data['orderReference'] ?? $data['reference'] ?? null;

        if (!$reference) {
            return response()->json(['message' => 'Reference not found'], 400);
        }

        // Find payment by reference
        $payment = AdPayment::where('gateway_reference', $reference)
            ->where('payment_gateway', 'nomba')
            ->first();

        if (!$payment) {
            \Log::warning('Nomba webhook: Payment not found', ['reference' => $reference]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Verify payment status using existing method
        $verificationResult = $nombaHelper->verifyPaymentByReference($reference);

        if ($verificationResult['success'] && $verificationResult['payment_status'] === 'successful') {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_transaction_id' => $data['transactionId'] ?? $data['transaction_id'] ?? null,
                'gateway_response' => $data
            ]);

            // Update ad status to pending_review
            $payment->ad->update(['status' => \App\Models\Ad::STATUS_PENDING_REVIEW]);

            \Log::info('Nomba payment completed', ['payment_id' => $payment->id]);

           } elseif (!$verificationResult['success'] || $verificationResult['payment_status'] === 'failed') {
            $payment->markAsFailed($data['failure_reason'] ?? $verificationResult['message'] ?? 'Payment failed');
            \Log::info('Nomba payment failed', ['payment_id' => $payment->id]);
        }

        return response()->json(['message' => 'Webhook processed successfully']);

    } catch (\Exception $e) {
        \Log::error('Nomba webhook processing failed', [
            'error' => $e->getMessage(),
            'data' => $request->all()
        ]);

        return response()->json(['message' => 'Webhook processing failed'], 500);
    }
}
/**
 * Handle Stripe payment webhook
 */
public function handleStripeWebhook(Request $request)
{
    try {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        // Verify webhook signature
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

        // Handle the event
        switch ($event['type']) {
            case 'checkout.session.completed':
                $session = $event['data']['object'];
                $this->handleStripeCheckoutCompleted($session);
                break;

            case 'payment_intent.succeeded':
                $paymentIntent = $event['data']['object'];
                $this->handleStripePaymentSucceeded($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event['data']['object'];
                $this->handleStripePaymentFailed($paymentIntent);
                break;

            default:
                \Log::info('Unhandled Stripe webhook event', ['type' => $event['type']]);
        }

        return response()->json(['message' => 'Webhook processed successfully']);

    } catch (\UnexpectedValueException $e) {
        \Log::error('Invalid Stripe webhook payload', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Invalid payload'], 400);

    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        \Log::error('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Invalid signature'], 400);

    } catch (\Exception $e) {
        \Log::error('Stripe webhook processing failed', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Webhook processing failed'], 500);
    }
}

/**
 * Handle Stripe checkout session completed
 */
private function handleStripeCheckoutCompleted($session)
{
    $paymentId = $session['client_reference_id'];

    $payment = AdPayment::where('id', $paymentId)
        ->where('payment_gateway', 'stripe')
        ->first();

    if (!$payment) {
        \Log::warning('Stripe webhook: Payment not found', ['payment_id' => $paymentId]);
        return;
    }

    $payment->update([
        'status' => 'completed',
        'paid_at' => now(),
        'gateway_transaction_id' => $session['payment_intent'],
        'gateway_response' => $session
    ]);

    // Update ad status to pending_review
    $payment->ad->update(['status' => Ad::STATUS_PENDING_REVIEW]);

    \Log::info('Stripe payment completed', ['payment_id' => $payment->id]);
}

/**
 * Handle Stripe payment succeeded
 */
private function handleStripePaymentSucceeded($paymentIntent)
{
    // Additional handling if needed
    \Log::info('Stripe payment intent succeeded', ['payment_intent' => $paymentIntent['id']]);
}

/**
 * Handle Stripe payment failed
 */
private function handleStripePaymentFailed($paymentIntent)
{
    // Find payment by payment intent ID
    $payment = AdPayment::where('gateway_transaction_id', $paymentIntent['id'])
        ->where('payment_gateway', 'stripe')
        ->first();

    if ($payment) {
        $payment->markAsFailed($paymentIntent['last_payment_error']['message'] ?? 'Payment failed');
        \Log::info('Stripe payment failed', ['payment_id' => $payment->id]);
    }
}

/**
 * Handle payment success
 */
public function paymentSuccess(Request $request, $paymentId)
{
    try {
        $payment = AdPayment::with('ad')->find($paymentId);

        if (!$payment) {
            return redirect()->to(config('app.frontend_url') . '/ads/payment/error?message=Payment not found');
        }

        // Update payment status if still pending
        if ($payment->status === 'pending') {
            // Verify payment with gateway
            $verified = false;

            if ($payment->payment_gateway === 'nomba') {
                $nombaHelper = new NombaPyamentHelper();
                $verificationResult = $nombaHelper->verifyPaymentByReference($payment->gateway_reference);
                $verified = $verificationResult['success'] && $verificationResult['payment_status'] === 'successful';
            } elseif ($payment->payment_gateway === 'stripe') {
                // For Stripe, we trust the success redirect as verification
                // In production, you should verify with Stripe API
                $verified = true;
            }

            if ($verified) {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now()
                ]);

                // Update ad status
                $payment->ad->update(['status' => \App\Models\Ad::STATUS_PENDING_REVIEW]);

                \Log::info('Payment marked as completed via success callback', [
                    'payment_id' => $payment->id,
                    'ad_id' => $payment->ad_id
                ]);
            }
        }

        // Redirect to external callback URL if provided, otherwise to frontend
        if ($payment->external_callback_url) {
            return redirect()->to($payment->external_callback_url . '?status=success&payment_id=' . $payment->id);
        }

        return redirect()->to(config('app.frontend_url') . '/ads/payment/success?payment_id=' . $paymentId . '&ad_id=' . $payment->ad_id);
    } catch (\Exception $e) {
        \Log::error('Error in payment success callback', [
            'payment_id' => $paymentId,
            'error' => $e->getMessage()
        ]);

        return redirect()->to(config('app.frontend_url') . '/ads/payment/error?message=Error processing payment');
    }
}

/**
 * Handle payment cancellation
 */
public function paymentCancel(Request $request, $paymentId)
{
    try {
        $payment = AdPayment::with('ad')->find($paymentId);

        if ($payment && $payment->status === 'pending') {
            $payment->update(['status' => 'cancelled']);

            \Log::info('Payment cancelled via cancel callback', [
                'payment_id' => $payment->id,
                'ad_id' => $payment->ad_id
            ]);
        }

        // Redirect to external callback URL if provided, otherwise to frontend
        if ($payment && $payment->external_callback_url) {
            return redirect()->to($payment->external_callback_url . '?status=cancelled&payment_id=' . $payment->id);
        }

        return redirect()->to(config('app.frontend_url') . '/ads/payment/cancelled?payment_id=' . $paymentId);
    } catch (\Exception $e) {
        \Log::error('Error in payment cancel callback', [
            'payment_id' => $paymentId,
            'error' => $e->getMessage()
        ]);

        return redirect()->to(config('app.frontend_url') . '/ads/payment/error?message=Error processing cancellation');
    }
}

/**
 * Manually verify payment status
 */
public function verifyPayment(Request $request, $id, $paymentId)
{
    try {
        $user = $request->user();

        $payment = AdPayment::with('ad')
            ->where('id', $paymentId)
            ->where('user_id', $user->id)
            ->whereHas('ad', function($query) use ($id) {
                $query->where('id', $id);
            })
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        // Only verify if payment is still pending
        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => true,
                'message' => 'Payment status is already final',
                'data' => [
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                    'gateway_reference' => $payment->gateway_reference
                ]
            ]);
        }

        // Verify with payment gateway
        if ($payment->payment_gateway === 'nomba') {
            $nombaHelper = new NombaPyamentHelper();
            $verificationResult = $nombaHelper->verifyPaymentByReference($payment->gateway_reference);

            if ($verificationResult['success'] && $verificationResult['payment_status'] === 'successful') {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'gateway_response' => $verificationResult['data']
                ]);

                // Update ad status
                $payment->ad->update(['status' => \App\Models\Ad::STATUS_PENDING_REVIEW]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified and completed successfully',
                    'data' => [
                        'payment_id' => $payment->id,
                        'status' => 'completed',
                        'ad_status' => \App\Models\Ad::STATUS_PENDING_REVIEW
                    ]
                ]);
            } elseif ($verificationResult['payment_status'] === 'failed') {
                $payment->markAsFailed('Payment verification failed');

                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed',
                    'data' => [
                        'payment_id' => $payment->id,
                        'status' => 'failed'
                    ]
                ]);
            }
        }

        // For Stripe, you would implement similar verification
        if ($payment->payment_gateway === 'stripe') {
            // Stripe verification logic here
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            try {
                $session = \Stripe\Checkout\Session::retrieve($payment->gateway_reference);

                if ($session->payment_status === 'paid') {
                    $payment->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                        'gateway_transaction_id' => $session->payment_intent,
                        'gateway_response' => $session->toArray()
                    ]);

                    $payment->ad->update(['status' => \App\Models\Ad::STATUS_PENDING_REVIEW]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment verified and completed successfully',
                        'data' => [
                            'payment_id' => $payment->id,
                            'status' => 'completed',
                            'ad_status' => \App\Models\Ad::STATUS_PENDING_REVIEW
                        ]
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Stripe payment verification failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment is still pending verification',
            'data' => [
                'payment_id' => $payment->id,
                'status' => $payment->status
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Payment verification failed', [
            'payment_id' => $paymentId,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to verify payment: ' . $e->getMessage()
        ], 500);
    }
}


/**
 * Get payment history for an ad
 */
public function getPaymentHistory(Request $request, $id)
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

        $payments = AdPayment::where('ad_id', $ad->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'amount_usd' => $payment->amount_usd,
                    'exchange_rate' => $payment->exchange_rate,
                    'payment_gateway' => $payment->payment_gateway,
                    'status' => $payment->status,
                    'gateway_reference' => $payment->gateway_reference,
                    'paid_at' => $payment->paid_at,
                    'created_at' => $payment->created_at,
                    'expires_at' => $payment->expires_at,
                    'failure_reason' => $payment->failure_reason,
                    'can_retry' => $payment->canRetry()
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Payment history retrieved successfully',
            'data' => [
                'ad_id' => $ad->id,
                'ad_name' => $ad->ad_name,
                'ad_status' => $ad->status,
                'payments' => $payments
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve payment history: ' . $e->getMessage()
        ], 500);
    }
}



/**
 * @OA\Get(
 *     path="/api/v1/ads/analytics/impressions-overtime",
 *     summary="Get impressions overtime data for line chart",
 *     tags={"Advertising"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="year", in="query", required=true, @OA\Schema(type="integer", example=2024)),
 *     @OA\Parameter(name="ad_id", in="query", @OA\Schema(type="integer", description="Specific ad ID (optional)")),
 *     @OA\Response(response=200, description="Impressions overtime data retrieved successfully")
 * )
 */
public function getImpressionsOvertime(Request $request)
{
    try {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'ad_id' => 'nullable|integer|exists:ads,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $year = $request->input('year');
        $adId = $request->input('ad_id');

        // If ad_id is provided, verify it belongs to the user
        if ($adId) {
            $ad = Ad::where('id', $adId)
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$ad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Advertisement not found or access denied'
                ], 404);
            }
        }

        // Get impressions overtime data
        $data = AdHelper::getImpressionsOvertime($user->id, $year, $adId);

        return response()->json([
            'success' => true,
            'message' => 'Impressions overtime data retrieved successfully',
            'data' => $data
        ]);

    } catch (\Exception $e) {
        \Log::error('Failed to get impressions overtime data', [
            'user_id' => $request->user()->id,
            'year' => $request->input('year'),
            'ad_id' => $request->input('ad_id'),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve impressions overtime data: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * @OA\Get(
 *     path="/api/v1/ads/analytics/available-years",
 *     summary="Get available years for analytics",
 *     tags={"Advertising"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Available years retrieved successfully")
 * )
 */
public function getAvailableYears(Request $request)
{
    try {
        $user = $request->user();

        // Get years from user's ads
        $years = Ad::where('user_id', $user->id)
            ->where('deleted_flag', 'N')
            ->selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Add current year if not present
        $currentYear = (int) date('Y');
          if (!in_array($currentYear, $years)) {
            array_unshift($years, $currentYear);
        }

        // Sort years in descending order
        rsort($years);

        return response()->json([
            'success' => true,
            'message' => 'Available years retrieved successfully',
            'data' => [
                'years' => $years,
                'default_year' => $currentYear,
                'total_years' => count($years)
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Failed to get available years', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve available years: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * @OA\Get(
 *     path="/api/v1/ads/analytics/comparison",
 *     summary="Compare impressions between two years",
 *     tags={"Advertising"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="year1", in="query", required=true, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="year2", in="query", required=true, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="ad_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Comparison data retrieved successfully")
 * )
 */
public function getYearComparison(Request $request)
{
    try {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'year1' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'year2' => 'required|integer|min:2020|max:' . (date('Y') + 1) . '|different:year1',
            'ad_id' => 'nullable|integer|exists:ads,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $year1 = $request->input('year1');
        $year2 = $request->input('year2');
        $adId = $request->input('ad_id');

        // Get data for both years
        $data1 = AdHelper::getImpressionsOvertime($user->id, $year1, $adId);
        $data2 = AdHelper::getImpressionsOvertime($user->id, $year2, $adId);

        // Calculate growth/decline
        $comparison = [
            'year1' => $year1,
            'year2' => $year2,
            'data1' => $data1,
            'data2' => $data2,
            'growth_analysis' => [
                'impressions_growth' => $this->calculateGrowth(
                    $data1['summary']['total_impressions'] ?? 0,
                    $data2['summary']['total_impressions'] ?? 0
                ),
                'clicks_growth' => $this->calculateGrowth(
                    $data1['summary']['total_clicks'] ?? 0,
                    $data2['summary']['total_clicks'] ?? 0
                ),
                'conversions_growth' => $this->calculateGrowth(
                    $data1['summary']['total_conversions'] ?? 0,
                    $data2['summary']['total_conversions'] ?? 0
                ),
                'ctr_change' => $this->calculateChange(
                    $data1['summary']['average_ctr'] ?? 0,
                    $data2['summary']['average_ctr'] ?? 0
                )
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Year comparison data retrieved successfully',
            'data' => $comparison
        ]);

    } catch (\Exception $e) {
        \Log::error('Failed to get year comparison data', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve comparison data: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Helper method to calculate growth percentage
 */
private function calculateGrowth($oldValue, $newValue)
{
    if ($oldValue == 0) {
        return $newValue > 0 ? 100 : 0;
    }

    return round((($newValue - $oldValue) / $oldValue) * 100, 2);
}

/**
 * Helper method to calculate change
 */
private function calculateChange($oldValue, $newValue)
{
    return round($newValue - $oldValue, 2);
}
}
