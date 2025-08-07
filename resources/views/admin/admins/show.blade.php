@extends('admin.layouts.app')

@section('page-title', 'Admin Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Administrator Details</h1>
            <p class="mb-0 text-gray-600">View administrator account information</p>
        </div>
        <div class="d-flex gap-2">
            @if(auth('admin')->user()->id !== $admin->id && (auth('admin')->user()->hasRole('super_admin') || (auth('admin')->user()->hasRole('admin') && $admin->role !== 'super_admin')))
            <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Admin
            </a>
            @endif
            <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Admins
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Profile Information -->
        <div class="col-lg-8">
            <!-- Personal Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            @if($admin->profile_image)
                                <img src="{{ Storage::url($admin->profile_image) }}" alt="Profile"
                                     class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                            @else
                                <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center text-white"
                                     style="width: 120px; height: 120px; font-size: 3rem; font-weight: bold;">
                                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="col-md-9">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-semibold text-muted" width="120">Name:</td>
                                    <td>{{ $admin->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Email:</td>
                                    <td>{{ $admin->email }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Phone:</td>
                                    <td>{{ $admin->phone ?: 'Not provided' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Role:</td>
                                    <td>
                                        <span class="badge role-badge role-{{ str_replace(' ', '_', strtolower($admin->getRoleDisplayName())) }}">
                                            {{ $admin->getRoleDisplayName() }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Status:</td>
                                    <td>
                                        <span class="badge status-badge status-{{ $admin->status }}">
                                            {{ ucfirst($admin->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Security -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Security</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-semibold text-muted">Last Login:</td>
                                    <td>{{ $admin->last_login_at ? $admin->last_login_at->format('M d, Y \a\t g:i A') : 'Never logged in' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Last OTP Sent:</td>
                                    <td>{{ $admin->last_otp_sent_at ? $admin->last_otp_sent_at->format('M d, Y \a\t g:i A') : 'Never' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Failed Attempts:</td>
                                    <td>
                                        @if($admin->failed_login_attempts > 0)
                                            <span class="text-warning">{{ $admin->failed_login_attempts }}</span>
                                        @else
                                            <span class="text-success">0</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-semibold text-muted">Force Password Change:</td>
                                    <td>
                                        @if($admin->force_password_change)
                                            <span class="badge bg-warning">Yes</span>
                                        @else
                                            <span class="badge bg-success">No</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Account Locked:</td>
                                    <td>
                                        @if($admin->isLocked())
                                            <span class="badge bg-danger">Yes (until {{ $admin->locked_until->format('M d, Y g:i A') }})</span>
                                        @else
                                            <span class="badge bg-success">No</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-muted">Created:</td>
                                    <td>{{ $admin->created_at->format('M d, Y \a\t g:i A') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            @if($admin->permissions && count($admin->permissions) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Additional Permissions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($admin->permissions as $permission)
                        <div class="col-md-6 mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            {{ ucwords(str_replace('_', ' ', $permission)) }}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            @if(auth('admin')->user()->id !== $admin->id && (auth('admin')->user()->hasRole('super_admin') || (auth('admin')->user()->hasRole('admin') && $admin->role !== 'super_admin')))
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <!-- Status Actions -->
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Change Status
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item status-change" href="#" data-status="active">Activate</a></li>
                            <li><a class="dropdown-item status-change" href="#" data-status="inactive">Deactivate</a></li>
                            <li><a class="dropdown-item status-change" href="#" data-status="suspended">Suspend</a></li>
                        </ul>
                    </div>

                    <!-- Reset Password -->
                    <button class="btn btn-outline-warning reset-password">
                        <i class="fas fa-key"></i> Reset Password
                    </button>

                    <!-- Edit Admin -->
                    <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit"></i> Edit Admin
                    </a>

                    <!-- Delete Admin (Super Admin Only) -->
                    @if(auth('admin')->user()->hasRole('super_admin'))
                    <button class="btn btn-outline-danger delete-admin" data-id="{{ $admin->id }}">
                        <i class="fas fa-trash"></i> Delete Admin
                    </button>
                    @endif
                </div>
            </div>
            @endif

            <!-- Activity Summary -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Activity Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="mb-1">{{ $admin->created_at->diffInDays() }}</h4>
                                <small class="text-muted">Days Active</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="mb-1">{{ $admin->last_login_at ? $admin->last_login_at->diffInDays() : 'N/A' }}</h4>
                            <small class="text-muted">Days Since Login</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.status-active { background-color: #d4edda; color: #155724; }
.status-inactive { background-color: #f8d7da; color: #721c24; }
.status-suspended { background-color: #fff3cd; color: #856404; }

.role-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.role-super_admin { background-color: #e7e3ff; color: #5b21b6; }
.role-admin { background-color: #dbeafe; color: #1e40af; }
.role-moderator { background-color: #d1fae5; color: #065f46; }
.role-content_manager { background-color: #fef3c7; color: #92400e; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Status change
    $('.status-change').click(function(e) {
        e.preventDefault();
        const status = $(this).data('status');

        if (confirm(`Are you sure you want to ${status} this admin?`)) {
            $.ajax({
                url: '{{ route("admin.admins.update-status", $admin) }}',
                method: 'PATCH',
                data: {
                    status: status,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Error updating status');
                }
            });
        }
    });

    // Reset password
    $('.reset-password').click(function(e) {
        e.preventDefault();

        if (confirm('Are you sure you want to reset this admin\'s password?')) {
            $.ajax({
                url: '{{ route("admin.admins.reset-password", $admin) }}',
                method: 'PATCH',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    alert('Password reset successfully. New password: ' + response.new_password);
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Error resetting password');
                }
            });
        }
    });

    // Delete admin
    $('.delete-admin').click(function(e) {
        e.preventDefault();
        const adminId = $(this).data('id');

        if (confirm('Are you sure you want to delete this admin? This action cannot be undone.')) {
            $.ajax({
                url: `/admin/admins/${adminId}`,
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    window.location.href = '{{ route("admin.admins.index") }}';
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Error deleting admin');
                }
            });
        }
    });
});
</script>
@endpush
