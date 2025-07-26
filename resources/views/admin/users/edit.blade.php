@extends('admin.layouts.app')

@section('title', 'Edit User - ' . $user->name)

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
            <p class="text-gray-600">Update user information and settings</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.users.show', $user) }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to User
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md">
            <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <!-- Basic Information -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>

                                <!-- Name -->
                                <div class="mb-4">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           id="name"
                                           name="name"
                                           value="{{ old('name', $user->name) }}"
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary @error('name') border-red-500 @enderror">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Email -->
                                <div class="mb-4">
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email"
                                           id="email"
                                           name="email"
                                           value="{{ old('email', $user->email) }}"
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary @error('email') border-red-500 @enderror">
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Phone -->
                                <div class="mb-4">
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="text"
                                           id="phone"
                                           name="phone"
                                           value="{{ old('phone', $user->phone) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary @error('phone') border-red-500 @enderror">
                                    @error('phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Account Settings -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Account Settings</h3>

                                <!-- Status Settings -->
                                <div class="space-y-4">
                                    <!-- Active Status -->
                                    <div class="flex items-center">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox"
                                               id="is_active"
                                               name="is_active"
                                               value="1"
                                               {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                                            Active Account
                                            <span class="text-xs text-gray-500 block">User can login and use the application</span>
                                        </label>
                                    </div>

                                    <!-- Banned Status -->
                                    <div class="flex items-center">
                                        <input type="hidden" name="is_banned" value="0">
                                        <input type="checkbox"
                                               id="is_banned"
                                               name="is_banned"
                                               value="1"
                                               {{ old('is_banned', $user->is_banned) ? 'checked' : '' }}
                                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                        <label for="is_banned" class="ml-2 block text-sm text-gray-700">
                                            Banned Account
                                            <span class="text-xs text-gray-500 block">User is permanently restricted</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Account Information -->
                                <div class="mt-6 p-4 bg-gray-50 rounded-md">
                                    <h4 class="text-sm font-medium text-gray-900 mb-3">Account Information</h4>
                                    <div class="space-y-2 text-sm text-gray-600">
                                        <div class="flex justify-between">
                                            <span>User ID:</span>
                                            <span class="font-mono">{{ $user->id }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Registration Date:</span>
                                            <span>{{ $user->created_at->format('M d, Y') }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Email Verified:</span>
                                            <span>
                                                @if($user->email_verified_at)
                                                    <i class="fas fa-check text-green-500"></i> Yes
                                                @else
                                                    <i class="fas fa-times text-red-500"></i> No
                                                @endif
                                            </span>
                                        </div>
                                        @if($user->last_login_at)
                                        <div class="flex justify-between">
                                            <span>Last Login:</span>
                                            <span>{{ $user->last_login_at->diffForHumans() }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                    <div class="flex space-x-3">
                        <a href="{{ route('admin.users.show', $user) }}"
                           class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Cancel
                        </a>
                    </div>
                    <div class="flex space-x-3">
                        <button type="button"
                                onclick="resetForm()"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Reset Form
                        </button>
                        <button type="submit"
                                class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Update User
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="bg-white rounded-lg shadow-md mt-6 border border-red-200">
            <div class="p-6">
                <h3 class="text-lg font-medium text-red-900 mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Danger Zone
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    These actions are irreversible. Please be certain before proceeding.
                </p>

                <div class="space-y-3">
                    <!-- Reset Password -->
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-md">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Reset Password</h4>
                            <p class="text-xs text-gray-500">Send a password reset email to the user</p>
                        </div>
                        <button onclick="resetPassword({{ $user->id }})"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm">
                            Reset Password
                        </button>
                    </div>

                    <!-- Delete User -->
                    <div class="flex items-center justify-between p-4 border border-red-200 rounded-md bg-red-50">
                        <div>
                            <h4 class="text-sm font-medium text-red-900">Delete User</h4>
                            <p class="text-xs text-red-600">Permanently remove this user and all associated data</p>
                        </div>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('Are you absolutely sure? This action cannot be undone.')"
                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                Delete User
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function resetForm() {
        if (confirm('Are you sure you want to reset the form? All unsaved changes will be lost.')) {
            document.querySelector('form').reset();
            // Restore original values
            @json($user->toArray())
            const originalData = @json($user->toArray());
            document.getElementById('name').value = originalData.name;
            document.getElementById('email').value = originalData.email;
            document.getElementById('phone').value = originalData.phone || '';
            document.getElementById('is_active').checked = originalData.is_active;
            document.getElementById('is_banned').checked = originalData.is_banned;
        }
    }

    async function resetPassword(userId) {
        if (confirm('Send password reset email to this user?')) {
            try {
                const response = await fetch(`/admin/api/users/${userId}/reset-password`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (response.ok) {
                    showToast('Password reset email sent successfully', 'success');
                } else {
                    showToast('Failed to send password reset email', 'error');
                }
            } catch (error) {
                showToast('Failed to send password reset email', 'error');
            }
        }
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-4 py-2 rounded-md shadow-lg z-50 text-white ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Handle checkbox mutual exclusivity
    document.getElementById('is_banned').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('is_active').checked = false;
        }
    });

    document.getElementById('is_active').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('is_banned').checked = false;
        }
    });
</script>
@endpush
