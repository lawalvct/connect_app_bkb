@extends('admin.layouts.app')

@section('title', 'Edit Admin - ' . $admin->name)

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Administrator</h1>
            <p class="text-gray-600">Update administrator account information</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.admins.show', $admin) }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-eye mr-2"></i>
                View Details
            </a>
            <a href="{{ route('admin.admins.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Admins
            </a>
        </div>
    </div>
@endsection

@section('content')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="adminEdit()">
        <div class="lg:col-span-2">
            <form action="{{ route('admin.admins.update', $admin) }}" method="POST" enctype="multipart/form-data" @submit="validateForm">
                @csrf
                @method('PUT')

                <!-- Personal Information -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Personal Information</h3>
                    </div>
                    <div class="p-6">
                        <!-- Current Profile Image -->
                        <div class="mb-6">
                            <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-2">Profile Image</label>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div id="currentImage" class="h-20 w-20 rounded-full overflow-hidden border-4 border-gray-200">
                                        @if($admin->profile_image)
                                            <img src="{{ Storage::url($admin->profile_image) }}"
                                                 class="w-full h-full object-cover" alt="{{ $admin->name }}">
                                        @else
                                            <div class="w-full h-full bg-primary flex items-center justify-center text-white text-2xl font-bold">
                                                {{ strtoupper(substr($admin->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <input type="file"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-primary file:text-white hover:file:bg-primary-dark @error('profile_image') border-red-300 @enderror"
                                           id="profile_image" name="profile_image" accept="image/*" @change="previewImage">
                                    <p class="text-sm text-gray-500 mt-1">Leave empty to keep current image</p>
                                    @error('profile_image')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary @error('name') border-red-300 @enderror"
                                       id="name" name="name" value="{{ old('name', $admin->name) }}" required>
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
                                       id="email" name="email" value="{{ old('email', $admin->email) }}" required>
                                @error('email')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary @error('phone') border-red-300 @enderror"
                                       id="phone" name="phone" value="{{ old('phone', $admin->phone) }}">
                                @error('phone')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status (only if not editing own profile) -->
                            @if(auth('admin')->user()->id !== $admin->id)
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary @error('status') border-red-300 @enderror"
                                        id="status" name="status" required>
                                    <option value="active" {{ old('status', $admin->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $admin->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="suspended" {{ old('status', $admin->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                                @error('status')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Password Change (Optional) -->
                <div class="bg-white rounded-lg shadow-md mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Password Change (Optional)</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                <input type="password"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary @error('password') border-red-300 @enderror"
                                       id="password" name="password" x-model="password">
                                <p class="text-sm text-gray-500 mt-1">Leave empty to keep current password</p>
                                @error('password')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                <input type="password"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                                       :class="{ 'border-red-300': passwordMismatch }"
                                       id="password_confirmation" name="password_confirmation" x-model="passwordConfirmation">
                                <p x-show="passwordMismatch" class="text-red-500 text-sm mt-1">Passwords do not match</p>
                            </div>

                            @if(auth('admin')->user()->id !== $admin->id)
                            <div class="md:col-span-2">
                                <div class="flex items-center">
                                    <input class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                           type="checkbox" id="force_password_change"
                                           name="force_password_change" value="1"
                                           {{ old('force_password_change', $admin->force_password_change) ? 'checked' : '' }}>
                                    <label class="ml-2 block text-sm text-gray-900" for="force_password_change">
                                        Force password change on next login
                                    </label>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Role & Permissions (only if not editing own profile) -->
                @if(auth('admin')->user()->id !== $admin->id)
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
                                    id="role" name="role" required>
                                <option value="admin" {{ old('role', $admin->role) === 'admin' ? 'selected' : '' }}>Administrator</option>
                                <option value="moderator" {{ old('role', $admin->role) === 'moderator' ? 'selected' : '' }}>Moderator</option>
                                <option value="content_manager" {{ old('role', $admin->role) === 'content_manager' ? 'selected' : '' }}>Content Manager</option>
                            </select>
                            @error('role')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Additional Permissions -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-4">Additional Permissions</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @php
                                    $allPermissions = [
                                        'view_analytics' => 'View Analytics',
                                        'manage_ads' => 'Manage Advertisements',
                                        'manage_subscriptions' => 'Manage Subscriptions',
                                        'send_notifications' => 'Send Notifications',
                                        'view_reports' => 'View Reports',
                                        'export_data' => 'Export Data'
                                    ];
                                    $currentPermissions = old('permissions', $admin->permissions ?? []);
                                @endphp

                                @foreach($allPermissions as $key => $label)
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <input class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                           type="checkbox" id="{{ $key }}"
                                           name="permissions[]" value="{{ $key }}"
                                           {{ in_array($key, $currentPermissions) ? 'checked' : '' }}>
                                    <label class="flex-1 text-sm text-gray-900" for="{{ $key }}">
                                        {{ $label }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Submit Buttons -->
                <div class="bg-white rounded-lg shadow-md mt-6">
                    <div class="p-6">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('admin.admins.show', $admin) }}"
                               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Update Administrator
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
                    <h3 class="text-lg font-medium text-gray-900">Current Information</h3>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Current Role:</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            @if($admin->role === 'super_admin')
                                bg-purple-100 text-purple-800
                            @elseif($admin->role === 'admin')
                                bg-blue-100 text-blue-800
                            @elseif($admin->role === 'moderator')
                                bg-green-100 text-green-800
                            @else
                                bg-yellow-100 text-yellow-800
                            @endif">
                            {{ $admin->getRoleDisplayName() }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Current Status:</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            @if($admin->status === 'active')
                                bg-green-100 text-green-800
                            @elseif($admin->status === 'suspended')
                                bg-yellow-100 text-yellow-800
                            @else
                                bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($admin->status) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Last Login:</span>
                        <span class="text-gray-900">{{ $admin->last_login_at ? $admin->last_login_at->format('M d, Y') : 'Never' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created:</span>
                        <span class="text-gray-900">{{ $admin->created_at->format('M d, Y') }}</span>
                    </div>
                    @if($admin->permissions && count($admin->permissions) > 0)
                    <div class="pt-3 border-t border-gray-200">
                        <span class="text-gray-500 text-xs uppercase tracking-wide">Current Permissions</span>
                        <div class="mt-2 space-y-1">
                            @foreach($admin->permissions as $permission)
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-check text-green-500 text-xs"></i>
                                <span class="text-xs text-gray-700">{{ ucwords(str_replace('_', ' ', $permission)) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            @if(auth('admin')->user()->id === $admin->id)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Note</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>You are editing your own profile. Role and permission changes are not allowed.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function adminEdit() {
        return {
            password: '',
            passwordConfirmation: '',

            get passwordMismatch() {
                return this.password && this.passwordConfirmation && this.password !== this.passwordConfirmation;
            },

            previewImage(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        document.getElementById('currentImage').innerHTML =
                            `<img src="${e.target.result}" class="w-full h-full object-cover" alt="Preview">`;
                    };
                    reader.readAsDataURL(file);
                }
            },

            validateForm(event) {
                // Password validation
                if (this.password) {
                    if (this.password.length < 8) {
                        event.preventDefault();
                        this.showToast('Password must be at least 8 characters long', 'error');
                        return false;
                    }

                    if (this.password !== this.passwordConfirmation) {
                        event.preventDefault();
                        this.showToast('Password and confirm password do not match', 'error');
                        return false;
                    }
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
            }
        }
    }
</script>
@endpush
