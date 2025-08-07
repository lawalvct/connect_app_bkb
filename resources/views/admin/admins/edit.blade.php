@extends('admin.layouts.app')

@section('page-title', 'Edit Admin')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Edit Administrator</h1>
            <p class="mb-0 text-gray-600">Update administrator account information</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.admins.show', $admin) }}" class="btn btn-outline-secondary">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Admins
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.admins.update', $admin) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Personal Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Current Profile Image -->
                            <div class="col-md-12 mb-3">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <div class="d-flex align-items-center">
                                    <div id="currentImage" class="me-3" style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden;">
                                        @if($admin->profile_image)
                                            <img src="{{ Storage::url($admin->profile_image) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <div class="w-100 h-100 bg-primary d-flex align-items-center justify-content-center text-white fw-bold" style="font-size: 2rem;">
                                                {{ strtoupper(substr($admin->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <input type="file" class="form-control @error('profile_image') is-invalid @enderror"
                                               id="profile_image" name="profile_image" accept="image/*">
                                        <small class="form-text text-muted">Leave empty to keep current image</small>
                                        @error('profile_image')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Name -->
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name', $admin->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email', $admin->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone', $admin->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status (only if not editing own profile) -->
                            @if(auth('admin')->user()->id !== $admin->id)
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="active" {{ old('status', $admin->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $admin->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="suspended" {{ old('status', $admin->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Password Change (Optional) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Password Change (Optional)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                       id="password" name="password">
                                <small class="form-text text-muted">Leave empty to keep current password</small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control"
                                       id="password_confirmation" name="password_confirmation">
                            </div>

                            @if(auth('admin')->user()->id !== $admin->id)
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="force_password_change"
                                           name="force_password_change" value="1"
                                           {{ old('force_password_change', $admin->force_password_change) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="force_password_change">
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
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Role & Permissions</h5>
                    </div>
                    <div class="card-body">
                        <!-- Role Selection -->
                        <div class="mb-4">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="admin" {{ old('role', $admin->role) === 'admin' ? 'selected' : '' }}>Administrator</option>
                                <option value="moderator" {{ old('role', $admin->role) === 'moderator' ? 'selected' : '' }}>Moderator</option>
                                <option value="content_manager" {{ old('role', $admin->role) === 'content_manager' ? 'selected' : '' }}>Content Manager</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Additional Permissions -->
                        <div class="mb-3">
                            <label class="form-label">Additional Permissions</label>
                            <div class="row">
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
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="{{ $key }}"
                                               name="permissions[]" value="{{ $key }}"
                                               {{ in_array($key, $currentPermissions) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $key }}">
                                            {{ $label }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Submit Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.admins.show', $admin) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Administrator
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Current Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted">Current Role:</td>
                            <td><span class="badge role-badge role-{{ str_replace(' ', '_', strtolower($admin->getRoleDisplayName())) }}">{{ $admin->getRoleDisplayName() }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Current Status:</td>
                            <td><span class="badge status-badge status-{{ $admin->status }}">{{ ucfirst($admin->status) }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Last Login:</td>
                            <td>{{ $admin->last_login_at ? $admin->last_login_at->format('M d, Y') : 'Never' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Created:</td>
                            <td>{{ $admin->created_at->format('M d, Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if(auth('admin')->user()->id === $admin->id)
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> You are editing your own profile. Role and permission changes are not allowed.
            </div>
            @endif
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
    // Image preview
    $('#profile_image').change(function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#currentImage').html('<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover;">');
            }
            reader.readAsDataURL(file);
        }
    });

    // Password confirmation validation
    $('#password, #password_confirmation').on('keyup', function() {
        const password = $('#password').val();
        const confirmPassword = $('#password_confirmation').val();

        if (password && confirmPassword) {
            if (password !== confirmPassword) {
                $('#password_confirmation').addClass('is-invalid');
                $('#password_confirmation').next('.invalid-feedback').remove();
                $('#password_confirmation').after('<div class="invalid-feedback">Passwords do not match</div>');
            } else {
                $('#password_confirmation').removeClass('is-invalid');
                $('#password_confirmation').next('.invalid-feedback').remove();
            }
        }
    });

    // Form validation
    $('form').submit(function(e) {
        const password = $('#password').val();
        const confirmPassword = $('#password_confirmation').val();

        if (password && password !== confirmPassword) {
            e.preventDefault();
            alert('Password and confirm password do not match');
            return false;
        }

        if (password && password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long');
            return false;
        }
    });
});
</script>
@endpush
