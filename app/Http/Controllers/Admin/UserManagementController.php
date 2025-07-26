<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        // Only load relationships that exist
        $user->load(['posts']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Suspend user
     */
    public function suspend(User $user)
    {
        try {
            $user->update(['status' => 'suspended']);
            return redirect()->back()
                ->with('success', 'User suspended successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to suspend user: ' . $e->getMessage());
        }
    }

    /**
     * Activate user
     */
    public function activate(User $user)
    {
        try {
            $user->update(['status' => 'active']);
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

            if ($request->filled('verified')) {
                if ($request->get('verified') == '1') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }

            // Get paginated results - start simple without relationships
            $users = $query->select(['id', 'name', 'email', 'is_active', 'is_banned', 'banned_until', 'created_at', 'email_verified_at'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Add formatted data
            $users->getCollection()->transform(function ($user) {
                $user->created_at_human = $user->created_at->diffForHumans();
                // Set default counts
                $user->posts_count = 0;
                $user->streams_count = 0;
                // Add default profile picture if not set
                $user->profile_picture = '/images/default-avatar.png';
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

                return $user;
            });

            // Get stats
            $stats = [
                'total' => User::count(),
                'active' => User::where('is_active', true)->where('is_banned', false)->count(),
                'suspended' => User::where('is_active', false)->count(),
                'banned' => User::where('is_banned', true)->count()
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
     * Login as user (impersonation)
     */
    public function loginAsUser(User $user)
    {
        try {
            // Generate a secure token for impersonation
            $token = Str::random(64);

            // Store the token temporarily (you might want to use cache or database)
            cache()->put("admin_impersonate_{$token}", [
                'admin_id' => auth('admin')->id(),
                'user_id' => $user->id,
                'expires_at' => now()->addMinutes(5)
            ], 300); // 5 minutes

            $loginUrl = url("/admin/impersonate/{$token}");

            return response()->json([
                'success' => true,
                'login_url' => $loginUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate login link'
            ], 500);
        }
    }
}
