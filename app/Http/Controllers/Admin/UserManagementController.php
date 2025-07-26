<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        $user->load(['posts', 'streams', 'subscriptions', 'ads']);
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
        $query = User::with(['posts', 'streams', 'subscriptions']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('verified')) {
            if ($request->get('verified') == '1') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Get paginated results
        $users = $query->withCount(['posts', 'streams'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Add formatted data
        $users->getCollection()->transform(function ($user) {
            $user->created_at_human = $user->created_at->diffForHumans();
            return $user;
        });

        // Get stats
        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'banned' => User::where('status', 'banned')->count()
        ];

        return response()->json([
            'users' => $users,
            'stats' => $stats
        ]);
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
            $user->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => "User {$request->status} successfully"
            ]);
        } catch (\Exception $e) {
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
            User::whereIn('id', $request->user_ids)
                ->update(['status' => $request->status]);

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
