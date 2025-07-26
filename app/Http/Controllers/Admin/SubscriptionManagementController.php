<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscribe;
use App\Models\UserSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionManagementController extends Controller
{
    public function index()
    {
        return view('admin.subscriptions.index');
    }

    public function plansIndex()
    {
        return view('admin.subscriptions.plans.index');
    }

    public function show(UserSubscription $subscription)
    {
        dd(5);
        $subscription->load(['user', 'subscription']);
        return view('admin.subscriptions.show', compact('subscription'));
    }

    public function showPlan(Subscribe $plan)
    {
        $plan->load(['userSubscriptions.user', 'activeUserSubscriptions']);
        return view('admin.subscriptions.plans.show', compact('plan'));
    }

    public function getSubscriptions(Request $request)
    {
        try {
            $query = UserSubscription::with(['user', 'subscription'])
                ->where('deleted_flag', 'N');

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('transaction_reference', 'like', "%{$search}%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('username', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      })
                      ->orWhereHas('subscription', function($subQuery) use ($search) {
                          $subQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->filled('subscription_plan')) {
                $query->where('subscription_id', $request->subscription_plan);
            }

            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->filled('date_range')) {
                $dateRange = $request->date_range;
                switch ($dateRange) {
                    case 'today':
                        $query->whereDate('created_at', today());
                        break;
                    case 'yesterday':
                        $query->whereDate('created_at', Carbon::yesterday());
                        break;
                    case 'this_week':
                        $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'this_month':
                        $query->whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year);
                        break;
                    case 'active':
                        $query->where('status', 'active')
                              ->where('expires_at', '>', now());
                        break;
                    case 'expiring_soon':
                        $query->where('status', 'active')
                              ->whereBetween('expires_at', [now(), now()->addDays(7)]);
                        break;
                    case 'expired':
                        $query->where('expires_at', '<=', now());
                        break;
                }
            }

            // Sort by creation date (newest first)
            $query->orderBy('created_at', 'desc');

            $subscriptions = $query->paginate(20);

            return response()->json([
                'success' => true,
                'subscriptions' => $subscriptions
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching subscriptions: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch subscriptions'
            ], 500);
        }
    }

    public function getStats()
    {
        try {
            $stats = [
                'total_subscriptions' => UserSubscription::where('deleted_flag', 'N')->count(),
                'active_subscriptions' => UserSubscription::where('status', 'active')
                    ->where('deleted_flag', 'N')
                    ->where('expires_at', '>', now())
                    ->count(),
                'expired_subscriptions' => UserSubscription::where('expires_at', '<=', now())
                    ->where('deleted_flag', 'N')
                    ->count(),
                'cancelled_subscriptions' => UserSubscription::where('status', 'cancelled')
                    ->where('deleted_flag', 'N')
                    ->count(),
                'total_revenue' => UserSubscription::where('payment_status', 'completed')
                    ->where('deleted_flag', 'N')
                    ->sum('amount'),
                'monthly_revenue' => UserSubscription::where('payment_status', 'completed')
                    ->where('deleted_flag', 'N')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount'),
                'pending_payments' => UserSubscription::where('payment_status', 'pending')
                    ->where('deleted_flag', 'N')
                    ->count(),
                'expiring_soon' => UserSubscription::where('status', 'active')
                    ->where('deleted_flag', 'N')
                    ->whereBetween('expires_at', [now(), now()->addDays(7)])
                    ->count(),
            ];

            // Payment method breakdown
            $stats['payment_methods'] = UserSubscription::where('deleted_flag', 'N')
                ->groupBy('payment_method')
                ->selectRaw('payment_method, count(*) as count')
                ->pluck('count', 'payment_method')
                ->toArray();

            // Plan popularity
            $stats['plan_breakdown'] = UserSubscription::with('subscription')
                ->where('deleted_flag', 'N')
                ->get()
                ->groupBy('subscription.name')
                ->map(function($group) {
                    return $group->count();
                })
                ->toArray();

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Error fetching subscription stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    public function getPlans(Request $request)
    {
        try {
            Log::info('getPlans called', ['request_params' => $request->all()]);

            $query = Subscribe::withCount([
                'userSubscriptions',
                'userSubscriptions as active_user_subscriptions_count' => function ($query) {
                    $query->where('status', 'active')
                          ->where('expires_at', '>', now())
                          ->where('deleted_flag', 'N');
                }
            ]);

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
                Log::info('Applied search filter', ['search' => $search]);
            }

            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } else {
                    $query->where('is_active', false);
                }
                Log::info('Applied status filter', ['status' => $request->status]);
            }

            $query->ordered();

            $perPage = $request->get('per_page', 20);
            $plans = $query->paginate($perPage);

            Log::info('getPlans success', [
                'plans_count' => $plans->count(),
                'total' => $plans->total(),
                'current_page' => $plans->currentPage(),
                'per_page' => $plans->perPage(),
                'first_plan_sample' => $plans->count() > 0 ? $plans->first()->toArray() : null
            ]);

            return response()->json([
                'success' => true,
                'plans' => $plans
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching subscription plans: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch subscription plans',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPlanStats()
    {
        try {
            $stats = [
                'total_plans' => Subscribe::count(),
                'active_plans' => Subscribe::where('is_active', true)->count(),
                'inactive_plans' => Subscribe::where('is_active', false)->count(),
                'total_subscribers' => UserSubscription::where('status', 'active')
                    ->where('deleted_flag', 'N')
                    ->where('expires_at', '>', now())
                    ->distinct('user_id')
                    ->count(),
                'total_plan_revenue' => UserSubscription::where('payment_status', 'completed')
                    ->where('deleted_flag', 'N')
                    ->sum('amount'),
                'avg_plan_price' => Subscribe::where('is_active', true)->avg('price'),
                'most_popular_plan' => '',
                'subscription_growth' => 0,
            ];

            // Find most popular plan
            $popularPlan = Subscribe::withCount(['activeUserSubscriptions'])
                ->orderBy('active_user_subscriptions_count', 'desc')
                ->first();

            if ($popularPlan) {
                $stats['most_popular_plan'] = $popularPlan->name;
            }

            // Calculate growth (subscriptions this month vs last month)
            $thisMonth = UserSubscription::where('status', 'active')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $lastMonth = UserSubscription::where('status', 'active')
                ->whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->count();

            if ($lastMonth > 0) {
                $stats['subscription_growth'] = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
            }

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Error fetching plan stats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch statistics'
            ], 500);
        }
    }

    public function updateSubscriptionStatus(Request $request, UserSubscription $subscription)
    {
        $request->validate([
            'status' => 'required|in:active,cancelled,expired',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $subscription->status;
            $subscription->update([
                'status' => $request->status,
                'updated_by' => Auth::id(),
                'cancelled_at' => $request->status === 'cancelled' ? now() : null,
            ]);

            // Log the status change
            Log::info("Subscription status updated", [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'admin_id' => Auth::id(),
                'reason' => $request->reason
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription status updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating subscription status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription status'
            ], 500);
        }
    }

    public function updatePlanStatus(Request $request, Subscribe $plan)
    {
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        try {
            $plan->update([
                'is_active' => $request->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plan status updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating plan status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update plan status'
            ], 500);
        }
    }

    public function extendSubscription(Request $request, UserSubscription $subscription)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $oldExpiry = $subscription->expires_at;
            $newExpiry = $subscription->expires_at->addDays($request->days);

            $subscription->update([
                'expires_at' => $newExpiry,
                'updated_by' => Auth::id()
            ]);

            // Log the extension
            Log::info("Subscription extended", [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'old_expiry' => $oldExpiry,
                'new_expiry' => $newExpiry,
                'days_added' => $request->days,
                'admin_id' => Auth::id(),
                'reason' => $request->reason
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Subscription extended by {$request->days} days"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error extending subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend subscription'
            ], 500);
        }
    }

    public function export()
    {
        try {
            $subscriptions = UserSubscription::with(['user', 'subscription'])
                ->where('deleted_flag', 'N')
                ->orderBy('created_at', 'desc')
                ->get();

            $csvData = [];
            $csvData[] = [
                'ID', 'User', 'Email', 'Plan', 'Amount', 'Currency', 'Payment Method',
                'Payment Status', 'Subscription Status', 'Started At', 'Expires At',
                'Transaction Reference', 'Created Date'
            ];

            foreach ($subscriptions as $subscription) {
                $csvData[] = [
                    $subscription->id,
                    $subscription->user ? $subscription->user->username : 'N/A',
                    $subscription->user ? $subscription->user->email : 'N/A',
                    $subscription->subscription ? $subscription->subscription->name : 'N/A',
                    $subscription->amount,
                    $subscription->currency,
                    ucfirst($subscription->payment_method),
                    ucfirst($subscription->payment_status),
                    ucfirst($subscription->status),
                    $subscription->started_at ? $subscription->started_at->format('Y-m-d H:i:s') : 'N/A',
                    $subscription->expires_at ? $subscription->expires_at->format('Y-m-d H:i:s') : 'N/A',
                    $subscription->transaction_reference ?? 'N/A',
                    $subscription->created_at->format('Y-m-d H:i:s')
                ];
            }

            $filename = 'subscriptions_export_' . now()->format('Y_m_d_H_i_s') . '.csv';

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
            Log::error('Error exporting subscriptions: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to export subscriptions'
            ], 500);
        }
    }

    public function exportPlans()
    {
        try {
            $plans = Subscribe::withCount(['userSubscriptions', 'activeUserSubscriptions'])
                ->ordered()
                ->get();

            $csvData = [];
            $csvData[] = [
                'ID', 'Name', 'Description', 'Price', 'Currency', 'Duration (Days)',
                'Features', 'Status', 'Total Subscribers', 'Active Subscribers',
                'Sort Order', 'Created Date'
            ];

            foreach ($plans as $plan) {
                $csvData[] = [
                    $plan->id,
                    $plan->name,
                    $plan->description,
                    $plan->price,
                    $plan->currency,
                    $plan->duration_days,
                    implode(', ', $plan->features ?? []),
                    $plan->is_active ? 'Active' : 'Inactive',
                    $plan->user_subscriptions_count,
                    $plan->active_user_subscriptions_count,
                    $plan->sort_order,
                    $plan->created_at->format('Y-m-d H:i:s')
                ];
            }

            $filename = 'subscription_plans_export_' . now()->format('Y_m_d_H_i_s') . '.csv';

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
            Log::error('Error exporting plans: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to export plans'
            ], 500);
        }
    }
}
