@extends('admin.layouts.app')

@section('page-title', 'Create Admin')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-semibold text-gray-900">Create New Administrator</h1>
            <p class="text-gray-600 mt-1">Add a new administrator account with specific roles and permissions</p>
        </div>
        <a href="{{ route('admin.admins.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Admins
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form action="{{ route('admin.admins.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Personal Information -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-medium text-gray-900">Personal Information</h5>
                    </div>
                    <div class="p-6"
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Profile Image -->
                            <div class="md:col-span-2 mb-4">
                                <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-2">Profile Image</label>
                                <div class="flex items-center space-x-4">
                                    <div id="imagePreview" class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center border-2 border-dashed border-gray-300">
                                        <i class="fas fa-user text-gray-400 text-2xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('profile_image') border-red-300 @enderror"
                                               id="profile_image" name="profile_image" accept="image/*">
                                        <p class="text-sm text-gray-500 mt-1">Maximum file size: 2MB. Supported formats: JPEG, PNG, JPG, GIF</p>
                                        @error('profile_image')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-300 @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('email') border-red-300 @enderror"
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('phone') border-red-300 @enderror"
                                       id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-300 @enderror" id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-medium text-gray-900">Account Security</h5>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Password -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                                <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('password') border-red-300 @enderror"
                                       id="password" name="password" required>
                                <p class="text-sm text-gray-500 mt-1">Minimum 8 characters</p>
                                @error('password')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password <span class="text-red-500">*</span></label>
                                <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                       id="password_confirmation" name="password_confirmation" required>
                            </div>

                            <!-- Force Password Change -->
                            <div class="md:col-span-2">
                                <label class="flex items-center">
                                    <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary"
                                           id="force_password_change" name="force_password_change" value="1" {{ old('force_password_change') ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700">Force password change on first login</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role & Permissions -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-medium text-gray-900">Role & Permissions</h5>
                    </div>
                    <div class="p-6">
                        <!-- Role Selection -->
                        <div class="mb-6">
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('role') border-red-300 @enderror" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                                <option value="moderator" {{ old('role') === 'moderator' ? 'selected' : '' }}>Moderator</option>
                                <option value="content_manager" {{ old('role') === 'content_manager' ? 'selected' : '' }}>Content Manager</option>
                            </select>
                            @error('role')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Role Descriptions -->
                        <div class="role-descriptions mb-6">
                            <div class="role-desc hidden" id="admin-desc">
                                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-info-circle text-blue-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-700">
                                                <strong>Administrator:</strong> Full access to user management, content moderation,
                                                analytics, and most system settings. Cannot manage other administrators or super admins.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="role-desc hidden" id="moderator-desc">
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700">
                                                <strong>Moderator:</strong> Access to content moderation, user management (limited),
                                                and basic analytics. Cannot access system settings or admin management.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="role-desc hidden" id="content_manager-desc">
                                <div class="bg-green-50 border-l-4 border-green-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-check-circle text-green-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-green-700">
                                                <strong>Content Manager:</strong> Access to content management, story/post moderation,
                                                and content analytics. Limited user management capabilities.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Permissions -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-4">Additional Permissions</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary"
                                               id="view_analytics" name="permissions[]" value="view_analytics"
                                               {{ in_array('view_analytics', old('permissions', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">View Analytics</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary"
                                               id="manage_ads" name="permissions[]" value="manage_ads"
                                               {{ in_array('manage_ads', old('permissions', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">Manage Advertisements</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary"
                                               id="manage_subscriptions" name="permissions[]" value="manage_subscriptions"
                                               {{ in_array('manage_subscriptions', old('permissions', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">Manage Subscriptions</span>
                                    </label>
                                </div>
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary"
                                               id="send_notifications" name="permissions[]" value="send_notifications"
                                               {{ in_array('send_notifications', old('permissions', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">Send Notifications</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary"
                                               id="view_reports" name="permissions[]" value="view_reports"
                                               {{ in_array('view_reports', old('permissions', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">View Reports</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary"
                                               id="export_data" name="permissions[]" value="export_data"
                                               {{ in_array('export_data', old('permissions', [])) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">Export Data</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('admin.admins.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition-colors inline-flex items-center">
                                <i class="fas fa-save mr-2"></i> Create Administrator
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h6 class="text-base font-medium text-gray-900">Guidelines</h6>
                </div>
                <div class="p-6">
                    <h6 class="text-sm font-semibold text-gray-900 mb-3">Security Best Practices:</h6>
                    <ul class="space-y-2 mb-6">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span class="text-sm text-gray-700">Use strong passwords (min 8 characters)</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span class="text-sm text-gray-700">Enable password change on first login</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span class="text-sm text-gray-700">Assign minimum required permissions</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span class="text-sm text-gray-700">Use company email addresses</span>
                        </li>
                    </ul>

                    <hr class="border-gray-200 mb-6">

                    <h6 class="text-sm font-semibold text-gray-900 mb-3">Role Hierarchy:</h6>
                    <ol class="space-y-2">
                        <li class="text-sm text-gray-700"><strong>1. Super Admin</strong> - Full system access</li>
                        <li class="text-sm text-gray-700"><strong>2. Administrator</strong> - Management access</li>
                        <li class="text-sm text-gray-700"><strong>3. Moderator</strong> - Content moderation</li>
                        <li class="text-sm text-gray-700"><strong>4. Content Manager</strong> - Content only</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Role selection change handler
    $('#role').change(function() {
        const selectedRole = $(this).val();

        // Hide all role descriptions
        $('.role-desc').addClass('hidden');

        // Show selected role description
        if (selectedRole) {
            $('#' + selectedRole + '-desc').removeClass('hidden');
        }

        // Auto-select permissions based on role
        autoSelectPermissions(selectedRole);
    });

    // Image preview
    $('#profile_image').change(function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').html('<img src="' + e.target.result + '" class="w-full h-full object-cover rounded-full">');
            }
            reader.readAsDataURL(file);
        }
    });

    // Auto-select permissions based on role
    function autoSelectPermissions(role) {
        // Clear all permissions first
        $('input[name="permissions[]"]').prop('checked', false);

        switch(role) {
            case 'admin':
                $('#view_analytics, #manage_ads, #manage_subscriptions, #send_notifications, #view_reports, #export_data').prop('checked', true);
                break;
            case 'moderator':
                $('#view_analytics, #send_notifications, #view_reports').prop('checked', true);
                break;
            case 'content_manager':
                $('#view_analytics, #view_reports').prop('checked', true);
                break;
        }
    }

    // Form validation
    $('form').submit(function(e) {
        const password = $('#password').val();
        const confirmPassword = $('#password_confirmation').val();

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Password and confirm password do not match');
            return false;
        }

        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long');
            return false;
        }
    });
});
</script>
@endpush
