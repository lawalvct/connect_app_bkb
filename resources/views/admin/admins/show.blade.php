@extends('admin.layouts.app')

@section('title', 'Admin Details - ' . $admin->name)

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Administrator Details</h1>
            <p class="text-gray-600">View and manage administrator account information</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.admins.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Admins
            </a>
            @if(auth('admin')->user()->id !== $admin->id && (auth('admin')->user()->hasRole('super_admin') || (auth('admin')->user()->hasRole('admin') && $admin->role !== 'super_admin')))
            <a href="{{ route('admin.admins.edit', $admin) }}"
               class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-edit mr-2"></i>
                Edit Admin
            </a>
            @endif
        </div>
    </div>
@endsection

@section('content')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Administrator Information Card -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <div class="flex items-start space-x-6">
                        <!-- Profile Picture -->
                        <div class="flex-shrink-0">
                            @if($admin->profile_image)
                                <img class="h-20 w-20 rounded-full object-cover border-4 border-gray-200"
                                     src="{{ Storage::url($admin->profile_image) }}"
                                     alt="{{ $admin->name }}">
                            @else
                                <div class="h-20 w-20 rounded-full bg-primary flex items-center justify-center text-white text-2xl font-bold border-4 border-gray-200">
                                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <!-- Admin Info -->
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-bold text-gray-900">{{ $admin->name }}</h2>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        @if($admin->status === 'active')
                                            bg-green-100 text-green-800
                                        @elseif($admin->status === 'suspended')
                                            bg-yellow-100 text-yellow-800
                                        @else
                                            bg-red-100 text-red-800
                                        @endif">
                                        {{ ucfirst($admin->status) }}
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
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
                            </div>

                            <div class="mt-2 space-y-2">
                                <p class="text-gray-600">
                                    <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                    {{ $admin->email }}
                                </p>

                                @if($admin->phone)
                                <p class="text-gray-600">
                                    <i class="fas fa-phone mr-2 text-gray-400"></i>
                                    {{ $admin->phone }}
                                </p>
                                @endif

                                <p class="text-gray-600">
                                    <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                    Created {{ $admin->created_at->format('M d, Y') }}
                                </p>

                                @if($admin->last_login_at)
                                <p class="text-gray-600">
                                    <i class="fas fa-clock mr-2 text-gray-400"></i>
                                    Last login {{ $admin->last_login_at->diffForHumans() }}
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Security Information -->
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Account Security</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Last Login:</span>
                                <span class="text-gray-900">{{ $admin->last_login_at ? $admin->last_login_at->format('M d, Y g:i A') : 'Never' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Last OTP Sent:</span>
                                <span class="text-gray-900">{{ $admin->last_otp_sent_at ? $admin->last_otp_sent_at->format('M d, Y g:i A') : 'Never' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Failed Attempts:</span>
                                <span class="@if($admin->failed_login_attempts > 0) text-yellow-600 @else text-green-600 @endif">
                                    {{ $admin->failed_login_attempts }}
                                </span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Force Password Change:</span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if($admin->force_password_change)
                                        bg-yellow-100 text-yellow-800
                                    @else
                                        bg-green-100 text-green-800
                                    @endif">
                                    @if($admin->force_password_change) Yes @else No @endif
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Account Locked:</span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if($admin->isLocked())
                                        bg-red-100 text-red-800
                                    @else
                                        bg-green-100 text-green-800
                                    @endif">
                                    @if($admin->isLocked())
                                        Yes (until {{ $admin->locked_until->format('M d, Y g:i A') }})
                                    @else
                                        No
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Created:</span>
                                <span class="text-gray-900">{{ $admin->created_at->format('M d, Y g:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            @if($admin->permissions && count($admin->permissions) > 0)
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Additional Permissions ({{ count($admin->permissions) }})</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($admin->permissions as $permission)
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check text-green-500"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $permission)) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Additional Permissions</h3>
                </div>
                <div class="p-6">
                    <div class="text-center py-8">
                        <i class="fas fa-shield-alt text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No additional permissions assigned beyond role permissions.</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Actions Sidebar -->
        <div class="lg:col-span-1">
            <!-- Quick Actions -->
            @if(auth('admin')->user()->id !== $admin->id && (auth('admin')->user()->hasRole('super_admin') || (auth('admin')->user()->hasRole('admin') && $admin->role !== 'super_admin')))
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6 space-y-3" x-data="adminActions()">

                    @if($admin->status === 'active')
                    <button @click="updateStatus('inactive')"
                            class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-pause mr-2"></i>
                        Deactivate Admin
                    </button>
                    @elseif($admin->status === 'inactive')
                    <button @click="updateStatus('active')"
                            class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-play mr-2"></i>
                        Activate Admin
                    </button>
                    @endif

                    @if($admin->status !== 'suspended')
                    <button @click="updateStatus('suspended')"
                            class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-ban mr-2"></i>
                        Suspend Admin
                    </button>
                    @else
                    <button @click="updateStatus('active')"
                            class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-unlock mr-2"></i>
                        Unsuspend Admin
                    </button>
                    @endif

                    <button @click="resetPassword()"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-key mr-2"></i>
                        Reset Password
                    </button>

                    <a href="{{ route('admin.admins.edit', $admin) }}"
                       class="w-full bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md text-sm font-medium transition-colors inline-block text-center">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Admin
                    </a>

                    @if(auth('admin')->user()->hasRole('super_admin'))
                    <hr class="my-4">
                    <button @click="deleteAdmin()"
                            class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Admin
                    </button>
                    @endif
                </div>
            </div>
            @endif

            <!-- Account Statistics -->
            <div class="bg-white rounded-lg shadow-md mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Account Details</h3>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Admin ID:</span>
                        <span class="text-gray-900">{{ $admin->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created:</span>
                        <span class="text-gray-900">{{ $admin->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Role:</span>
                        <span class="text-gray-900">{{ $admin->getRoleDisplayName() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status:</span>
                        <span class="text-gray-900">
                            @if($admin->status === 'suspended')
                                <span class="text-yellow-600">Suspended</span>
                            @elseif($admin->status === 'inactive')
                                <span class="text-red-600">Inactive</span>
                            @else
                                <span class="text-green-600">Active</span>
                            @endif
                        </span>
                    </div>
                    @if($admin->last_login_at)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Last Login:</span>
                        <span class="text-gray-900">{{ $admin->last_login_at->diffForHumans() }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-500">Days Active:</span>
                        <span class="text-gray-900">{{ $admin->created_at->diffInDays() }}</span>
                    </div>
                    @if($admin->isLocked())
                    <div class="flex justify-between">
                        <span class="text-gray-500">Account Locked:</span>
                        <span class="text-red-600">Until {{ $admin->locked_until->format('M d, Y g:i A') }}</span>
                    </div>
                    @endif
                    @if($admin->force_password_change)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Password Change:</span>
                        <span class="text-yellow-600">Required</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function adminActions() {
        return {
            async updateStatus(status) {
                const action = status === 'active' ? 'activate' :
                              status === 'inactive' ? 'deactivate' : 'suspend';

                if (confirm(`Are you sure you want to ${action} this admin?`)) {
                    try {
                        const response = await fetch('{{ route("admin.admins.update-status", $admin) }}', {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ status: status })
                        });

                        const data = await response.json();
                        if (response.ok) {
                            showToast(data.message || 'Status updated successfully', 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showToast(data.message || 'Error updating status', 'error');
                        }
                    } catch (error) {
                        showToast('Error updating status', 'error');
                    }
                }
            },

            async resetPassword() {
                if (confirm('Are you sure you want to reset this admin\'s password?')) {
                    try {
                        const response = await fetch('{{ route("admin.admins.reset-password", $admin) }}', {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Content-Type': 'application/json'
                            }
                        });

                        const data = await response.json();
                        if (response.ok) {
                            showToast('Password reset successfully. New password: ' + data.new_password, 'success');
                        } else {
                            showToast(data.message || 'Error resetting password', 'error');
                        }
                    } catch (error) {
                        showToast('Error resetting password', 'error');
                    }
                }
            },

            async deleteAdmin() {
                if (confirm('Are you sure you want to delete this admin? This action cannot be undone.')) {
                    try {
                        const response = await fetch('{{ route("admin.admins.destroy", $admin) }}', {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Content-Type': 'application/json'
                            }
                        });

                        const data = await response.json();
                        if (response.ok) {
                            showToast('Admin deleted successfully', 'success');
                            setTimeout(() => window.location.href = '{{ route("admin.admins.index") }}', 1500);
                        } else {
                            showToast(data.message || 'Error deleting admin', 'error');
                        }
                    } catch (error) {
                        showToast('Error deleting admin', 'error');
                    }
                }
            }
        }
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-4 py-2 rounded-md shadow-lg z-50 text-white max-w-sm ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
</script>
@endpush
