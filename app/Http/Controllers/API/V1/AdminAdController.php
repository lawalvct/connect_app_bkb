<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Admin - Advertising",
 *     description="Admin advertisement management operations"
 * )
 */
class AdminAdController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/ads",
     *     summary="Get all advertisements for admin review",
     *     tags={"Admin - Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Advertisements retrieved successfully")
     * )
     */
    public function index(Request $request)
    {
        try {
            // Check if user is admin (you can implement your own admin check)
            // if (!$request->user()->hasRole('admin')) {
            //     return $this->sendError('Unauthorized', null, 403);
            // }

            $query = Ad::with(['user'])->notDeleted();

            // Apply filters
            if ($request->has('admin_status') && !empty($request->admin_status)) {
                $query->where('admin_status', $request->admin_status);
            }

            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
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
                ]
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisements: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/ads/{id}/approve",
     *     summary="Approve an advertisement",
     *     tags={"Admin - Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="comments", type="string", description="Optional admin comments")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Advertisement approved successfully")
     * )
     */
    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comments' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $admin = $request->user();
            $ad = Ad::notDeleted()->find($id);

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            if ($ad->admin_status !== 'pending') {
                return $this->sendError('Only pending advertisements can be approved', null, 403);
            }

            $ad->approve($admin->id, $request->comments);

            // Here you can send notification to the ad owner about approval
            // NotificationHelper::sendAdApprovalNotification($ad->user_id, $ad->id);

            return $this->sendResponse('Advertisement approved successfully', $ad->load(['user', 'reviewer']));

        } catch (\Exception $e) {
            return $this->sendError('Failed to approve advertisement: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/ads/{id}/reject",
     *     summary="Reject an advertisement",
     *     tags={"Admin - Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="comments", type="string", description="Required rejection reason")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Advertisement rejected successfully")
     * )
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comments' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $admin = $request->user();
            $ad = Ad::notDeleted()->find($id);

            if (!$ad) {
                return $this->sendError('Advertisement not found', null, 404);
            }

            if ($ad->admin_status !== 'pending') {
                return $this->sendError('Only pending advertisements can be rejected', null, 403);
            }

            $ad->reject($admin->id, $request->comments);

            // Here you can send notification to the ad owner about rejection
            // NotificationHelper::sendAdRejectionNotification($ad->user_id, $ad->id, $request->comments);

            return $this->sendResponse('Advertisement rejected successfully', $ad->load(['user', 'reviewer']));

        } catch (\Exception $e) {
            return $this->sendError('Failed to reject advertisement: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/ads/dashboard",
     *     summary="Get admin advertising dashboard",
     *     tags={"Admin - Advertising"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Admin dashboard retrieved successfully")
     * )
     */
    public function dashboard(Request $request)
    {
        try {
            $totalAds = Ad::notDeleted()->count();
            $pendingAds = Ad::where('admin_status', 'pending')->count();
            $approvedAds = Ad::where('admin_status', 'approved')->count();
            $rejectedAds = Ad::where('admin_status', 'rejected')->count();
            $activeAds = Ad::where('status', 'active')->count();

            // Revenue metrics
            $totalRevenue = Ad::sum('total_spent');
            $monthlyRevenue = Ad::whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->sum('total_spent');

            // Performance metrics
            $totalImpressions = Ad::sum('current_impressions');
            $totalClicks = Ad::sum('clicks');
            $overallCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;

            // Recent activities
            $recentAds = Ad::with(['user'])
                            ->notDeleted()
                            ->latest()
                            ->limit(10)
                            ->get();

            return $this->sendResponse('Admin dashboard retrieved successfully', [
                'summary' => [
                    'total_ads' => $totalAds,
                    'pending_ads' => $pendingAds,
                    'approved_ads' => $approvedAds,
                    'rejected_ads' => $rejectedAds,
                    'active_ads' => $activeAds,
                    'total_revenue' => $totalRevenue,
                    'monthly_revenue' => $monthlyRevenue,
                    'total_impressions' => $totalImpressions,
                    'total_clicks' => $totalClicks,
                    'overall_ctr' => $overallCtr
                ],
                'recent_ads' => $recentAds
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve admin dashboard: ' . $e->getMessage(), null, 500);
        }
    }
}
