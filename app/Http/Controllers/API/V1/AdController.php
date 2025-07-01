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
     *     @OA\Response(response=200, description="Dashboard data retrieved successfully")
     * )
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();

            // Get user's ads statistics
            $totalAds = Ad::byUser($user->id)->notDeleted()->count();
            $activeAds = Ad::byUser($user->id)->where('status', 'active')->count();
            $pausedAds = Ad::byUser($user->id)->where('status', 'paused')->count();
            $pendingAds = Ad::byUser($user->id)->where('admin_status', 'pending')->count();

            // Get performance metrics
            $totalImpressions = Ad::byUser($user->id)->sum('current_impressions');
            $totalClicks = Ad::byUser($user->id)->sum('clicks');
            $totalSpent = Ad::byUser($user->id)->sum('total_spent');
            $totalBudget = Ad::byUser($user->id)->sum('budget');

            // Calculate CTR
            $overallCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;

            // Get recent ads
            $recentAds = Ad::byUser($user->id)
                ->notDeleted()
                ->with(['user'])
                ->latest()
                ->limit(5)
                ->get();

            // Get ads by status for chart
            $adsByStatus = Ad::byUser($user->id)
                ->notDeleted()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            return $this->sendResponse('Dashboard data retrieved successfully', [
                'summary' => [
                    'total_ads' => $totalAds,
                    'active_ads' => $activeAds,
                    'paused_ads' => $pausedAds,
                    'pending_ads' => $pendingAds,
                    'total_impressions' => $totalImpressions,
                    'total_clicks' => $totalClicks,
                    'total_spent' => $totalSpent,
                    'total_budget' => $totalBudget,
                    'overall_ctr' => $overallCtr,
                    'budget_utilization' => $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 2) : 0
                ],
                'recent_ads' => $recentAds,
                'ads_by_status' => $adsByStatus
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve dashboard data: ' . $e->getMessage(), null, 500);
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
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="date")),
     *     @OA\Response(response=200, description="Advertisements retrieved successfully")
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = Ad::byUser($user->id)->notDeleted()->with(['user']);

            // Apply filters
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }

            if ($request->has('admin_status') && !empty($request->admin_status)) {
                $query->where('admin_status', $request->admin_status);
            }

            if ($request->has('start_date') && !empty($request->start_date)) {
                $query->where('start_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && !empty($request->end_date)) {
                $query->where('end_date', '<=', $request->end_date);
            }

            if ($request->has('search') && !empty($request->search)) {
                $query->where('ad_name', 'like', '%' . $request->search . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $ads = $query->paginate($perPage);

            return $this->sendResponse('Advertisements retrieved successfully', [
                'ads' => $ads->items(),
                'pagination' => [
                    'current_page' => $ads->currentPage(),
                    'total_pages' => $ads->lastPage(),
                    'per_page' => $ads->perPage(),
                    'total' => $ads->total(),
                    'from' => $ads->firstItem(),
                    'to' => $ads->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisements: ' . $e->getMessage(), null, 500);
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="ad_name", type="string"),
     *                 @OA\Property(property="type", type="string", enum={"banner", "video", "carousel", "story", "feed"}),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="media_files", type="array", @OA\Items(type="string", format="binary")),
     *                 @OA\Property(property="call_to_action", type="string"),
     *                 @OA\Property(property="destination_url", type="string"),
     *                 @OA\Property(property="start_date", type="string", format="date"),
     *                 @OA\Property(property="end_date", type="string", format="date"),
     *                 @OA\Property(property="budget", type="number"),
     *                 @OA\Property(property="daily_budget", type="number"),
     *                 @OA\Property(property="target_impressions", type="integer"),
     *                 @OA\Property(property="target_audience", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Advertisement created successfully")
     * )
     */
    public function store(CreateAdRequest $request)
    {
        try {
            $user = $request->user();

            // Check if user can create more ads
            if (!AdHelper::canUserCreateAd($user->id)) {
                return $this->sendError('You have reached the maximum number of active advertisements', null, 403);
            }

            $data = $request->validated();

            // Handle media file uploads
            $mediaFiles = [];
            if ($request->hasFile('media_files')) {
                foreach ($request->file('media_files') as $file) {
                    if ($file->isValid()) {
                        $uploadResult = S3UploadHelper::uploadFile($file, 'ads');
                        $mediaFiles[] = [
                            'filename' => $uploadResult['filename'],
                            'url' => $uploadResult['url'],
                            'type' => $file->getMimeType(),
                            'size' => $file->getSize()
                        ];
                    }
                }
            }

            $adData = [
                'user_id' => $user->id,
                'ad_name' => $data['ad_name'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'media_files' => !empty($mediaFiles) ? $mediaFiles : null,
                'call_to_action' => $data['call_to_action'] ?? null,
                'destination_url' => $data['destination_url'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'budget' => $data['budget'],
                'daily_budget' => $data['daily_budget'] ?? AdHelper::calculateEstimatedDailySpend(
                    $data['budget'],
                    $data['start_date'],
                    $data['end_date']
                ),
                'target_impressions' => $data['target_impressions'] ?? 1000,
                'target_audience' => $data['target_audience'] ?? null,
                'status' => 'pending_review',
                'admin_status' => 'pending',
                'created_by' => $user->id
            ];

            $ad = Ad::create($adData);

            // Send notification to admins for review
            // event(new AdCreatedEvent($ad));

            return $this->sendResponse('Advertisement created successfully and sent for review', new AdResource($ad->load('user')), 201);

        } catch (\Exception $e) {
            return $this->sendError('Failed to create advertisement: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ads/{id}",
     *     summary="Get a specific advertisement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Advertisement retrieved successfully")
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $ad = Ad::byUser($user->id)->notDeleted()->with(['user', 'reviewer'])->find($id);

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            return $this->sendResponse('Advertisement retrieved successfully', $ad);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisement: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ads/{id}/preview",
     *     summary="Preview advertisement",
     *     tags={"Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Advertisement preview retrieved successfully")
     * )
     */
    public function preview(Request $request, $id)
    {
        try {
            $user = $request->user();
            $ad = Ad::byUser($user->id)->notDeleted()->find($id);

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            // Generate preview data based on ad type
            $previewData = [
                'ad' => $ad,
                'preview_html' => $this->generatePreviewHtml($ad),
                'estimated_reach' => $this->calculateEstimatedReach($ad),
                'estimated_performance' => $this->calculateEstimatedPerformance($ad)
            ];

            return $this->sendResponse('Advertisement preview retrieved successfully', $previewData);

        } catch (\Exception $e) {
            return $this->sendError('Failed to generate preview: ' . $e->getMessage(), null, 500);
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
            $ad = Ad::byUser($user->id)->notDeleted()->find($id);

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            if (!$ad->can_be_edited) {
                return $this->sendError('Advertisement cannot be edited in current status', null, 403);
            }

            $data = $request->validated();

            // Handle media file uploads if provided
            if ($request->hasFile('media_files')) {
                $mediaFiles = [];
                foreach ($request->file('media_files') as $file) {
                    if ($file->isValid()) {
                        $uploadResult = S3UploadHelper::uploadFile($file, 'ads');
                        $mediaFiles[] = [
                            'filename' => $uploadResult['filename'],
                            'url' => $uploadResult['url'],
                            'type' => $file->getMimeType(),
                            'size' => $file->getSize()
                        ];
                    }
                }
                $data['media_files'] = $mediaFiles;
            }

            // Recalculate daily budget if budget or dates changed
            if (isset($data['budget']) || isset($data['start_date']) || isset($data['end_date'])) {
                $budget = $data['budget'] ?? $ad->budget;
                $startDate = $data['start_date'] ?? $ad->start_date;
                $endDate = $data['end_date'] ?? $ad->end_date;

                $data['daily_budget'] = $data['daily_budget'] ?? AdHelper::calculateEstimatedDailySpend(
                    $budget, $startDate, $endDate
                );
            }

            $data['updated_by'] = $user->id;

            // If ad was rejected and now being updated, reset to pending review
            if ($ad->admin_status === 'rejected') {
                $data['admin_status'] = 'pending';
                $data['status'] = 'pending_review';
                $data['admin_comments'] = null;
                $data['reviewed_by'] = null;
                $data['reviewed_at'] = null;
            }

            $ad->update($data);

            return $this->sendResponse('Advertisement updated successfully', new AdResource($ad->load(['user', 'reviewer'])));

        } catch (\Exception $e) {
            return $this->sendError('Failed to update advertisement: ' . $e->getMessage(), null, 500);
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
            $ad = Ad::byUser($user->id)->notDeleted()->find($id);

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            if (!$ad->canBePaused()) {
                return $this->sendError('Advertisement cannot be paused in current status', null, 403);
            }

            $ad->pause();

            return $this->sendResponse('Advertisement paused successfully', $ad);

        } catch (\Exception $e) {
            return $this->sendError('Failed to pause advertisement: ' . $e->getMessage(), null, 500);
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
            $ad = Ad::byUser($user->id)->notDeleted()->find($id);

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            if ($ad->status !== 'paused') {
                return $this->sendError('Only paused advertisements can be resumed', null, 403);
            }

            $ad->resume();

            return $this->sendResponse('Advertisement resumed successfully', $ad);

        } catch (\Exception $e) {
            return $this->sendError('Failed to resume advertisement: ' . $e->getMessage(), null, 500);
        }
    }

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
               $ad = Ad::byUser($user->id)->notDeleted()->find($id);

               if (!$ad) {
                   return $this->sendError('Advertisement not found', null, 404);
               }

               if (!$ad->canBeStopped()) {
                   return $this->sendError('Advertisement cannot be stopped in current status', null, 403);
               }

               $ad->stop();

               return $this->sendResponse('Advertisement stopped successfully', $ad);

           } catch (\Exception $e) {
               return $this->sendError('Failed to stop advertisement: ' . $e->getMessage(), null, 500);
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
               $ad = Ad::byUser($user->id)->notDeleted()->find($id);

               if (!$ad) {
                   return $this->sendError('Advertisement not found', null, 404);
               }

               if (!$ad->canBeDeleted()) {
                   return $this->sendError('Advertisement cannot be deleted in current status', null, 403);
               }

               // Soft delete by updating deleted_flag
               $ad->update([
                   'deleted_flag' => 'Y',
                   'updated_by' => $user->id
               ]);

               return $this->sendResponse('Advertisement deleted successfully', null);

           } catch (\Exception $e) {
               return $this->sendError('Failed to delete advertisement: ' . $e->getMessage(), null, 500);
           }
       }

       /**
        * @OA\Get(
        *     path="/api/v1/ads/export",
        *     summary="Export advertisements to PDF or Excel",
        *     tags={"Advertising"},
        *     security={{"bearerAuth":{}}},
        *     @OA\Parameter(name="format", in="query", required=true, @OA\Schema(type="string", enum={"pdf", "excel"})),
        *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
        *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
        *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="date")),
        *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="date")),
        *     @OA\Response(response=200, description="Export file generated successfully")
        * )
        */
       public function export(Request $request)
       {
           $validator = Validator::make($request->all(), [
               'format' => 'required|in:pdf,excel',
               'status' => 'nullable|string',
               'type' => 'nullable|string',
               'start_date' => 'nullable|date',
               'end_date' => 'nullable|date'
           ]);

           if ($validator->fails()) {
               return $this->sendError('Validation Error', $validator->errors(), 422);
           }

           try {
               $user = $request->user();
               $query = Ad::byUser($user->id)->notDeleted()->with(['user']);

               // Apply same filters as index method
               if ($request->has('status') && !empty($request->status)) {
                   $query->where('status', $request->status);
               }

               if ($request->has('type') && !empty($request->type)) {
                   $query->where('type', $request->type);
               }

               if ($request->has('start_date') && !empty($request->start_date)) {
                   $query->where('start_date', '>=', $request->start_date);
               }

               if ($request->has('end_date') && !empty($request->end_date)) {
                   $query->where('end_date', '<=', $request->end_date);
               }

               $ads = $query->orderBy('created_at', 'desc')->get();

               if ($request->format === 'pdf') {
                   return $this->exportToPdf($ads, $user);
               } else {
                   return $this->exportToExcel($ads, $user);
               }

           } catch (\Exception $e) {
               return $this->sendError('Failed to export advertisements: ' . $e->getMessage(), null, 500);
           }
       }

       /**
        * @OA\Get(
        *     path="/api/v1/ads/{id}/analytics",
        *     summary="Get detailed analytics for an advertisement",
        *     tags={"Advertising"},
        *     security={{"bearerAuth":{}}},
        *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
        *     @OA\Response(response=200, description="Advertisement analytics retrieved successfully")
        * )
        */
       public function analytics(Request $request, $id)
       {
           try {
               $user = $request->user();
               $ad = Ad::byUser($user->id)->notDeleted()->find($id);

               if (!$ad) {
                   return $this->sendError('Advertisement not found', null, 404);
               }

               $analytics = [
                   'basic_metrics' => [
                       'impressions' => $ad->current_impressions,
                       'clicks' => $ad->clicks,
                       'conversions' => $ad->conversions,
                       'ctr' => $ad->ctr,
                       'conversion_rate' => $ad->current_impressions > 0 ? round(($ad->conversions / $ad->current_impressions) * 100, 2) : 0,
                       'cost_per_click' => $ad->cost_per_click,
                       'cost_per_conversion' => $ad->conversions > 0 ? round($ad->total_spent / $ad->conversions, 2) : 0
                   ],
                   'budget_metrics' => [
                       'total_budget' => $ad->budget,
                       'daily_budget' => $ad->daily_budget,
                       'total_spent' => $ad->total_spent,
                       'remaining_budget' => $ad->budget - $ad->total_spent,
                       'budget_utilization' => $ad->budget > 0 ? round(($ad->total_spent / $ad->budget) * 100, 2) : 0
                   ],
                   'timeline_metrics' => [
                       'start_date' => $ad->start_date->format('Y-m-d'),
                       'end_date' => $ad->end_date->format('Y-m-d'),
                       'days_total' => $ad->start_date->diffInDays($ad->end_date) + 1,
                       'days_remaining' => $ad->days_remaining,
                       'days_active' => $ad->activated_at ? $ad->activated_at->diffInDays(now()) : 0
                   ],
                   'performance_metrics' => [
                       'target_impressions' => $ad->target_impressions,
                       'progress_percentage' => $ad->progress_percentage,
                       'daily_average_impressions' => $ad->activated_at ?
                           round($ad->current_impressions / max(1, $ad->activated_at->diffInDays(now()) + 1)) : 0,
                       'daily_average_clicks' => $ad->activated_at ?
                           round($ad->clicks / max(1, $ad->activated_at->diffInDays(now()) + 1)) : 0
                   ]
               ];

               return $this->sendResponse('Advertisement analytics retrieved successfully', $analytics);

           } catch (\Exception $e) {
               return $this->sendError('Failed to retrieve analytics: ' . $e->getMessage(), null, 500);
           }
       }

       // Helper Methods

       private function generatePreviewHtml($ad)
       {
           $html = '<div class="ad-preview ad-type-' . $ad->type . '">';

           switch ($ad->type) {
               case 'banner':
                   $html .= '<div class="banner-ad">';
                   if ($ad->media_files && count($ad->media_files) > 0) {
                       $html .= '<img src="' . $ad->media_files[0]['url'] . '" alt="' . $ad->ad_name . '" class="ad-image">';
                   }
                   $html .= '<div class="ad-content">';
                   $html .= '<h3>' . $ad->ad_name . '</h3>';
                   if ($ad->description) {
                       $html .= '<p>' . $ad->description . '</p>';
                   }
                   if ($ad->call_to_action) {
                       $html .= '<button class="cta-button">' . $ad->call_to_action . '</button>';
                   }
                   $html .= '</div></div>';
                   break;

               case 'feed':
                   $html .= '<div class="feed-ad">';
                   $html .= '<div class="ad-header">';
                   $html .= '<span class="sponsored-label">Sponsored</span>';
                   $html .= '</div>';
                   if ($ad->media_files && count($ad->media_files) > 0) {
                       $html .= '<img src="' . $ad->media_files[0]['url'] . '" alt="' . $ad->ad_name . '" class="ad-image">';
                   }
                   $html .= '<div class="ad-content">';
                   $html .= '<h3>' . $ad->ad_name . '</h3>';
                   if ($ad->description) {
                       $html .= '<p>' . $ad->description . '</p>';
                   }
                   if ($ad->call_to_action) {
                       $html .= '<button class="cta-button">' . $ad->call_to_action . '</button>';
                   }
                   $html .= '</div></div>';
                   break;

               case 'story':
                   $html .= '<div class="story-ad">';
                   if ($ad->media_files && count($ad->media_files) > 0) {
                       $html .= '<img src="' . $ad->media_files[0]['url'] . '" alt="' . $ad->ad_name . '" class="story-image">';
                   }
                   $html .= '<div class="story-overlay">';
                   $html .= '<h3>' . $ad->ad_name . '</h3>';
                   if ($ad->call_to_action) {
                       $html .= '<button class="story-cta">' . $ad->call_to_action . '</button>';
                   }
                   $html .= '</div></div>';
                   break;

               default:
                   $html .= '<div class="default-ad">';
                   $html .= '<h3>' . $ad->ad_name . '</h3>';
                   if ($ad->description) {
                       $html .= '<p>' . $ad->description . '</p>';
                   }
                   $html .= '</div>';
           }

           $html .= '</div>';
           return $html;
       }

       private function calculateEstimatedReach($ad)
       {
           // Simple estimation based on target audience and budget
           $baseReach = 1000;
           $budgetMultiplier = $ad->budget / 100;

           // Adjust based on target audience
           if ($ad->target_audience) {
               $audience = $ad->target_audience;

               // Age range adjustment
               if (isset($audience['age_min']) && isset($audience['age_max'])) {
                   $ageRange = $audience['age_max'] - $audience['age_min'];
                   $baseReach *= ($ageRange / 50); // Normalize to 0-1 range
               }

               // Gender adjustment
               if (isset($audience['gender']) && $audience['gender'] !== 'all') {
                   $baseReach *= 0.5; // Reduce reach for gender-specific targeting
               }

               // Location adjustment
               if (isset($audience['locations']) && is_array($audience['locations'])) {
                   $baseReach *= (count($audience['locations']) / 10); // Assume 10 is average
               }
           }

           return [
               'min_reach' => round($baseReach * $budgetMultiplier * 0.8),
               'max_reach' => round($baseReach * $budgetMultiplier * 1.2),
               'estimated_reach' => round($baseReach * $budgetMultiplier)
           ];
       }

       private function calculateEstimatedPerformance($ad)
       {
           $estimatedReach = $this->calculateEstimatedReach($ad)['estimated_reach'];

           // Industry average CTR is around 1-2%
           $estimatedCtr = 1.5;
           $estimatedClicks = round($estimatedReach * ($estimatedCtr / 100));

           // Estimated conversion rate is around 2-5%
           $estimatedConversionRate = 3;
           $estimatedConversions = round($estimatedClicks * ($estimatedConversionRate / 100));

           return [
               'estimated_impressions' => $estimatedReach,
               'estimated_clicks' => $estimatedClicks,
               'estimated_conversions' => $estimatedConversions,
               'estimated_ctr' => $estimatedCtr,
               'estimated_conversion_rate' => $estimatedConversionRate,
               'estimated_cost_per_click' => $estimatedClicks > 0 ? round($ad->budget / $estimatedClicks, 2) : 0
           ];
       }

       private function exportToPdf($ads, $user)
       {
           $data = [
               'ads' => $ads,
               'user' => $user,
               'export_date' => now()->format('Y-m-d H:i:s')
           ];

           $pdf = Pdf::loadView('exports.ads-pdf', $data);

           $filename = 'ads-export-' . now()->format('Y-m-d-H-i-s') . '.pdf';

           return response()->streamDownload(function() use ($pdf) {
               echo $pdf->output();
           }, $filename, [
               'Content-Type' => 'application/pdf'
           ]);
       }

       private function exportToExcel($ads, $user)
       {
           $filename = 'ads-export-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

           return Excel::download(new class($ads, $user) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping {

               private $ads;
               private $user;

               public function __construct($ads, $user)
               {
                   $this->ads = $ads;
                   $this->user = $user;
               }

               public function collection()
               {
                   return $this->ads;
               }

               public function headings(): array
               {
                   return [
                       'ID',
                       'Ad Name',
                       'Type',
                       'Status',
                       'Admin Status',
                       'Start Date',
                       'End Date',
                       'Budget',
                       'Total Spent',
                       'Impressions',
                       'Clicks',
                       'CTR (%)',
                       'Conversions',
                       'Progress (%)',
                       'Created At'
                   ];
               }

               public function map($ad): array
               {
                   return [
                       $ad->id,
                       $ad->ad_name,
                       ucfirst($ad->type),
                       ucfirst($ad->status),
                       ucfirst($ad->admin_status),
                       $ad->start_date->format('Y-m-d'),
                       $ad->end_date->format('Y-m-d'),
                       '$' . number_format($ad->budget, 2),
                       '$' . number_format($ad->total_spent, 2),
                       number_format($ad->current_impressions),
                       number_format($ad->clicks),
                       $ad->ctr . '%',
                       number_format($ad->conversions),
                       $ad->progress_percentage . '%',
                       $ad->created_at->format('Y-m-d H:i:s')
                   ];
               }
           }, $filename);
       }
   }
