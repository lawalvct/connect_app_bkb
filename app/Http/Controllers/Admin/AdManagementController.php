<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdPayment;
use App\Models\User;
use App\Models\SocialCircle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdManagementController extends Controller
{
    public function index()
    {
        return view('admin.ads.index');
    }

    public function show(Ad $ad)
    {
        $ad->load(['user', 'reviewer', 'payments', 'socialCircles']);
        return view('admin.ads.show', compact('ad'));
    }

    public function getAds(Request $request)
    {
        try {
            $query = Ad::with(['user', 'reviewer', 'latestPayment'])
                ->where('deleted_flag', 'N');

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('ad_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('username', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('admin_status')) {
                $query->where('admin_status', $request->admin_status);
            }

            if ($request->filled('payment_status')) {
                $query->whereHas('latestPayment', function($paymentQuery) use ($request) {
                    $paymentQuery->where('status', $request->payment_status);
                });
            }

            // Add date range filtering with calendar dates
            if ($request->filled('date_from')) {
                $dateFrom = $request->get('date_from');
                try {
                    $query->whereDate('created_at', '>=', $dateFrom);
                } catch (\Exception $e) {
                    Log::warning('Invalid date_from format: ' . $dateFrom);
                }
            }

            if ($request->filled('date_to')) {
                $dateTo = $request->get('date_to');
                try {
                    $query->whereDate('created_at', '<=', $dateTo);
                } catch (\Exception $e) {
                    Log::warning('Invalid date_to format: ' . $dateTo);
                }
            }

            // Filter by target country
            if ($request->filled('country')) {
                $countryId = $request->get('country');
                $query->whereJsonContains('target_countries', (int) $countryId);
            }

            // Filter by target social circle
            if ($request->filled('social_circle')) {
                $socialCircleId = $request->get('social_circle');
                $query->whereJsonContains('target_social_circles', (int) $socialCircleId);
            }

            // Sort by creation date (newest first) or by priority for review
            if ($request->admin_status === 'pending') {
                $query->orderByRaw("CASE
                    WHEN EXISTS(SELECT 1 FROM ad_payments WHERE ad_id = ads.id AND status = 'completed') THEN 1
                    ELSE 2
                END")
                ->orderBy('created_at', 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $ads = $query->paginate(20);

            // Append the target countries and social circles data
            $ads->getCollection()->transform(function ($ad) {
                $ad->append(['target_countries_data', 'placement_social_circles']);
                return $ad;
            });

            return response()->json([
                'success' => true,
                'ads' => $ads
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching ads: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch ads'
            ], 500);
        }
    }

    public function getStats()
    {
        try {
            $stats = [
                'total_ads' => Ad::where('deleted_flag', 'N')->count(),
                'pending_review' => Ad::where('admin_status', 'pending')->where('deleted_flag', 'N')->count(),
                'approved' => Ad::where('admin_status', 'approved')->where('deleted_flag', 'N')->count(),
                'rejected' => Ad::where('admin_status', 'rejected')->where('deleted_flag', 'N')->count(),
                'active_ads' => Ad::where('status', 'active')->where('deleted_flag', 'N')->count(),
                'total_revenue' => AdPayment::where('status', 'completed')->sum('amount'),
                'ads_with_payment' => Ad::whereHas('payments', function($q) {
                    $q->where('status', 'completed');
                })->where('deleted_flag', 'N')->count(),
                'draft_ads' => Ad::where('status', 'draft')->where('deleted_flag', 'N')->count(),
            ];

            // Type breakdown
            $stats['type_breakdown'] = Ad::where('deleted_flag', 'N')
                ->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray();

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Error fetching ad stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    public function approve(Request $request, Ad $ad)
    {
        $request->validate([
            'admin_comments' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            // Check if ad has completed payment
            $hasPayment = $ad->payments()->where('status', 'completed')->exists();

            if (!$hasPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot approve ad without completed payment'
                ], 400);
            }

            // Approve the ad
            $ad->approve(Auth::id(), $request->admin_comments);

            DB::commit();

            // Log the approval
            Log::info("Ad approved", [
                'ad_id' => $ad->id,
                'admin_id' => Auth::id(),
                'ad_name' => $ad->ad_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ad approved successfully and is now active'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving ad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve ad'
            ], 500);
        }
    }

    public function reject(Request $request, Ad $ad)
    {
        $request->validate([
            'admin_comments' => 'required|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            // Reject the ad
            $ad->reject(Auth::id(), $request->admin_comments);

            DB::commit();

            // Log the rejection
            Log::info("Ad rejected", [
                'ad_id' => $ad->id,
                'admin_id' => Auth::id(),
                'ad_name' => $ad->ad_name,
                'reason' => $request->admin_comments
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ad rejected successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting ad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject ad'
            ], 500);
        }
    }

    public function destroy(Ad $ad)
    {
        try {
            DB::beginTransaction();

            // Soft delete by setting deleted_flag
            $ad->update([
                'deleted_flag' => 'Y',
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ad deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting ad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ad'
            ], 500);
        }
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ad_ids' => 'required|array|min:1',
            'ad_ids.*' => 'exists:ads,id',
            'admin_comments' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $approved = 0;
            $failed = 0;

            foreach ($request->ad_ids as $adId) {
                $ad = Ad::find($adId);

                if ($ad && $ad->admin_status === 'pending') {
                    // Check if ad has completed payment
                    $hasPayment = $ad->payments()->where('status', 'completed')->exists();

                    if ($hasPayment) {
                        $ad->approve(Auth::id(), $request->admin_comments);
                        $approved++;
                    } else {
                        $failed++;
                    }
                }
            }

            DB::commit();

            $message = "Approved {$approved} ads";
            if ($failed > 0) {
                $message .= ", {$failed} ads failed (no payment or not pending)";
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk approving ads: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve ads'
            ], 500);
        }
    }

    public function bulkReject(Request $request)
    {
        $request->validate([
            'ad_ids' => 'required|array|min:1',
            'ad_ids.*' => 'exists:ads,id',
            'admin_comments' => 'required|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $rejected = 0;

            foreach ($request->ad_ids as $adId) {
                $ad = Ad::find($adId);

                if ($ad && $ad->admin_status === 'pending') {
                    $ad->reject(Auth::id(), $request->admin_comments);
                    $rejected++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Rejected {$rejected} ads"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk rejecting ads: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject ads'
            ], 500);
        }
    }

    public function export()
    {
        try {
            $ads = Ad::with(['user', 'reviewer', 'latestPayment'])
                ->where('deleted_flag', 'N')
                ->orderBy('created_at', 'desc')
                ->get();

            $csvData = [];
            $csvData[] = [
                'Ad ID', 'Ad Name', 'User', 'Type', 'Status', 'Admin Status',
                'Budget', 'Total Spent', 'Impressions', 'Clicks', 'CTR %',
                'Payment Status', 'Created Date', 'Reviewed Date', 'Reviewer'
            ];

            foreach ($ads as $ad) {
                $csvData[] = [
                    $ad->id,
                    $ad->ad_name,
                    $ad->user ? $ad->user->username : 'N/A',
                    ucfirst($ad->type),
                    ucfirst($ad->status),
                    ucfirst($ad->admin_status),
                    $ad->budget,
                    $ad->total_spent,
                    $ad->current_impressions,
                    $ad->clicks,
                    $ad->ctr,
                    $ad->latestPayment ? ucfirst($ad->latestPayment->status) : 'No Payment',
                    $ad->created_at->format('Y-m-d H:i:s'),
                    $ad->reviewed_at ? $ad->reviewed_at->format('Y-m-d H:i:s') : 'Not Reviewed',
                    $ad->reviewer ? $ad->reviewer->username : 'N/A'
                ];
            }

            $filename = 'ads_export_' . now()->format('Y_m_d_H_i_s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting ads: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to export ads'
            ], 500);
        }
    }

    public function pauseAd(Ad $ad)
    {
        try {
            if ($ad->pause()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ad paused successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot pause this ad'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error pausing ad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause ad'
            ], 500);
        }
    }

    public function resumeAd(Ad $ad)
    {
        try {
            if ($ad->resume()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ad resumed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot resume this ad'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error resuming ad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume ad'
            ], 500);
        }
    }

    public function stopAd(Ad $ad)
    {
        try {
            if ($ad->stop()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ad stopped successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot stop this ad'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error stopping ad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop ad'
            ], 500);
        }
    }

    /**
     * Get all countries for filter dropdown
     */
    public function getCountries()
    {
        try {
            $countries = \App\Models\Country::select('id', 'name', 'code')
                ->where('active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'countries' => $countries
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load countries'], 500);
        }
    }

    /**
     * Get all social circles for filter dropdown
     */
    public function getSocialCircles()
    {
        try {
            $socialCircles = \App\Models\SocialCircle::select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json($socialCircles);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load social circles'], 500);
        }
    }
}
