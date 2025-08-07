@extends('admin.layouts.app')

@section('page-title', 'Admin Management')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-semibold text-gray-900">Admin Management</h1>
            <p class="text-gray-600 mt-1">Manage administrator accounts and permissions</p>
        </div>
        @if(auth('admin')->user()->hasRole('super_admin') || auth('admin')->user()->hasRole('admin'))
        <a href="{{ route('admin.admins.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors inline-flex items-center">
            <i class="fas fa-plus mr-2"></i> Add New Admin
        </a>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="roleFilter" class="block text-sm font-medium text-gray-700 mb-2">Filter by Role</label>
                    <select id="roleFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Roles</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="admin">Admin</option>
                        <option value="moderator">Moderator</option>
                        <option value="content_manager">Content Manager</option>
                    </select>
                </div>
                <div>
                    <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                    <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bulk Actions</label>
                    <div class="flex gap-2">
                        <select id="bulkAction" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Select Action</option>
                            <option value="active">Activate Selected</option>
                            <option value="inactive">Deactivate Selected</option>
                            <option value="suspended">Suspend Selected</option>
                        </select>
                        <button id="applyBulkAction" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admins Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-medium text-gray-900">Administrator Accounts</h5>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table id="adminsTable" class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary">
                            </th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Profile</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Name</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Email</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Role</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Status</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Last Login</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Created</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-900">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('admin.admins.partials.modals')
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet">
<style>
.profile-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-weight: 500;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}
.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}
.status-suspended {
    background-color: #fff3cd;
    color: #856404;
}

.role-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-weight: 500;
}

.role-super_admin {
    background-color: #e7e3ff;
    color: #5b21b6;
}
.role-admin {
    background-color: #dbeafe;
    color: #1e40af;
}
.role-moderator {
    background-color: #d1fae5;
    color: #065f46;
}
.role-content_manager {
    background-color: #fef3c7;
    color: #92400e;
}

/* DataTables styling with Tailwind */
.dataTables_wrapper {
    font-family: inherit;
}

.dataTables_length select,
.dataTables_filter input {
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 0.5rem;
    background-color: white;
}

.dataTables_paginate .paginate_button {
    padding: 0.5rem 0.75rem;
    margin: 0 0.125rem;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    background-color: white;
    color: #374151;
    text-decoration: none;
}

.dataTables_paginate .paginate_button:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.dataTables_paginate .paginate_button.current {
    background-color: #A20030;
    border-color: #A20030;
    color: white;
}

#adminsTable tbody tr:hover {
    background-color: #f9fafb;
}

#adminsTable tbody tr {
    border-bottom: 1px solid #e5e7eb;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background-color: white;
    min-width: 160px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border-radius: 0.375rem;
    border: 1px solid #e5e7eb;
    z-index: 1000;
    padding: 0.25rem 0;
    margin-top: 0.25rem;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: block;
    width: 100%;
    padding: 0.5rem 1rem;
    color: #374151;
    text-decoration: none;
    border: none;
    background: none;
    text-align: left;
    cursor: pointer;
}

.dropdown-item:hover {
    background-color: #f3f4f6;
}

.dropdown-divider {
    height: 1px;
    background-color: #e5e7eb;
    margin: 0.25rem 0;
}

.btn-group {
    position: relative;
    display: inline-block;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#adminsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.admins.api.admins") }}',
            data: function(d) {
                d.role = $('#roleFilter').val();
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (row.can_edit) {
                        return '<input type="checkbox" class="admin-checkbox rounded border-gray-300 text-primary focus:ring-primary" value="' + data + '">';
                    }
                    return '';
                }
            },
            {
                data: 'profile_image',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (data) {
                        return '<img src="' + data + '" alt="Profile" class="profile-img">';
                    }
                    return '<div class="profile-img bg-primary flex items-center justify-center text-white font-bold">' +
                           row.name.charAt(0).toUpperCase() + '</div>';
                }
            },
            { data: 'name' },
            { data: 'email' },
            {
                data: 'role',
                render: function(data, type, row) {
                    const roleClass = 'role-' + data.toLowerCase().replace(' ', '_');
                    return '<span class="role-badge ' + roleClass + '">' + data + '</span>';
                }
            },
            {
                data: 'status',
                render: function(data, type, row) {
                    const statusClass = 'status-' + data;
                    return '<span class="status-badge ' + statusClass + '">' +
                           data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                }
            },
            { data: 'last_login_at' },
            { data: 'created_at' },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let actions = '';

                    // View button
                    actions += '<a href="/admin/admins/' + data + '" class="inline-flex items-center px-3 py-1 mr-1 text-sm text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors" title="View">' +
                              '<i class="fas fa-eye"></i></a>';

                    // Edit button
                    if (row.can_edit) {
                        actions += '<a href="/admin/admins/' + data + '/edit" class="inline-flex items-center px-3 py-1 mr-1 text-sm text-primary bg-primary/10 hover:bg-primary/20 rounded-md transition-colors" title="Edit">' +
                                  '<i class="fas fa-edit"></i></a>';
                    }

                    // Status change dropdown
                    if (row.can_edit) {
                        actions += '<div class="btn-group mr-1">' +
                                  '<button class="inline-flex items-center px-3 py-1 text-sm text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-md transition-colors dropdown-toggle" onclick="toggleDropdown(this)">' +
                                  '<i class="fas fa-cog"></i></button>' +
                                  '<div class="dropdown-menu">' +
                                  '<a class="dropdown-item status-change" href="#" data-id="' + data + '" data-status="active">Activate</a>' +
                                  '<a class="dropdown-item status-change" href="#" data-id="' + data + '" data-status="inactive">Deactivate</a>' +
                                  '<a class="dropdown-item status-change" href="#" data-id="' + data + '" data-status="suspended">Suspend</a>' +
                                  '<div class="dropdown-divider"></div>' +
                                  '<a class="dropdown-item reset-password" href="#" data-id="' + data + '">Reset Password</a>' +
                                  '</div></div>';
                    }

                    // Delete button (only for super admin)
                    if (row.can_delete) {
                        actions += '<button class="inline-flex items-center px-3 py-1 text-sm text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition-colors delete-admin" data-id="' + data + '" title="Delete">' +
                                  '<i class="fas fa-trash"></i></button>';
                    }

                    return actions;
                }
            }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        language: {
            processing: "Loading administrators...",
            emptyTable: "No administrators found"
        }
    });

    // Filter event handlers
    $('#roleFilter, #statusFilter').change(function() {
        table.ajax.reload();
    });

    // Select all checkbox
    $('#selectAll').change(function() {
        $('.admin-checkbox').prop('checked', this.checked);
    });

    // Individual checkbox change
    $(document).on('change', '.admin-checkbox', function() {
        const total = $('.admin-checkbox').length;
        const checked = $('.admin-checkbox:checked').length;
        $('#selectAll').prop('checked', total === checked);
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
    });

    // Status change
    $(document).on('click', '.status-change', function(e) {
        e.preventDefault();
        const adminId = $(this).data('id');
        const status = $(this).data('status');

        $.ajax({
            url: `/admin/admins/${adminId}/status`,
            method: 'PATCH',
            data: {
                status: status,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                table.ajax.reload();
                showAlert('success', 'Status updated successfully');
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON?.message || 'Error updating status');
            }
        });
    });

    // Reset password
    $(document).on('click', '.reset-password', function(e) {
        e.preventDefault();
        const adminId = $(this).data('id');

        if (confirm('Are you sure you want to reset this admin\'s password?')) {
            $.ajax({
                url: `/admin/admins/${adminId}/reset-password`,
                method: 'PATCH',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showAlert('success', 'Password reset successfully. New password: ' + response.new_password);
                },
                error: function(xhr) {
                    showAlert('danger', xhr.responseJSON?.message || 'Error resetting password');
                }
            });
        }
    });

    // Delete admin
    $(document).on('click', '.delete-admin', function(e) {
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
                    table.ajax.reload();
                    showAlert('success', 'Admin deleted successfully');
                },
                error: function(xhr) {
                    showAlert('danger', xhr.responseJSON?.message || 'Error deleting admin');
                }
            });
        }
    });

    // Bulk actions
    $('#applyBulkAction').click(function() {
        const action = $('#bulkAction').val();
        const selectedIds = $('.admin-checkbox:checked').map(function() {
            return this.value;
        }).get();

        if (!action) {
            showAlert('warning', 'Please select an action');
            return;
        }

        if (selectedIds.length === 0) {
            showAlert('warning', 'Please select at least one admin');
            return;
        }

        if (confirm(`Are you sure you want to ${action} the selected admins?`)) {
            $.ajax({
                url: '{{ route("admin.admins.api.bulk-status") }}',
                method: 'PATCH',
                data: {
                    admin_ids: selectedIds,
                    status: action,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    table.ajax.reload();
                    $('#selectAll').prop('checked', false);
                    $('#bulkAction').val('');
                    showAlert('success', response.message);
                },
                error: function(xhr) {
                    showAlert('danger', xhr.responseJSON?.message || 'Error performing bulk action');
                }
            });
        }
    });

    // Alert helper function
    function showAlert(type, message) {
        const typeClasses = {
            'success': 'bg-green-100 border-green-400 text-green-700',
            'danger': 'bg-red-100 border-red-400 text-red-700',
            'warning': 'bg-yellow-100 border-yellow-400 text-yellow-700',
            'info': 'bg-blue-100 border-blue-400 text-blue-700'
        };

        const alertHtml = `<div class="mb-4 p-4 border rounded-lg ${typeClasses[type] || typeClasses.info} alert-dismissible" role="alert">
            ${message}
            <button type="button" class="float-right text-xl leading-none font-semibold" onclick="this.parentElement.remove()">&times;</button>
        </div>`;

        const alertContainer = $('.max-w-7xl').first();
        alertContainer.prepend(alertHtml);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.alert-dismissible').fadeOut();
        }, 5000);
    }

    // Dropdown toggle function
    window.toggleDropdown = function(button) {
        const dropdown = button.nextElementSibling;
        const isOpen = dropdown.classList.contains('show');

        // Close all dropdowns
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });

        // Toggle current dropdown
        if (!isOpen) {
            dropdown.classList.add('show');
        }
    };

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.btn-group')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
});
</script>
@endpush
