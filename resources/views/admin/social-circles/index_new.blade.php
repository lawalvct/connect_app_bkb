@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Social Circles Management</h1>
        <a href="{{ route('admin.social-circles.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Circle
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4" x-data="socialCircleManager()" x-init="loadSocialCircles()">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Circles</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" x-text="stats.total || 0"></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Circles</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" x-text="stats.active || 0"></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Members</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" x-text="stats.total_users || 0"></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Default Circle</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" x-text="stats.default_circle || 'None'"></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="card shadow mt-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Social Circles List</h6>
                <div class="d-flex gap-2">
                    <!-- Search -->
                    <div class="form-group mb-0">
                        <input type="text" class="form-control form-control-sm" placeholder="Search circles..."
                               x-model="filters.search" @input.debounce.300ms="loadSocialCircles()">
                    </div>
                    <!-- Status Filter -->
                    <select class="form-control form-control-sm" x-model="filters.status" @change="loadSocialCircles()">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <!-- Type Filter -->
                    <select class="form-control form-control-sm" x-model="filters.type" @change="loadSocialCircles()">
                        <option value="">All Types</option>
                        <option value="default">Default</option>
                        <option value="custom">Custom</option>
                    </select>
                    <!-- Export -->
                    <button class="btn btn-outline-primary btn-sm" @click="exportData()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Loading -->
                <div x-show="loading" class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive" x-show="!loading">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Circle</th>
                                <th>Members</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="circle in socialCircles" :key="circle.id">
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <div x-show="circle.logo && (circle.logo_full_url || circle.logo_url)"
                                                     class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px;">
                                                    <img :src="circle.logo_full_url || (circle.logo_url ? '/' + circle.logo_url + '/' + circle.logo : '/storage/social-circles/' + circle.logo)"
                                                         :alt="circle.name"
                                                         class="rounded-circle"
                                                         style="width: 32px; height: 32px; object-fit: cover;"
                                                         @error="$el.parentElement.style.display='none'; $el.parentElement.nextElementSibling.style.display='flex';">
                                                </div>
                                                <div x-show="!circle.logo || !(circle.logo_full_url || circle.logo_url)"
                                                     class="rounded-circle d-flex align-items-center justify-content-center text-white"
                                                     style="width: 40px; height: 40px;"
                                                     :style="'background-color: ' + (circle.color || '#6B7280')">
                                                    <span x-text="circle.name.charAt(0).toUpperCase()"></span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-weight-bold" x-text="circle.name"></div>
                                                <div class="text-muted small" x-text="circle.description || 'No description'"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span x-text="circle.users_count || 0"></span> users
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <span x-show="circle.is_default" class="badge badge-warning">Default</span>
                                            <span x-show="circle.is_active" class="badge badge-success">Active</span>
                                            <span x-show="!circle.is_active" class="badge badge-secondary">Inactive</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span x-text="formatDate(circle.created_at)"></span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" @click="viewCircle(circle)" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" @click="editCircle(circle)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm"
                                                    :class="circle.is_active ? 'btn-outline-warning' : 'btn-outline-success'"
                                                    @click="toggleStatus(circle)"
                                                    :title="circle.is_active ? 'Deactivate' : 'Activate'">
                                                <i :class="circle.is_active ? 'fas fa-pause' : 'fas fa-play'"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" @click="deleteCircle(circle)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <!-- No Results -->
                    <div x-show="socialCircles.length === 0 && !loading" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-circle fa-3x mb-3"></i>
                            <p>No social circles found.</p>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <nav x-show="pagination.last_page > 1" class="mt-4">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item" :class="{ 'disabled': pagination.current_page <= 1 }">
                            <button class="page-link" @click="changePage(pagination.current_page - 1)">Previous</button>
                        </li>
                        <template x-for="page in Math.min(pagination.last_page, 10)" :key="page">
                            <li class="page-item" :class="{ 'active': page === pagination.current_page }">
                                <button class="page-link" x-text="page" @click="changePage(page)"></button>
                            </li>
                        </template>
                        <li class="page-item" :class="{ 'disabled': pagination.current_page >= pagination.last_page }">
                            <button class="page-link" @click="changePage(pagination.current_page + 1)">Next</button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function socialCircleManager() {
    return {
        loading: false,
        socialCircles: [],
        stats: {
            total: 0,
            active: 0,
            total_users: 0,
            default_circle: ''
        },
        filters: {
            search: '',
            status: '',
            type: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0
        },

        async loadSocialCircles() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    ...this.filters
                });

                const response = await fetch(`/admin/api/social-circles?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.socialCircles = data.data.data || [];
                    this.pagination = {
                        current_page: data.data.current_page || 1,
                        last_page: data.data.last_page || 1,
                        per_page: data.data.per_page || 10,
                        total: data.data.total || 0
                    };
                    this.stats = data.stats || this.stats;
                }
            } catch (error) {
                console.error('Error loading social circles:', error);
            } finally {
                this.loading = false;
            }
        },

        async toggleStatus(circle) {
            try {
                const response = await fetch(`/admin/social-circles/${circle.id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        is_active: !circle.is_active
                    })
                });

                const data = await response.json();
                if (data.success) {
                    circle.is_active = !circle.is_active;
                    this.loadSocialCircles(); // Refresh stats
                } else {
                    alert('Error updating status: ' + data.message);
                }
            } catch (error) {
                console.error('Error updating status:', error);
                alert('Error updating status');
            }
        },

        async deleteCircle(circle) {
            if (!confirm('Are you sure you want to delete this social circle?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/social-circles/${circle.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.loadSocialCircles();
                } else {
                    alert('Error deleting circle: ' + data.message);
                }
            } catch (error) {
                console.error('Error deleting circle:', error);
                alert('Error deleting circle');
            }
        },

        viewCircle(circle) {
            window.location.href = `/admin/social-circles/${circle.id}`;
        },

        editCircle(circle) {
            window.location.href = `/admin/social-circles/${circle.id}/edit`;
        },

        exportData() {
            window.open('/admin/social-circles/export', '_blank');
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                this.loadSocialCircles();
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                status: '',
                type: ''
            };
            this.pagination.current_page = 1;
            this.loadSocialCircles();
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString();
        }
    }
}
</script>
@endpush
