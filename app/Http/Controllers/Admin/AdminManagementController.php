<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AdminManagementController extends Controller
{
    /**
     * Display a listing of admins
     */
    public function index()
    {
        // Check permissions
        $currentAdmin = Auth::guard('admin')->user();
        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && !$currentAdmin->hasRole(Admin::ROLE_ADMIN)) {
            abort(403, 'Unauthorized to manage admins');
        }

        return view('admin.admins.index');
    }

    /**
     * Get admins data for DataTables (AJAX)
     */
    public function getAdmins(Request $request)
    {
        $currentAdmin = Auth::guard('admin')->user();

        $query = Admin::query();

        // Super admin can see all, regular admin cannot see super admins
        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN)) {
            $query->where('role', '!=', Admin::ROLE_SUPER_ADMIN);
        }

        // Search functionality
        if ($request->has('search') && $request->search['value']) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('role', 'LIKE', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $totalRecords = $query->count();

        // Ordering
        if ($request->has('order')) {
            $orderColumn = $request->columns[$request->order[0]['column']]['data'];
            $orderDirection = $request->order[0]['dir'];
            $query->orderBy($orderColumn, $orderDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        if ($request->has('length') && $request->length > 0) {
            $query->skip($request->start)->take($request->length);
        }

        $admins = $query->get();

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Admin::count(),
            'recordsFiltered' => $totalRecords,
            'data' => $admins->map(function($admin) use ($currentAdmin) {
                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->getRoleDisplayName(),
                    'status' => $admin->status,
                    'last_login_at' => $admin->last_login_at ? $admin->last_login_at->diffForHumans() : 'Never',
                    'created_at' => $admin->created_at->format('M d, Y'),
                    'can_edit' => $currentAdmin->id !== $admin->id && ($currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) || $admin->role !== Admin::ROLE_SUPER_ADMIN),
                    'can_delete' => $currentAdmin->id !== $admin->id && ($currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) || $admin->role !== Admin::ROLE_SUPER_ADMIN),
                    'profile_image' => $admin->profile_image ? Storage::url($admin->profile_image) : null,
                ];
            })
        ]);
    }

    /**
     * Show the form for creating a new admin
     */
    public function create()
    {
        $currentAdmin = Auth::guard('admin')->user();
        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && !$currentAdmin->hasRole(Admin::ROLE_ADMIN)) {
            abort(403, 'Unauthorized to create admins');
        }

        return view('admin.admins.create');
    }

    /**
     * Store a newly created admin
     */
    public function store(Request $request)
    {
        $currentAdmin = Auth::guard('admin')->user();
        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && !$currentAdmin->hasRole(Admin::ROLE_ADMIN)) {
            abort(403, 'Unauthorized to create admins');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,moderator,content_manager',
            'phone' => 'nullable|string|max:20',
            'permissions' => 'nullable|array',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        // Regular admin cannot create super admin
        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && $request->role === Admin::ROLE_SUPER_ADMIN) {
            $validator->errors()->add('role', 'You cannot create super admin accounts.');
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $adminData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'permissions' => $request->permissions ?? [],
            'status' => $request->status,
            'force_password_change' => $request->has('force_password_change'),
        ];

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $filename = 'admin_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Create ImageManager instance
            $manager = new ImageManager(new Driver());

            // Resize and save image
            $resizedImage = $manager->read($image)->resize(200, 200)->encode();
            Storage::disk('public')->put('admin/profiles/' . $filename, $resizedImage);

            $adminData['profile_image'] = 'admin/profiles/' . $filename;
        }

        Admin::create($adminData);

        return redirect()->route('admin.admins.index')->with('success', 'Admin created successfully!');
    }

    /**
     * Display the specified admin
     */
    public function show(Admin $admin)
    {
        $currentAdmin = Auth::guard('admin')->user();

        // Check if current admin can view this admin
        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && $admin->role === Admin::ROLE_SUPER_ADMIN) {
            abort(403, 'Unauthorized to view this admin');
        }

        return view('admin.admins.show', compact('admin'));
    }

    /**
     * Show the form for editing the specified admin
     */
    public function edit(Admin $admin)
    {
        $currentAdmin = Auth::guard('admin')->user();

        // Check permissions
        if ($currentAdmin->id === $admin->id) {
            // Admins can edit their own profile with limited fields
            return view('admin.admins.edit-profile', compact('admin'));
        }

        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && !$currentAdmin->hasRole(Admin::ROLE_ADMIN)) {
            abort(403, 'Unauthorized to edit admins');
        }

        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && $admin->role === Admin::ROLE_SUPER_ADMIN) {
            abort(403, 'Unauthorized to edit super admin');
        }

        return view('admin.admins.edit', compact('admin'));
    }

    /**
     * Update the specified admin
     */
    public function update(Request $request, Admin $admin)
    {
        $currentAdmin = Auth::guard('admin')->user();
        $isOwnProfile = $currentAdmin->id === $admin->id;

        // Check permissions
        if (!$isOwnProfile && !$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && !$currentAdmin->hasRole(Admin::ROLE_ADMIN)) {
            abort(403, 'Unauthorized to update admins');
        }

        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && $admin->role === Admin::ROLE_SUPER_ADMIN) {
            abort(403, 'Unauthorized to update super admin');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins,email,' . $admin->id,
            'phone' => 'nullable|string|max:20',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        // Add role and permission rules only for admin management (not own profile)
        if (!$isOwnProfile) {
            $rules['role'] = 'required|in:admin,moderator,content_manager';
            $rules['permissions'] = 'nullable|array';
            $rules['status'] = 'required|in:active,inactive,suspended';
        }

        // Add password rules only if password is being changed
        if ($request->filled('password')) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        $validator = Validator::make($request->all(), $rules);

        // Regular admin cannot assign super admin role
        if (!$isOwnProfile && !$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && $request->role === Admin::ROLE_SUPER_ADMIN) {
            $validator->errors()->add('role', 'You cannot assign super admin role.');
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        // Add admin-specific fields if not editing own profile
        if (!$isOwnProfile) {
            $updateData['role'] = $request->role;
            $updateData['permissions'] = $request->permissions ?? [];
            $updateData['status'] = $request->status;
            $updateData['force_password_change'] = $request->has('force_password_change');
        }

        // Update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image
            if ($admin->profile_image) {
                Storage::disk('public')->delete($admin->profile_image);
            }

            $image = $request->file('profile_image');
            $filename = 'admin_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Create ImageManager instance
            $manager = new ImageManager(new Driver());

            // Resize and save image
            $resizedImage = $manager->read($image)->resize(200, 200)->encode();
            Storage::disk('public')->put('admin/profiles/' . $filename, $resizedImage);

            $updateData['profile_image'] = 'admin/profiles/' . $filename;
        }

        $admin->update($updateData);

        $redirectRoute = $isOwnProfile ? 'admin.dashboard' : 'admin.admins.index';
        return redirect()->route($redirectRoute)->with('success', 'Admin updated successfully!');
    }

    /**
     * Update admin status
     */
    public function updateStatus(Request $request, Admin $admin)
    {
        $currentAdmin = Auth::guard('admin')->user();

        if ($currentAdmin->id === $admin->id) {
            return response()->json(['error' => 'Cannot change your own status'], 403);
        }

        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && !$currentAdmin->hasRole(Admin::ROLE_ADMIN)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && $admin->role === Admin::ROLE_SUPER_ADMIN) {
            return response()->json(['error' => 'Cannot change super admin status'], 403);
        }

        $request->validate([
            'status' => 'required|in:active,inactive,suspended'
        ]);

        $admin->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
    }

    /**
     * Reset admin password
     */
    public function resetPassword(Request $request, Admin $admin)
    {
        $currentAdmin = Auth::guard('admin')->user();

        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && !$currentAdmin->hasRole(Admin::ROLE_ADMIN)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && $admin->role === Admin::ROLE_SUPER_ADMIN) {
            return response()->json(['error' => 'Cannot reset super admin password'], 403);
        }

        $newPassword = \Illuminate\Support\Str::random(12);

        $admin->update([
            'password' => Hash::make($newPassword),
            'force_password_change' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
            'new_password' => $newPassword
        ]);
    }

    /**
     * Bulk update admin status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $currentAdmin = Auth::guard('admin')->user();

        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN) && !$currentAdmin->hasRole(Admin::ROLE_ADMIN)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'admin_ids' => 'required|array',
            'status' => 'required|in:active,inactive,suspended'
        ]);

        $adminIds = collect($request->admin_ids)->filter(function($id) use ($currentAdmin) {
            return $id != $currentAdmin->id; // Cannot change own status
        });

        $query = Admin::whereIn('id', $adminIds);

        // Regular admin cannot change super admin status
        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN)) {
            $query->where('role', '!=', Admin::ROLE_SUPER_ADMIN);
        }

        $updated = $query->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "Updated {$updated} admin(s) successfully"
        ]);
    }

    /**
     * Remove the specified admin
     */
    public function destroy(Admin $admin)
    {
        $currentAdmin = Auth::guard('admin')->user();

        if ($currentAdmin->id === $admin->id) {
            return response()->json(['error' => 'Cannot delete your own account'], 403);
        }

        if (!$currentAdmin->hasRole(Admin::ROLE_SUPER_ADMIN)) {
            return response()->json(['error' => 'Only super admins can delete admin accounts'], 403);
        }

        // Delete profile image if exists
        if ($admin->profile_image) {
            Storage::disk('public')->delete($admin->profile_image);
        }

        $admin->delete();

        return response()->json(['success' => true, 'message' => 'Admin deleted successfully']);
    }
}
