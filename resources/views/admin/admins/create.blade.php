@extends('admin.layouts.app')

@section('title', 'Create New Admin')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create New Administrator</h1>
            <p class="text-gray-600">Add a new administrator account with specific roles and permissions</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.admins.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Admins
            </a>
        </div>
    </div>
@endsection

@section('content')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="adminCreate()">
        <div class="lg:col-span-2">
            <form action="{{ route('admin.admins.store') }}" method="POST" enctype="multipart/form-data" @submit="validateForm">
                @csrf

                <!-- Personal Information -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Personal Information</h3>
                    </div>
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
                                        <input type="file"
                                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-primary file:text-white hover:file:bg-primary-dark @error('profile_image') border-red-300 @enderror"
                                               id="profile_image" name="profile_image" accept="image/*" @change="previewImage">
                                        <p class="text-sm text-gray-500 mt-1">Maximum file size: 2MB. Supported formats: JPEG, PNG, JPG, GIF</p>
                                        @error('profile_image')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary @error('name') border-red-300 @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input type="email"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary @error('email') border-red-300 @enderror"
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary @error('phone') border-red-300 @enderror"
                                       id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary @error('status') border-red-300 @enderror"
                                        id="status" name="status" required>
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
                <div class="bg-white rounded-lg shadow-md mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Account Security</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Password -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary @error('password') border-red-300 @enderror"
                                       id="password" name="password" x-model="password" required>
                                <p class="text-sm text-gray-500 mt-1">Minimum 8 characters</p>
                                @error('password')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                                       :class="{ 'border-red-300': passwordMismatch }"
                                       id="password_confirmation" name="password_confirmation" x-model="passwordConfirmation" required>
                                <p x-show="passwordMismatch" class="text-red-500 text-sm mt-1">Passwords do not match</p>
                            </div>

                            <!-- Force Password Change -->
                            <div class="md:col-span-2">
                                <div class="flex items-center">
                                    <input type="checkbox"
                                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                           id="force_password_change" name="force_password_change" value="1"
                                           {{ old('force_password_change') ? 'checked' : '' }}>
                                    <label class="ml-2 block text-sm text-gray-900" for="force_password_change">
                                        Force password change on first login
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role & Permissions -->
                <div class="bg-white rounded-lg shadow-md mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Role & Permissions</h3>
                    </div>
                    <div class="p-6">
                        <!-- Role Selection -->
                        <div class="mb-6">
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                Role <span class="text-red-500">*</span>
                            </label>
                            <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary @error('role') border-red-300 @enderror"
                                    id="role" name="role" x-model="selectedRole" @change="updateRoleDescription" required>
                                <option value="">Select Role</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                                <option value="content_manager" {{ old('role') === 'content_manager' ? 'selected' : '' }}>Content Manager</option>
                                <option value="blog_manager" {{ old('role') === 'blog_manager' ? 'selected' : '' }}>Blog Manager</option>
                                <option value="moderator" {{ old('role') === 'moderator' ? 'selected' : '' }}>Moderator</option>
                                <option value="analytics_manager" {{ old('role') === 'analytics_manager' ? 'selected' : '' }}>Analytics Manager</option>
                                <option value="subscription_manager" {{ old('role') === 'subscription_manager' ? 'selected' : '' }}>Subscription Manager</option>
                            </select>
                            @error('role')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Role Descriptions -->
                        <div class="mb-6">
                            <div x-show="selectedRole === 'admin'" x-transition class="bg-blue-50 border-l-4 border-blue-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-crown text-blue-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            <strong>Administrator:</strong> Full access to users, content, ads, subscriptions, streams, notifications, and analytics. Cannot manage other administrators or system settings.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="selectedRole === 'content_manager'" x-transition class="bg-green-50 border-l-4 border-green-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-newspaper text-green-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-green-700">
                                            <strong>Content Manager:</strong> Access to user management, posts, stories, live streams moderation, and content-related notifications. Limited analytics access.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="selectedRole === 'blog_manager'" x-transition class="bg-teal-50 border-l-4 border-teal-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-blog text-teal-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-teal-700">
                                            <strong>Blog Manager:</strong> Full access to blog management including creating, editing, publishing blogs and managing SEO settings.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="selectedRole === 'moderator'" x-transition class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-shield-alt text-yellow-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            <strong>Moderator:</strong> Access to content moderation, user management (limited), live streams, and basic analytics. Cannot access system settings.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="selectedRole === 'analytics_manager'" x-transition class="bg-purple-50 border-l-4 border-purple-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-chart-line text-purple-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-purple-700">
                                            <strong>Analytics Manager:</strong> Full access to all analytics sections (users, content, revenue, advertising, streaming), view reports, and export data capabilities.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="selectedRole === 'subscription_manager'" x-transition class="bg-indigo-50 border-l-4 border-indigo-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-credit-card text-indigo-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-indigo-700">
                                            <strong>Subscription Manager:</strong> Access to user subscriptions, subscription plans, revenue analytics, and payment-related advertisements. Limited user management.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Permissions -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-4">Additional Permissions</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @php
                                    $allPermissions = [
                                        // User Management
                                        'manage_users' => 'Manage Users',
                                        'verify_users' => 'Verify User Identity',

                                        // Content Management
                                        'manage_posts' => 'Manage Posts',
                                        'manage_stories' => 'Manage Stories',
                                        'manage_streams' => 'Manage Live Streams',
                                        'manage_blogs' => 'Manage Blogs',

                                        // Business Operations
                                        'manage_ads' => 'Manage Advertisements',
                                        'manage_subscriptions' => 'Manage Subscriptions',
                                        'manage_subscription_plans' => 'Manage Subscription Plans',

                                        // Communications
                                        'send_push_notifications' => 'Send Push Notifications',
                                        'manage_email_templates' => 'Manage Email Templates',
                                        'view_notification_logs' => 'View Notification Logs',

                                        // Analytics & Reports
                                        'view_user_analytics' => 'View User Analytics',
                                        'view_content_analytics' => 'View Content Analytics',
                                        'view_revenue_analytics' => 'View Revenue Analytics',
                                        'view_advertising_analytics' => 'View Advertising Analytics',
                                        'view_streaming_analytics' => 'View Streaming Analytics',
                                        'export_data' => 'Export Data',

                                        // System
                                        'view_system_settings' => 'View System Settings'
                                    ];
                                @endphp

                                @foreach($allPermissions as $key => $label)
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <input class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                           type="checkbox" id="{{ $key }}"
                                           name="permissions[]" value="{{ $key }}"
                                           {{ in_array($key, old('permissions', [])) ? 'checked' : '' }}>
                                    <label class="flex-1 text-sm text-gray-900" for="{{ $key }}">
                                        {{ $label }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="bg-white rounded-lg shadow-md mt-6">
                    <div class="p-6">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('admin.admins.index') }}"
                               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Create Administrator
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Guidelines</h3>
                </div>
                <div class="p-6">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Security Best Practices:</h4>
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

                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Role Hierarchy:</h4>
                    <ol class="space-y-2">
                        <li class="text-sm text-gray-700"><strong>1. Super Admin</strong> - Complete system access</li>
                        <li class="text-sm text-gray-700"><strong>2. Administrator</strong> - Full management access</li>
                        <li class="text-sm text-gray-700"><strong>3. Content Manager</strong> - Content & user focus</li>
                        <li class="text-sm text-gray-700"><strong>4. Blog Manager</strong> - Blog management</li>
                        <li class="text-sm text-gray-700"><strong>5. Moderator</strong> - Content moderation</li>
                        <li class="text-sm text-gray-700"><strong>6. Analytics Manager</strong> - Data & reporting</li>
                        <li class="text-sm text-gray-700"><strong>7. Subscription Manager</strong> - Business operations</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function adminCreate() {
        return {
            password: '',
            passwordConfirmation: '',
            selectedRole: '{{ old("role") }}',

            get passwordMismatch() {
                return this.password && this.passwordConfirmation && this.password !== this.passwordConfirmation;
            },

            previewImage(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        document.getElementById('imagePreview').innerHTML =
                            `<img src="${e.target.result}" class="w-full h-full object-cover rounded-full" alt="Preview">`;
                    };
                    reader.readAsDataURL(file);
                }
            },

            updateRoleDescription() {
                // Auto-select permissions based on role
                this.autoSelectPermissions(this.selectedRole);
            },

            autoSelectPermissions(role) {
                // Clear all permissions first
                document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
                    checkbox.checked = false;
                });

                // Auto-select permissions based on role
                switch(role) {
                    case 'admin':
                        // Administrator gets most permissions except system settings
                        [
                            'manage_users', 'verify_users',
                            'manage_posts', 'manage_stories', 'manage_streams',
                            'manage_ads', 'manage_subscriptions', 'manage_subscription_plans',
                            'send_push_notifications', 'manage_email_templates', 'view_notification_logs',
                            'view_user_analytics', 'view_content_analytics', 'view_revenue_analytics',
                            'view_advertising_analytics', 'view_streaming_analytics', 'export_data'
                        ].forEach(permission => {
                            const checkbox = document.getElementById(permission);
                            if (checkbox) checkbox.checked = true;
                        });
                        break;

                    case 'content_manager':
                        // Content manager focuses on content and user management
                        [
                            'manage_users', 'verify_users',
                            'manage_posts', 'manage_stories', 'manage_streams',
                            'send_push_notifications', 'view_notification_logs',
                            'view_user_analytics', 'view_content_analytics', 'view_streaming_analytics'
                        ].forEach(permission => {
                            const checkbox = document.getElementById(permission);
                            if (checkbox) checkbox.checked = true;
                        });
                        break;

                    case 'blog_manager':
                        // Blog manager focuses on blog management
                        [
                            'manage_blogs',
                            'view_content_analytics'
                        ].forEach(permission => {
                            const checkbox = document.getElementById(permission);
                            if (checkbox) checkbox.checked = true;
                        });
                        break;

                    case 'moderator':
                        // Moderator gets content moderation and limited access
                        [
                            'manage_posts', 'manage_stories', 'manage_streams',
                            'send_push_notifications',
                            'view_content_analytics', 'view_streaming_analytics'
                        ].forEach(permission => {
                            const checkbox = document.getElementById(permission);
                            if (checkbox) checkbox.checked = true;
                        });
                        break;

                    case 'analytics_manager':
                        // Analytics manager gets all analytics and reporting
                        [
                            'view_user_analytics', 'view_content_analytics', 'view_revenue_analytics',
                            'view_advertising_analytics', 'view_streaming_analytics', 'export_data',
                            'view_notification_logs'
                        ].forEach(permission => {
                            const checkbox = document.getElementById(permission);
                            if (checkbox) checkbox.checked = true;
                        });
                        break;

                    case 'subscription_manager':
                        // Subscription manager focuses on business operations
                        [
                            'manage_subscriptions', 'manage_subscription_plans', 'manage_ads',
                            'view_revenue_analytics', 'view_advertising_analytics',
                            'send_push_notifications', 'manage_email_templates',
                            'manage_users', 'export_data'
                        ].forEach(permission => {
                            const checkbox = document.getElementById(permission);
                            if (checkbox) checkbox.checked = true;
                        });
                        break;
                }
            },

            validateForm(event) {
                // Password validation
                if (this.password !== this.passwordConfirmation) {
                    event.preventDefault();
                    this.showToast('Password and confirm password do not match', 'error');
                    return false;
                }

                if (this.password.length < 8) {
                    event.preventDefault();
                    this.showToast('Password must be at least 8 characters long', 'error');
                    return false;
                }

                return true;
            },

            showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 px-4 py-2 rounded-md shadow-lg z-50 text-white max-w-sm ${
                    type === 'success' ? 'bg-green-500' : 'bg-red-500'
                }`;
                toast.textContent = message;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
            },

            init() {
                // Initialize role descriptions and permissions if old role exists
                if (this.selectedRole) {
                    this.autoSelectPermissions(this.selectedRole);
                }
            }
        }
    }
</script>
@endpush
