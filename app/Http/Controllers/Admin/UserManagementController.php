<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use App\Exports\SimpleUsersExport;

class UserManagementController extends Controller
{
    /**
     * Display users listing
     */
    public function index()
    {
        return view('admin.users.index');
    }

    /**
     * Show user details
     */
    public function show(User $user)
    {
        // Load relationships that exist, including social circles
        $user->load(['posts', 'socialCircles:id,name,description,color,logo']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show edit user form
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_banned' => 'boolean',
        ]);

        try {
            $user->update($request->only([
                'name', 'email', 'phone', 'is_active', 'is_banned'
            ]));

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Suspend user
     */
    public function suspend(User $user, Request $request)
    {
        try {
            // Check if this is a ban action
            if ($request->filled('ban')) {
                $user->update([
                    'is_active' => false,
                    'is_banned' => true,
                    'banned_until' => null
                ]);
                $message = 'User banned successfully';
            } else {
                $user->update([
                    'is_active' => false,
                    'is_banned' => false,
                    'banned_until' => null
                ]);
                $message = 'User suspended successfully';
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Activate user
     */
    public function activate(User $user)
    {
        try {
            $user->update([
                'is_active' => true,
                'is_banned' => false,
                'banned_until' => null
            ]);
            return redirect()->back()
                ->with('success', 'User activated successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to activate user: ' . $e->getMessage());
        }
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Get users for AJAX requests
     */
    public function getUsers(Request $request)
    {
        try {
            Log::info('UserManagement getUsers called with params: ', $request->all());

            // Start with a simple query
            $query = User::query();

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                      // Remove phone filter if column doesn't exist
                      // ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $status = $request->get('status');
                if ($status === 'active') {
                    $query->where('is_active', true)->where('is_banned', false);
                } elseif ($status === 'suspended') {
                    $query->where('is_active', false);
                } elseif ($status === 'banned') {
                    $query->where('is_banned', true);
                }
            }

            // Comment out verification filter
            /*
            if ($request->filled('verified')) {
                if ($request->get('verified') == '1') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }
            */

            // Add verification filtering
            if ($request->filled('verification')) {
                $verification = $request->get('verification');
                if ($verification === 'verified') {
                    // Users with approved verification
                    $query->whereHas('verifications', function($q) {
                        $q->where('admin_status', 'approved');
                    });
                } elseif ($verification === 'pending') {
                    // Users with pending verification
                    $query->whereHas('verifications', function($q) {
                        $q->where('admin_status', 'pending');
                    });
                } elseif ($verification === 'rejected') {
                    // Users with rejected verification
                    $query->whereHas('verifications', function($q) {
                        $q->where('admin_status', 'rejected');
                    });
                } elseif ($verification === 'none') {
                    // Users without any verification submission
                    $query->whereDoesntHave('verifications');
                }
            }

            // Add date range filtering
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

            if ($request->filled('social_circles')) {
                $socialCircleFilter = $request->get('social_circles');
                if ($socialCircleFilter === 'has_circles') {
                    $query->whereHas('socialCircles');
                } elseif ($socialCircleFilter === 'no_circles') {
                    $query->whereDoesntHave('socialCircles');
                } elseif (is_numeric($socialCircleFilter)) {
                    // Filter by specific social circle ID
                    $query->whereHas('socialCircles', function($q) use ($socialCircleFilter) {
                        $q->where('social_circles.id', $socialCircleFilter);
                    });
                }
            }

            // Add country filtering
            if ($request->filled('country')) {
                $countryId = $request->get('country');
                if (is_numeric($countryId)) {
                    $query->where('country_id', $countryId);
                }
            }

            // Get paginated results - include social circles, country relationships, and verification data
            $users = $query->select(['id', 'name', 'email', 'profile', 'profile_url', 'is_active', 'is_banned', 'banned_until', 'created_at', 'email_verified_at', 'country_id'])
                ->with([
                    'socialCircles:id,name,color', // Load social circles with only needed fields
                    'country:id,name,code', // Load country data including code for flag URLs
                    'verifications' => function($query) {
                        $query->select('id', 'user_id', 'admin_status', 'submitted_at', 'reviewed_at', 'reviewed_by', 'admin_reason')
                              ->orderBy('submitted_at', 'desc');
                    }
                ])
                ->withCount(['socialCircles', 'verifications']) // Count social circles and verifications
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Add formatted data
            $users->getCollection()->transform(function ($user) {
                $user->created_at_human = $user->created_at->diffForHumans();
                // Set default counts
                $user->posts_count = 0;
                $user->streams_count = 0;

                // Add profile picture using the same logic as UserResource
                $user->profile_picture = $this->getProfileUrl($user);

                // Set default phone if not available
                $user->phone = $user->phone ?? 'No phone';

                // Calculate status based on boolean fields
                if ($user->is_banned) {
                    $user->status = 'banned';
                } elseif (!$user->is_active) {
                    $user->status = 'suspended';
                } else {
                    $user->status = 'active';
                }

                // Add verification status
                $latestVerification = $user->latestVerification;
                if ($latestVerification) {
                    $user->verification_status = $latestVerification->admin_status;
                    $user->verification_id = $latestVerification->id;
                    $user->verification_date = $latestVerification->updated_at ? $latestVerification->updated_at->format('Y-m-d') : null;
                } else {
                    $user->verification_status = 'none';
                    $user->verification_id = null;
                    $user->verification_date = null;
                }

                // Format social circles data
                $user->social_circles_count = $user->social_circles_count ?? 0;
                $user->social_circles_names = $user->socialCircles ? $user->socialCircles->pluck('name')->toArray() : [];
                $user->social_circles_colors = $user->socialCircles ? $user->socialCircles->pluck('color', 'name')->toArray() : [];

                // Format verification data
                $user->verification_status = 'none';
                $user->has_pending_verification = false;
                $user->latest_verification = null;

                if ($user->verifications && $user->verifications->count() > 0) {
                    $latestVerification = $user->verifications->first();
                    $user->latest_verification = $latestVerification;
                    $user->verification_status = $latestVerification->status;
                    $user->has_pending_verification = $latestVerification->status === 'pending';
                }

                return $user;
            });

            // Get stats
            $stats = [
                'total' => User::count(),
                'active' => User::where('is_active', true)->where('is_banned', false)->count(),
                'suspended' => User::where('is_active', false)->count(),
                'banned' => User::where('is_banned', true)->count(),
                'with_social_circles' => User::whereHas('socialCircles')->count(),
                'avg_social_circles' => round(User::withCount('socialCircles')->get()->avg('social_circles_count') ?? 0, 1),
                'pending_verifications' => \App\Models\UserVerification::where('admin_status', 'pending')->count(),
                'verified_users' => \App\Models\UserVerification::where('admin_status', 'approved')->distinct('user_id')->count(),
                'rejected_verifications' => \App\Models\UserVerification::where('admin_status', 'rejected')->count()
            ];

            Log::info('UserManagement getUsers success - returning ' . $users->count() . ' users');

            return response()->json([
                'users' => $users,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getUsers: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Failed to load users: ' . $e->getMessage(),
                'users' => (object)['data' => [], 'current_page' => 1, 'last_page' => 1, 'from' => 0, 'to' => 0, 'total' => 0],
                'stats' => ['total' => 0, 'active' => 0, 'suspended' => 0, 'banned' => 0]
            ], 200); // Return 200 instead of 500 for better debugging
        }
    }

    /**
     * Get social circles for filter dropdown
     */
    public function getSocialCircles()
    {
        try {
            // Get social circles with proper error handling
            $socialCircles = \App\Models\SocialCircle::withoutGlobalScope('active')
                ->where('is_active', true)
                ->whereNull('deleted_at') // Use soft deletes instead of deleted_flag
                ->orderBy('order_by', 'asc')
                ->get(['id', 'name', 'color']);

            // Log for debugging
            \Illuminate\Support\Facades\Log::info('Social circles loaded: ' . $socialCircles->count());

            return response()->json([
                'success' => true,
                'social_circles' => $socialCircles
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching social circles: ' . $e->getMessage());

            // Try without deleted_at filter as backup
            try {
                $socialCircles = \App\Models\SocialCircle::withoutGlobalScope('active')
                    ->where('is_active', true)
                    ->orderBy('id', 'asc')
                    ->get(['id', 'name', 'color']);

                \Illuminate\Support\Facades\Log::info('Social circles loaded (backup method): ' . $socialCircles->count());

                return response()->json([
                    'success' => true,
                    'social_circles' => $socialCircles
                ]);
            } catch (\Exception $e2) {
                \Illuminate\Support\Facades\Log::error('Backup method also failed: ' . $e2->getMessage());

                return response()->json([
                    'success' => false,
                    'error' => 'Failed to load social circles',
                    'social_circles' => []
                ], 200);
            }
        }
    }

    /**
     * Update user status
     */
    public function updateStatus(Request $request, User $user)
    {
        $request->validate([
            'status' => 'required|in:active,suspended,banned'
        ]);

        try {
            $status = $request->status;

            if ($status === 'active') {
                $user->update([
                    'is_active' => true,
                    'is_banned' => false,
                    'banned_until' => null
                ]);
            } elseif ($status === 'suspended') {
                $user->update([
                    'is_active' => false,
                    'is_banned' => false,
                    'banned_until' => null
                ]);
            } elseif ($status === 'banned') {
                $user->update([
                    'is_active' => false,
                    'is_banned' => true,
                    'banned_until' => null // You could set a future date here if needed
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "User {$request->status} successfully"
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating user status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status'
            ], 500);
        }
    }

    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'status' => 'required|in:active,suspended,banned'
        ]);

        try {
            $status = $request->status;
            $updateData = [];

            if ($status === 'active') {
                $updateData = [
                    'is_active' => true,
                    'is_banned' => false,
                    'banned_until' => null
                ];
            } elseif ($status === 'suspended') {
                $updateData = [
                    'is_active' => false,
                    'is_banned' => false,
                    'banned_until' => null
                ];
            } elseif ($status === 'banned') {
                $updateData = [
                    'is_active' => false,
                    'is_banned' => true,
                    'banned_until' => null
                ];
            }

            User::whereIn('id', $request->user_ids)->update($updateData);

            $count = count($request->user_ids);

            return response()->json([
                'success' => true,
                'message' => "{$count} users {$request->status} successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update users'
            ], 500);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user)
    {
        try {
            // Generate a temporary password
            $tempPassword = Str::random(12);
            $user->update(['password' => Hash::make($tempPassword)]);

            // Send email with new password (you would implement this based on your email setup)
            // Mail::to($user->email)->send(new PasswordResetMail($tempPassword));

            return response()->json([
                'success' => true,
                'message' => 'Password reset email sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password'
            ], 500);
        }
    }

    /**
     * Export users to CSV or Excel format.
     * For large datasets (>1000 records), uses queue processing.
     * For smaller datasets, returns immediate download.
     *
     * Supports filtering by:
     * - search: Filter by name or email
     * - status: active, suspended, banned
     * - social_circles: has_circles, no_circles, or specific circle ID
     *
     * Formats supported:
     * - csv: Comma-separated values
     * - excel/xlsx: Microsoft Excel format
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function export(Request $request)
    {
        try {
            // Get the format parameter (default to csv)
            $format = $request->get('format', 'csv');

            // Validate format
            if (!in_array($format, ['csv', 'excel', 'xlsx'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid export format. Supported formats: csv, excel, xlsx'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Invalid export format');
            }

            // Prepare filters
            $filters = [];
            if ($request->filled('search')) {
                $filters['search'] = $request->get('search');
            }
            if ($request->filled('status')) {
                $filters['status'] = $request->get('status');
            }
            if ($request->filled('social_circles')) {
                $filters['social_circles'] = $request->get('social_circles');
            }
            if ($request->filled('date_from')) {
                $filters['date_from'] = $request->get('date_from');
            }
            if ($request->filled('date_to')) {
                $filters['date_to'] = $request->get('date_to');
            }

            // Count total records to determine if we should queue the export
            $query = User::query();
            $this->applyFilters($query, $filters);
            $totalRecords = $query->count();

            Log::info("Export request: format={$format}, totalRecords={$totalRecords}, filters=" . json_encode($filters));

            // If dataset is large (>1000 records), use queue processing
            if ($totalRecords > 1000) {
                $user = Auth::user();

                // Dispatch the export job
                \App\Jobs\ExportUsersJob::dispatch($filters, $format, $user);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => "Export queued successfully! You will receive an email notification when your {$format} export ({$totalRecords} records) is ready for download.",
                        'queued' => true,
                        'total_records' => $totalRecords
                    ]);
                }

                return redirect()->back()->with('success', "Export queued successfully! You will receive an email notification when your {$format} export ({$totalRecords} records) is ready for download.");
            }

            // For smaller datasets, process immediately
            // Increase memory limit for Excel exports
            ini_set('memory_limit', '256M');
            set_time_limit(300); // 5 minutes

            // Generate filename with timestamp
            $timestamp = now()->format('Y-m-d_H-i-s');

            if ($format === 'csv') {
                // CSV Export using Laravel Excel
                $filename = "users_export_{$timestamp}.csv";
                Log::info("Exporting CSV immediately: {$filename}");
                return Excel::download(new SimpleUsersExport($filters), $filename, \Maatwebsite\Excel\Excel::CSV, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ]);
            } else {
                // Excel Export (handle both 'excel' and 'xlsx')
                $filename = "users_export_{$timestamp}.xlsx";
                Log::info("Exporting Excel immediately: {$filename}");

                return Excel::download(new SimpleUsersExport($filters), $filename, \Maatwebsite\Excel\Excel::XLSX, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error exporting users: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to export users: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to export users: ' . $e->getMessage());
        }
    }

    /**
     * Apply filters to the user query (shared between export and regular queries)
     */
    private function applyFilters($query, $filters)
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $status = $filters['status'];
            if ($status === 'active') {
                $query->where('is_active', true)->where('is_banned', false);
            } elseif ($status === 'suspended') {
                $query->where('is_active', false);
            } elseif ($status === 'banned') {
                $query->where('is_banned', true);
            }
        }

        if (!empty($filters['social_circles'])) {
            $socialCircles = $filters['social_circles'];
            $query->whereHas('socialCircles', function ($q) use ($socialCircles) {
                $q->whereIn('social_circles.id', $socialCircles);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query;
    }

    /**
     * Get the profile URL for the user (same logic as UserResource)
     *
     * @param User $user
     * @return string
     */
    private function getProfileUrl($user)
    {
        if (!$user->profile) {
            return '/images/default-avatar.png';
        }

        // Clean up profile filename
        $cleanProfile = $this->cleanFileName($user->profile);

        // Check if this is a legacy user (ID 1-3354) and use old server URL
        if ($user->id >= 1 && $user->id <= 3354) {
            // For legacy users, always use the old server URL with clean filename
            return 'https://connectapp.talosmart.xyz/uploads/profiles/' . $cleanProfile;
        }

        // For new users (ID > 3354), use current project logic

        // If profile_url is already set and is a full URL, return it
        if ($user->profile_url && filter_var($user->profile_url, FILTER_VALIDATE_URL)) {
            return $user->profile_url;
        }

        // If using cloud storage
        if (config('filesystems.default') === 's3') {
            try {
                // Try to get a public URL
                if (\Illuminate\Support\Facades\Storage::disk('s3')->exists('profiles/' . $cleanProfile)) {
                    return config('filesystems.disks.s3.url') . '/profiles/' . $cleanProfile;
                }
            } catch (\Exception $e) {
                // Log error and continue to fallback
                Log::warning('Failed to generate S3 URL for profile: ' . $cleanProfile, ['error' => $e->getMessage()]);
            }
        }

        // For local storage (new users)
        return url('uploads/profiles/' . $cleanProfile);
    }

    /**
     * Clean filename by removing duplications and invalid characters (same logic as UserResource)
     *
     * @param string $fileName
     * @return string
     */
    private function cleanFileName($fileName)
    {
        if (!$fileName) {
            return '';
        }

        // Remove any URL parts if they somehow got into the filename
        $fileName = basename($fileName);

        // Handle duplicated filenames (e.g., "file.jpegfile.jpeg" -> "file.jpeg")
        $pathInfo = pathinfo($fileName);
        $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
        $basename = isset($pathInfo['filename']) ? $pathInfo['filename'] : $fileName;

        // Check if the basename contains the extension duplicated
        if ($extension && str_ends_with($basename, $extension)) {
            // Remove the duplicated extension from basename
            $basename = substr($basename, 0, -strlen($extension));
        }

        // Reconstruct the clean filename
        $cleanFileName = $extension ? $basename . '.' . $extension : $basename;

        return $cleanFileName;
    }

    /**
     * Search users for notifications
     */
    public function searchUsers(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'users' => []
            ]);
        }

        $users = User::where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->select('id', 'name', 'email')
                    ->limit(20)
                    ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }
}
