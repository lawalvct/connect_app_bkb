@extends('admin.layouts.app')

@section('title', 'Profile Settings')
@section('page-title', 'Profile Settings')

@push('styles')
<style>
    /* Enhanced Form Input Styling */
    .form-input-enhanced {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 2px solid #9ca3af;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }

    .form-input-enhanced:hover {
        border-color: #6b7280;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }

    .form-input-enhanced:focus {
        border-color: #A20030;
        box-shadow: 0 0 0 3px rgba(162, 0, 48, 0.1), 0 4px 8px rgba(0, 0, 0, 0.1);
        background: #ffffff;
    }

    .form-label-enhanced {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
    }

    .form-label-enhanced .fas {
        color: #A20030;
        margin-right: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-background">
    <div class="max-w-4xl mx-auto space-y-8">

        <!-- Profile Header -->
        <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-100">
            <div class="flex items-center space-x-6">
                <div class="relative">
                    @if($admin->profile_image)
                        <img src="{{ Storage::url($admin->profile_image) }}"
                             alt="Profile Image"
                             class="w-24 h-24 rounded-full object-cover border-4 border-primary-light">
                    @else
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-primary to-red-600 flex items-center justify-center border-4 border-primary-light">
                            <span class="text-3xl font-bold text-white">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                        </div>
                    @endif
                    <div class="absolute -bottom-2 -right-2 bg-green-500 w-8 h-8 rounded-full border-4 border-white flex items-center justify-center">
                        <i class="fas fa-check text-white text-xs"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-900">{{ $admin->name }}</h1>
                    <p class="text-lg text-gray-600">{{ $admin->getRoleDisplayName() }}</p>
                    <p class="text-sm text-gray-500 flex items-center mt-2">
                        <i class="fas fa-envelope mr-2"></i>
                        {{ $admin->email }}
                    </p>
                    @if($admin->phone)
                        <p class="text-sm text-gray-500 flex items-center mt-1">
                            <i class="fas fa-phone mr-2"></i>
                            {{ $admin->phone }}
                        </p>
                    @endif
                    <div class="flex items-center mt-3 space-x-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-circle text-green-400 text-xs mr-1"></i>
                            {{ ucfirst($admin->status) }}
                        </span>
                        @if($admin->last_login_at)
                            <span class="text-xs text-gray-500">
                                Last login: {{ $admin->last_login_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white shadow-lg rounded-2xl border border-gray-100" x-data="{ activeTab: 'profile' }">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-8 pt-6">
                    <button @click="activeTab = 'profile'"
                            :class="activeTab === 'profile' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        <i class="fas fa-user mr-2"></i>
                        Personal Information
                    </button>
                    <button @click="activeTab = 'security'"
                            :class="activeTab === 'security' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        <i class="fas fa-shield-alt mr-2"></i>
                        Security
                    </button>
                    <button @click="activeTab = 'notifications'"
                            :class="activeTab === 'notifications' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        <i class="fas fa-bell mr-2"></i>
                        Notifications
                    </button>
                    {{-- <button @click="activeTab = 'activity'"
                            :class="activeTab === 'activity' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        <i class="fas fa-history mr-2"></i>
                        Activity Log
                    </button> --}}
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-8">

                <!-- Personal Information Tab -->
                <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
                    <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Profile Image -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Profile Image</label>
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    @if($admin->profile_image)
                                        <img src="{{ Storage::url($admin->profile_image) }}"
                                             alt="Profile Image"
                                             class="w-16 h-16 rounded-full object-cover border-2 border-gray-300">
                                    @else
                                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-red-600 flex items-center justify-center border-2 border-gray-300">
                                            <span class="text-xl font-bold text-white">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="profile_image" id="profile_image" accept="image/*"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-light file:text-primary hover:file:bg-primary hover:file:text-white file:transition-colors file:duration-200">
                                    <p class="text-xs text-gray-500 mt-2">Recommended: Square image, at least 200x200px. Max size: 2MB</p>
                                </div>
                                @if($admin->profile_image)
                                    <button type="button" onclick="deleteProfileImage()"
                                            class="inline-flex items-center px-3 py-2 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors duration-200">
                                        <i class="fas fa-trash mr-1"></i>
                                        Remove
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="form-label-enhanced">
                                    <i class="fas fa-user"></i>
                                    Full Name
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $admin->name) }}" required
                                       class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                       placeholder="Enter your full name">
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="form-label-enhanced">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                </label>
                                <input type="email" name="email" id="email" value="{{ old('email', $admin->email) }}" required
                                       class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                       placeholder="Enter your email address">
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="form-label-enhanced">
                                    <i class="fas fa-phone"></i>
                                    Phone Number
                                </label>
                                <input type="tel" name="phone" id="phone" value="{{ old('phone', $admin->phone) }}"
                                       class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                       placeholder="Enter your phone number">
                                @error('phone')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Role (Read-only) -->
                            <div>
                                <label class="form-label-enhanced">
                                    <i class="fas fa-shield-alt"></i>
                                    Role
                                </label>
                                <input type="text" value="{{ $admin->getRoleDisplayName() }}" readonly
                                       class="block w-full px-4 py-3 text-gray-700 bg-gray-100 border-2 border-gray-300 rounded-lg shadow-sm cursor-not-allowed">
                                <p class="text-xs text-gray-600 mt-2 flex items-center">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Contact a Super Admin to change your role
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-primary hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                                <i class="fas fa-save mr-2"></i>
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Tab -->
                <div x-show="activeTab === 'security'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="space-y-8">

                        <!-- Change Password -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                            <form action="{{ route('admin.profile.password') }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')

                                <div>
                                    <label for="current_password" class="form-label-enhanced">
                                        <i class="fas fa-lock"></i>
                                        Current Password
                                    </label>
                                    <input type="password" name="current_password" id="current_password" required
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="Enter your current password">
                                    @error('current_password')
                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="password" class="form-label-enhanced">
                                            <i class="fas fa-key"></i>
                                            New Password
                                        </label>
                                        <input type="password" name="password" id="password" required
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="Enter your new password">
                                        @error('password')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="password_confirmation" class="form-label-enhanced">
                                            <i class="fas fa-check-circle"></i>
                                            Confirm New Password
                                        </label>
                                        <input type="password" name="password_confirmation" id="password_confirmation" required
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="Confirm your new password">
                                    </div>
                                </div>

                                <div class="bg-blue-50 rounded-lg p-4">
                                    <div class="flex">
                                        <i class="fas fa-info-circle text-blue-400 mt-1 mr-3"></i>
                                        <div>
                                            <h4 class="text-sm font-medium text-blue-800">Password Requirements</h4>
                                            <ul class="text-sm text-blue-700 mt-2 space-y-1">
                                                <li>• At least 8 characters long</li>
                                                <li>• Contains uppercase and lowercase letters</li>
                                                <li>• Contains at least one number</li>
                                                <li>• Contains at least one special character</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-primary hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                                        <i class="fas fa-key mr-2"></i>
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Two-Factor Authentication -->
                        {{-- <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Two-Factor Authentication</h3>
                                    <p class="text-sm text-gray-600">Add an extra layer of security to your account</p>
                                </div>
                                <div class="flex items-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Enabled (OTP)
                                    </span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">
                                Your account is protected with OTP verification. You'll need to verify your identity with a one-time password sent to your email when logging in from a new device or after 24 hours.
                            </p>
                        </div> --}}

                        <!-- Account Information -->
                        {{-- <div class="bg-gray-50 rounded-xl p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Created</label>
                                    <p class="text-sm text-gray-900">{{ $admin->created_at->format('M d, Y \a\t g:i A') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Login</label>
                                    <p class="text-sm text-gray-900">
                                        {{ $admin->last_login_at ? $admin->last_login_at->format('M d, Y \a\t g:i A') : 'Never' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Failed Login Attempts</label>
                                    <p class="text-sm text-gray-900">{{ $admin->failed_login_attempts }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Status</label>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                        @if($admin->status === 'active') bg-green-100 text-green-800
                                        @elseif($admin->status === 'inactive') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($admin->status) }}
                                    </span>
                                </div>
                            </div>
                        </div> --}}

                    </div>
                </div>

                <!-- Notifications Tab -->
                <div x-show="activeTab === 'notifications'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
                    <form action="{{ route('admin.profile.notifications') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Preferences</h3>
                                <p class="text-sm text-gray-600 mb-6">Choose how you want to be notified about important events.</p>
                            </div>

                            <div class="space-y-4">
                                <!-- Email Notifications -->
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope text-blue-500 w-6 mr-3"></i>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Email Notifications</h4>
                                            <p class="text-sm text-gray-600">Receive notifications via email</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="email_notifications" value="1" class="sr-only peer"
                                               {{ (isset($admin->permissions['email_notifications']) && $admin->permissions['email_notifications']) ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>

                                <!-- Push Notifications -->
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-bell text-green-500 w-6 mr-3"></i>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Push Notifications</h4>
                                            <p class="text-sm text-gray-600">Receive browser push notifications</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="push_notifications" value="1" class="sr-only peer"
                                               {{ (isset($admin->permissions['push_notifications']) && $admin->permissions['push_notifications']) ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>

                                <!-- Login Alerts -->
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-shield-alt text-red-500 w-6 mr-3"></i>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Login Alerts</h4>
                                            <p class="text-sm text-gray-600">Get notified of new login attempts</p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="login_alerts" value="1" class="sr-only peer"
                                               {{ (isset($admin->permissions['login_alerts']) && $admin->permissions['login_alerts']) ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-primary hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Preferences
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Activity Log Tab -->
                {{-- <div x-show="activeTab === 'activity'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Recent Activity</h3>
                            <p class="text-sm text-gray-600">Track your recent account activity and security events.</p>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-6" x-data="{ activities: [] }" x-init="
                            fetch('{{ route('admin.profile.activity') }}')
                                .then(response => response.json())
                                .then(data => activities = data)
                                .catch(error => console.error('Error:', error))
                        ">
                            <div x-show="activities.length === 0" class="text-center py-8">
                                <i class="fas fa-history text-4xl text-gray-400 mb-3"></i>
                                <p class="text-sm text-gray-500">Loading activity history...</p>
                            </div>

                            <div x-show="activities.length > 0" class="space-y-4">
                                <template x-for="activity in activities" :key="activity.id">
                                    <div class="flex items-center space-x-4 p-4 bg-white rounded-lg border border-gray-200">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-sign-in-alt text-blue-600"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-medium text-gray-900" x-text="activity.action"></h4>
                                            <p class="text-sm text-gray-600" x-text="'IP: ' + activity.ip"></p>
                                            <p class="text-xs text-gray-500" x-text="new Date(activity.created_at).toLocaleString()"></p>
                                        </div>
                                        <div class="text-green-500">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div> --}}

            </div>
        </div>

    </div>
</div>

<script>
function deleteProfileImage() {
    if (confirm('Are you sure you want to delete your profile image?')) {
        fetch('{{ route('admin.profile.delete-image') }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the image.');
        });
    }
}
</script>
@endsection
