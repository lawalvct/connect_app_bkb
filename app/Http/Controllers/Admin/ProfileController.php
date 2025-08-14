<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Show the profile settings page
     */
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.profile.index', compact('admin'));
    }

    /**
     * Update the admin's profile information
     */
    public function updateProfile(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins,email,' . $admin->id,
            'phone' => 'nullable|string|max:20',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old profile image if exists
            if ($admin->profile_image && Storage::disk('public')->exists($admin->profile_image)) {
                Storage::disk('public')->delete($admin->profile_image);
            }

            // Store new profile image
            $path = $request->file('profile_image')->store('admin/profiles', 'public');
            $data['profile_image'] = $path;
        }

        $admin->update($data);

        return redirect()->route('admin.profile.index')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Update the admin's password
     */
    public function updatePassword(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        // Check if current password is correct
        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $admin->update([
            'password' => Hash::make($request->password),
            'force_password_change' => false, // Reset if it was set
        ]);

        return redirect()->route('admin.profile.index')
            ->with('success', 'Password updated successfully!');
    }

    /**
     * Update notification preferences
     */
    public function updateNotifications(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'login_alerts' => 'boolean',
        ]);

        // Update permissions array to include notification preferences
        $permissions = $admin->permissions ?? [];
        $permissions['email_notifications'] = $request->boolean('email_notifications');
        $permissions['push_notifications'] = $request->boolean('push_notifications');
        $permissions['login_alerts'] = $request->boolean('login_alerts');

        $admin->update(['permissions' => $permissions]);

        return redirect()->route('admin.profile.index')
            ->with('success', 'Notification preferences updated successfully!');
    }

    /**
     * Delete profile image
     */
    public function deleteProfileImage()
    {
        $admin = Auth::guard('admin')->user();

        if ($admin->profile_image && Storage::disk('public')->exists($admin->profile_image)) {
            Storage::disk('public')->delete($admin->profile_image);
        }

        $admin->update(['profile_image' => null]);

        return redirect()->route('admin.profile.index')
            ->with('success', 'Profile image deleted successfully!');
    }

    /**
     * Get admin's activity log
     */
    public function activityLog()
    {
        $admin = Auth::guard('admin')->user();

        // This would typically fetch from an activity log table
        // For now, we'll just show login history
        $activities = [
            [
                'action' => 'Login',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => $admin->last_login_at ?? now(),
            ],
            // Add more activity tracking as needed
        ];

        return response()->json($activities);
    }
}
