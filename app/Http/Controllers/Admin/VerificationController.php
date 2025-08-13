<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware(function ($request, $next) {
            $admin = Auth::guard('admin')->user();
            if (!$admin->hasPermission('verify_users') && !$admin->canManageUsers()) {
                abort(403, 'Unauthorized to manage user verifications');
            }
            return $next($request);
        });
    }

    /**
     * Get pending verifications count
     */
    public function getPendingCount()
    {
        try {
            $count = UserVerification::pending()->count();

            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get pending count'
            ], 500);
        }
    }

    /**
     * Get all pending verifications
     */
    public function getPendingVerifications()
    {
        try {
            $verifications = UserVerification::pending()
                ->with(['user:id,name,email,profile'])
                ->orderBy('submitted_at', 'asc')
                ->get()
                ->map(function ($verification) {
                    return [
                        'id' => $verification->id,
                        'id_card_type' => $verification->id_card_type,
                        'id_card_image_url' => $verification->id_card_image_url,
                        'submitted_at' => $verification->submitted_at,
                        'admin_status' => $verification->admin_status,
                        'user' => [
                            'id' => $verification->user->id,
                            'name' => $verification->user->name,
                            'email' => $verification->user->email,
                            'profile_picture' => $verification->user->profile ? url('uploads/profiles/' . $verification->user->profile) : null,
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'verifications' => $verifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load pending verifications'
            ], 500);
        }
    }

    /**
     * Approve a verification
     */
    public function approveVerification($id)
    {
        try {
            DB::beginTransaction();

            $verification = UserVerification::findOrFail($id);

            if ($verification->admin_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification is not in pending status'
                ], 400);
            }

            // Approve the verification
            $verification->approve(Auth::id(), 'Approved by admin');

            // Update user's is_verified status
            $verification->user->update(['is_verified' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Verification approved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve verification'
            ], 500);
        }
    }

    /**
     * Reject a verification
     */
    public function rejectVerification(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $verification = UserVerification::findOrFail($id);

            if ($verification->admin_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification is not in pending status'
                ], 400);
            }

            // Reject the verification
            $verification->reject(Auth::id(), $request->reason);

            // Ensure user's is_verified remains false
            $verification->user->update(['is_verified' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Verification rejected successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject verification'
            ], 500);
        }
    }
}
