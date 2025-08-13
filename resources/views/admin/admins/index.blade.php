@extends('admin.layouts.app')

@section('page-title', 'Admin Management')

@section('content')
<div class="max-w-7xl mx-auto" x-data="adminManagement()" x-init="loadAdmins()">>
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
        <!-- Filter Header with Toggle -->
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer" @click="filtersVisible = !filtersVisible">
            <div class="flex items-center space-x-2">
                <i class="fas fa-filter text-gray-500"></i>
                <h3 class="text-lg font-medium text-gray-700">Filters</h3>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"
                      x-show="Object.values(filters).some(val => val !== '')">
                    Filters Applied
                </span>
            </div>
            <div>
                <i class="fas" :class="filtersVisible ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </div>
        </div>

        <!-- Filter Content -->
        <div class="p-6" x-show="filtersVisible" x-transition>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="roleFilter" class="block text-sm font-medium text-gray-700 mb-2">Filter by Role</label>
                    <select id="roleFilter" x-model="filters.role" @change="loadAdmins()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Roles</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="admin">Admin</option>
                        <option value="moderator">Moderator</option>
                        <option value="content_manager">Content Manager</option>
                    </select>
                </div>
                <div>
                    <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                    <select id="statusFilter" x-model="filters.status" @change="loadAdmins()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <div class="relative">
                        <input type="text"
                               x-model="filters.search"
                               @input.debounce.500ms="loadAdmins()"
                               placeholder="Search admins..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- Clear Filters Button -->
            <div class="mt-4 flex justify-end">
                <button @click="clearAllFilters()"
                        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Admins Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h5 class="text-lg font-medium text-gray-900">Administrator Accounts</h5>

                <!-- Bulk Actions -->
                <div x-show="selectedAdmins.length > 0" class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600" x-text="`${selectedAdmins.length} selected`"></span>
                    <select x-model="bulkAction" class="px-3 py-1 border border-gray-300 rounded-md text-sm">
                        <option value="">Select Action</option>
                        <option value="active">Activate</option>
                        <option value="inactive">Deactivate</option>
                        <option value="suspended">Suspend</option>
                    </select>
                    <button @click="applyBulkAction()" class="px-3 py-1 bg-primary text-white rounded-md text-sm hover:bg-primary/90">
                        Apply
                    </button>
                </div>

                <!-- Pagination Info -->
                <div class="text-sm text-gray-600" x-show="!loading">
                    <span x-text="`Showing ${pagination.from || 0} to ${pagination.to || 0} of ${pagination.total || 0} admins`"></span>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-4"></i>
            <p class="text-gray-600">Loading administrators...</p>
        </div>

        <!-- Table Content -->
        <div x-show="!loading" class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="text-left py-3 px-4">
                            <input type="checkbox"
                                   :checked="isAllSelected()"
                                   @change="toggleSelectAll()"
                                   class="rounded border-gray-300 text-primary focus:ring-primary">
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
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="admin in admins" :key="admin.id">
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4">
                                <input type="checkbox"
                                       :value="admin.id"
                                       x-model="selectedAdmins"
                                       x-show="admin.can_edit"
                                       class="rounded border-gray-300 text-primary focus:ring-primary">
                            </td>
                            <td class="py-3 px-4">
                                <div x-show="admin.profile_image" class="w-10 h-10 rounded-full overflow-hidden">
                                    <img :src="admin.profile_image" :alt="admin.name" class="w-full h-full object-cover">
                                </div>
                                <div x-show="!admin.profile_image"
                                     class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-bold text-sm"
                                     x-text="admin.name.charAt(0).toUpperCase()">
                                </div>
                            </td>
                            <td class="py-3 px-4 font-medium text-gray-900" x-text="admin.name"></td>
                            <td class="py-3 px-4 text-gray-600" x-text="admin.email"></td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="getRoleBadgeClass(admin.role)"
                                      x-text="admin.role"></span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="getStatusBadgeClass(admin.status)"
                                      x-text="admin.status.charAt(0).toUpperCase() + admin.status.slice(1)"></span>
                            </td>
                            <td class="py-3 px-4 text-gray-600" x-text="admin.last_login_at"></td>
                            <td class="py-3 px-4 text-gray-600" x-text="admin.created_at"></td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-1">
                                    <!-- View -->
                                    <a :href="`/admin/admins/${admin.id}`"
                                       class="inline-flex items-center px-2 py-1 text-xs text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors"
                                       title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <!-- Edit -->
                                    <a x-show="admin.can_edit"
                                       :href="`/admin/admins/${admin.id}/edit`"
                                       class="inline-flex items-center px-2 py-1 text-xs text-primary bg-primary/10 hover:bg-primary/20 rounded-md transition-colors"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- Status Actions -->
                                    <div x-show="admin.can_edit" class="relative" x-data="{ open: false }">
                                        <button @click="open = !open"
                                                class="inline-flex items-center px-2 py-1 text-xs text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-md transition-colors"
                                                title="Actions">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <div x-show="open"
                                             @click.away="open = false"
                                             x-transition
                                             class="absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                            <div class="py-1">
                                                <button @click="updateStatus(admin.id, 'active'); open = false"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Activate
                                                </button>
                                                <button @click="updateStatus(admin.id, 'inactive'); open = false"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Deactivate
                                                </button>
                                                <button @click="updateStatus(admin.id, 'suspended'); open = false"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Suspend
                                                </button>
                                                <div class="border-t border-gray-100"></div>
                                                <button @click="resetPassword(admin); open = false"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    Reset Password
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete -->
                                    <button x-show="admin.can_delete"
                                            @click="deleteAdmin(admin)"
                                            class="inline-flex items-center px-2 py-1 text-xs text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition-colors"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="!loading && admins.length === 0">
                        <td colspan="9" class="py-8 text-center text-gray-500">
                            No administrators found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.total > 0" class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button @click="changePage(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Previous
                    </button>
                    <button @click="changePage(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium" x-text="pagination.from"></span> to <span class="font-medium" x-text="pagination.to"></span> of <span class="font-medium" x-text="pagination.total"></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button @click="changePage(pagination.current_page - 1)"
                                    :disabled="pagination.current_page <= 1"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i>
                            </button>

                            <template x-for="page in getPageNumbers()" :key="page">
                                <button @click="changePage(page)"
                                        :class="page === pagination.current_page ? 'bg-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium"
                                        x-text="page">
                                </button>
                            </template>

                            <button @click="changePage(pagination.current_page + 1)"
                                    :disabled="pagination.current_page >= pagination.last_page"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('admin.admins.partials.modals')
@endsection

@push('styles')
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
</style>
@endpush

@push('scripts')
<script>
function adminManagement() {
    return {
        admins: [],
        pagination: {},
        loading: false,
        selectedAdmins: [],
        bulkAction: '',
        filters: {
            search: '',
            role: '',
            status: ''
        },
        filtersVisible: false,

        async loadAdmins(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    ...this.filters
                });

                const response = await fetch(`{{ route('admin.admins.api.admins') }}?${params}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load admins');
                }

                const data = await response.json();

                this.admins = data.data || [];
                this.pagination = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    from: data.from || 0,
                    to: data.to || 0,
                    total: data.total || 0
                };
                this.selectedAdmins = [];

            } catch (error) {
                console.error('Failed to load admins:', error);
                this.showError('Failed to load administrators');
            } finally {
                this.loading = false;
            }
        },

        clearAllFilters() {
            this.filters = {
                search: '',
                role: '',
                status: ''
            };
            this.loadAdmins();
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadAdmins(page);
            }
        },

        getPageNumbers() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;

            let start = Math.max(1, current - 2);
            let end = Math.min(last, current + 2);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        toggleSelectAll() {
            if (this.isAllSelected()) {
                this.selectedAdmins = [];
            } else {
                this.selectedAdmins = this.admins.filter(admin => admin.can_edit).map(admin => admin.id);
            }
        },

        isAllSelected() {
            const editableAdmins = this.admins.filter(admin => admin.can_edit);
            return editableAdmins.length > 0 && this.selectedAdmins.length === editableAdmins.length;
        },

        getRoleBadgeClass(role) {
            const classes = {
                'Super Admin': 'bg-purple-100 text-purple-800',
                'Admin': 'bg-blue-100 text-blue-800',
                'Moderator': 'bg-green-100 text-green-800',
                'Content Manager': 'bg-yellow-100 text-yellow-800'
            };
            return classes[role] || 'bg-gray-100 text-gray-800';
        },

        getStatusBadgeClass(status) {
            const classes = {
                'active': 'bg-green-100 text-green-800',
                'inactive': 'bg-red-100 text-red-800',
                'suspended': 'bg-yellow-100 text-yellow-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        async updateStatus(adminId, status) {
            try {
                const response = await fetch(`/admin/admins/${adminId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ status })
                });

                if (response.ok) {
                    this.loadAdmins(this.pagination.current_page);
                    this.showSuccess('Status updated successfully');
                } else {
                    throw new Error('Failed to update status');
                }
            } catch (error) {
                this.showError('Failed to update status');
            }
        },

        async resetPassword(admin) {
            if (confirm(`Are you sure you want to reset ${admin.name}'s password?`)) {
                try {
                    const response = await fetch(`/admin/admins/${admin.id}/reset-password`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.showSuccess(`Password reset successfully. New password: ${data.new_password}`);
                    } else {
                        throw new Error('Failed to reset password');
                    }
                } catch (error) {
                    this.showError('Failed to reset password');
                }
            }
        },

        async deleteAdmin(admin) {
            if (confirm(`Are you sure you want to delete ${admin.name}? This action cannot be undone.`)) {
                try {
                    const response = await fetch(`/admin/admins/${admin.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        }
                    });

                    if (response.ok) {
                        this.loadAdmins(this.pagination.current_page);
                        this.showSuccess('Administrator deleted successfully');
                    } else {
                        throw new Error('Failed to delete admin');
                    }
                } catch (error) {
                    this.showError('Failed to delete administrator');
                }
            }
        },

        async applyBulkAction() {
            if (!this.bulkAction) {
                this.showError('Please select an action');
                return;
            }

            if (this.selectedAdmins.length === 0) {
                this.showError('Please select at least one administrator');
                return;
            }

            if (confirm(`Are you sure you want to ${this.bulkAction} the selected administrators?`)) {
                try {
                    const response = await fetch('{{ route("admin.admins.api.bulk-status") }}', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({
                            admin_ids: this.selectedAdmins,
                            status: this.bulkAction
                        })
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.loadAdmins(this.pagination.current_page);
                        this.selectedAdmins = [];
                        this.bulkAction = '';
                        this.showSuccess(data.message || 'Bulk action completed successfully');
                    } else {
                        throw new Error('Failed to perform bulk action');
                    }
                } catch (error) {
                    this.showError('Failed to perform bulk action');
                }
            }
        },

        showSuccess(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 border border-green-600';
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        },

        showError(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 border border-red-600';
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }
    }
}
</script>
@endpush
