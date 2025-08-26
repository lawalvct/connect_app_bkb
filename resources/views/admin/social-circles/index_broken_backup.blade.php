@extends('admin.layouts.app')

@section('title', 'Social Circles Management')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Social Circles Management</h1>
            <p class="text-gray-600">Manage all social circles and communities</p>
        </div>
        <div class="flex space-x-3">
               </        </div>
    </div>

    </div>

    <div class="card shadow">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Social Circles List</h6>
            <div class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm" placeholder="Search circles..."
                       x-model="filters.search" @input.debounce.300ms="loadSocialCircles()" style="width: 150px;">div>

    </div>

    <div class="card shadow">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Social Circles List</h6>
            <div class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm" placeholder="Search circles..."
                       x-model="filters.search" @input.debounce.300ms="loadSocialCircles()" style="width: 150px;">f="{{ route('admin.social-circles.create') }}"
               class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Create New Circle
            </a>
            <button type="button"
                    onclick="exportSocialCircles()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-download mr-2"></i>
                Export
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="socialCircleManager()" x-init="loadSocialCircles(); loadCountries()">

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <!-- First Row - Main Filters -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Circles</label>
                        <div class="relative">
                            <input type="text"
                                   id="search"
                                   x-model="filters.search"
                                   @input="debounceSearch()"
                                   placeholder="Search by name or description..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <div x-show="filters.search"
                                 @click="filters.search = ''; loadSocialCircles()"
                                 class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                                <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status"
                                x-model="filters.status"
                                @change="loadSocialCircles()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select id="type"
                                x-model="filters.type"
                                @change="loadSocialCircles()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Types</option>
                            <option value="default">Default</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <!-- Sort By -->
                    <div>
                        <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select id="sort_by"
                                x-model="filters.sort_by"
                                @change="loadSocialCircles()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="created_at">Created Date</option>
                            <option value="name">Name</option>
                            <option value="users_count">Member Count</option>
                        </select>
                    </div>

                </div>

                <!-- Second Row - Date Range Filter -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Date Range Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Creation Date Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <input type="date"
                                       x-model="filters.date_from"
                                       @change="loadSocialCircles()"
                                       placeholder="From"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm">
                            </div>
                            <div>
                                <input type="date"
                                       x-model="filters.date_to"
                                       @change="loadSocialCircles()"
                                       placeholder="To"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm">
                            </div>
                        </div>
                        <div x-show="filters.date_from || filters.date_to" class="mt-1">
                            <button @click="filters.date_from = ''; filters.date_to = ''; loadSocialCircles()"
                                    class="text-xs text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times mr-1"></i>Clear dates
                            </button>
                        </div>
                    </div>

                    <!-- Member Count Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Member Count Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <input type="number"
                                       x-model="filters.min_members"
                                       @change="loadSocialCircles()"
                                       placeholder="Min"
                                       min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm">
                            </div>
                            <div>
                                <input type="number"
                                       x-model="filters.max_members"
                                       @change="loadSocialCircles()"
                                       placeholder="Max"
                                       min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Quick Stats -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div class="text-center p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-600">Total Circles</p>
                        <p class="text-xl font-bold text-blue-900" x-text="stats.total || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-md">
                        <p class="text-sm text-green-600">Active</p>
                        <p class="text-xl font-bold text-green-900" x-text="stats.active || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-md">
                        <p class="text-sm text-red-600">Inactive</p>
                        <p class="text-xl font-bold text-red-900" x-text="stats.inactive || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 rounded-md">
                        <p class="text-sm text-yellow-600">Default</p>
                        <p class="text-xl font-bold text-yellow-900" x-text="stats.default || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-md">
                        <p class="text-sm text-purple-600">Total Members</p>
                        <p class="text-xl font-bold text-purple-900" x-text="stats.total_users || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-indigo-50 rounded-md">
                        <p class="text-sm text-indigo-600">Avg Members</p>
                        <p class="text-xl font-bold text-indigo-900" x-text="stats.avg_members || '0'">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Circles Table -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Social Circles</h3>
                    <div class="flex items-center space-x-2">
                        <input type="checkbox"
                               @change="toggleSelectAll()"
                               :checked="isAllSelected()"
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="text-sm text-gray-600">Select All</span>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="p-8 text-center">
                <div class="inline-flex items-center">
                    <i class="fas fa-spinner fa-spin text-primary mr-2"></i>
                    <span class="text-gray-600">Loading social circles...</span>
                </div>
            </div>

            <!-- Circles List -->
            <div x-show="!loading">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Circle
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Members
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Color
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="circle in socialCircles" :key="circle.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox"
                                               :value="circle.id"
                                               x-model="selectedCircles"
                                               class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="h-12 w-12 rounded-full flex items-center justify-center"
                                                     :style="'background-color: ' + (circle.color || '#6B7280')">
                                                    <img x-show="circle.logo_full_url"
                                                         :src="circle.logo_full_url"
                                                         :alt="circle.name"
                                                         class="h-10 w-10 rounded-full object-cover">
                                                    <span x-show="!circle.logo_full_url"
                                                          x-text="circle.name ? circle.name.charAt(0).toUpperCase() : 'C'"
                                                          class="text-white font-medium text-lg"></span>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900" x-text="circle.name"></p>
                                                <p class="text-sm text-gray-500" x-text="circle.description || 'No description'"></p>
                                                <div class="flex items-center mt-1 space-x-2">
                                                    <span x-show="circle.is_default" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <i class="fas fa-star mr-1"></i> Default
                                                    </span>
                                                    <span x-show="circle.posts_count > 0" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <i class="fas fa-edit mr-1"></i> <span x-text="circle.posts_count"></span> Posts
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-users text-gray-400 mr-2"></i>
                                                <span class="font-medium" x-text="circle.users_count || 0"></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex gap-1">
                                            <span x-show="circle.is_active"
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                            <span x-show="!circle.is_active"
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Inactive
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-6 h-6 rounded-full border-2 border-gray-200"
                                                 :style="'background-color: ' + (circle.color || '#6B7280')"></div>
                                            <span class="ml-2 text-xs text-gray-500" x-text="circle.color || '#6B7280'"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div x-text="formatDate(circle.created_at)"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a :href="'/admin/social-circles/' + circle.id"
                                               class="text-primary hover:text-primary font-medium">
                                                View
                                            </a>
                                            <div class="relative" x-data="{ open: false }">
                                                <button @click="open = !open"
                                                        class="text-gray-400 hover:text-gray-600">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div x-show="open"
                                                     @click.away="open = false"
                                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                                    <div class="py-1">
                                                        <a :href="'/admin/social-circles/' + circle.id + '/edit'"
                                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            Edit Circle
                                                        </a>
                                                        <button @click="updateCircleStatus(circle.id, !circle.is_active)"
                                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                                            <span x-text="circle.is_active ? 'Deactivate' : 'Activate'"></span>
                                                        </button>
                                                        <button @click="deleteCircle(circle.id)"
                                                                class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100 w-full text-left">
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <tr x-show="socialCircles.length === 0 && !loading">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-circle text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-lg">No social circles found</p>
                                    <p class="text-sm">Try adjusting your filters or search criteria</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div x-show="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
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
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <button @click="changePage(pagination.current_page - 1)"
                                            :disabled="pagination.current_page <= 1"
                                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <template x-for="page in getPageNumbers()" :key="page">
                                        <button @click="changePage(page)"
                                                :class="page === pagination.current_page ?
                                                       'bg-primary text-white' :
                                                       'bg-white text-gray-500 hover:bg-gray-50'"
                                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium">
                                            <span x-text="page"></span>
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

    </div>

    <div class="card shadow">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Social Circles List</h6>
            <div class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm" placeholder="Search circles..."
                       x-model="filters.search" @input.debounce.300ms="loadSocialCircles()" style="width: 150px;">
                <select class="form-control form-control-sm" x-model="filters.status" @change="loadSocialCircles()" style="width: 120px;">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
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
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <img x-show="circle.logo_full_url" :src="circle.logo_full_url" :alt="circle.name"
                                                     class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                <span x-show="!circle.logo_full_url" x-text="circle.name ? circle.name.charAt(0).toUpperCase() : 'C'"
                                                      class="text-primary font-weight-bold"></span>
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
                                    <span x-show="circle.is_default" class="badge badge-warning mr-1">Default</span>
                                    <span x-show="circle.is_active" class="badge badge-success">Active</span>
                                    <span x-show="!circle.is_active" class="badge badge-secondary">Inactive</span>
                                </td>
                                <td>
                                    <span x-text="formatDate(circle.created_at)"></span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" @click="editCircle(circle)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm"
                                                x-bind:class="circle.is_active ? 'btn-outline-warning' : 'btn-outline-success'"
                                                @click="toggleStatus(circle)"
                                                x-bind:title="circle.is_active ? 'Deactivate' : 'Activate'">
                                            <i x-bind:class="circle.is_active ? 'fas fa-pause' : 'fas fa-play'"></i>
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
                    <li class="page-item" x-bind:class="{ 'disabled': pagination.current_page <= 1 }">
                        <button class="page-link" @click="changePage(pagination.current_page - 1)">Previous</button>
                    </li>
                    <template x-for="page in Math.min(pagination.last_page, 10)" :key="page">
                        <li class="page-item" x-bind:class="{ 'active': page === pagination.current_page }">
                            <button class="page-link" x-text="page" @click="changePage(page)"></button>
                        </li>
                    </template>
                    <li class="page-item" x-bind:class="{ 'disabled': pagination.current_page >= pagination.last_page }">
                        <button class="page-link" @click="changePage(pagination.current_page + 1)">Next</button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function socialCircleManager() {
        return {
            socialCircles: [],
            stats: {},
            pagination: {},
            countries: [],
            loading: false,
            selectedCircles: [],
            filters: {
                search: '',
                status: '',
                type: '',
                sort_by: 'created_at',
                date_from: '',
                date_to: '',
                min_members: '',
                max_members: ''
            },
            searchTimeout: null,

            async loadCountries() {
                try {
                    const response = await fetch('/admin/api/countries', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if (!response.ok) throw new Error('Failed to fetch countries');
                    const data = await response.json();
                    this.countries = data.countries || [];
                } catch (e) {
                    this.countries = [];
                }
            },

            async loadSocialCircles(page = 1) {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: page,
                        per_page: 10,
                        ...this.filters
                    });

                    const response = await fetch(`/admin/api/social-circles?${params}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        if (response.status === 401) {
                            window.location.href = '/admin/login';
                            return;
                        }
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();

                    if (data.error) {
                        throw new Error(data.error);
                    }

                    // Handle different API response formats
                    if (data.success && data.data) {
                        // New format: { success: true, data: { data: [...], current_page: ... }, stats: {...} }
                        this.socialCircles = data.data.data || [];
                        this.pagination = {
                            current_page: data.data.current_page || 1,
                            last_page: data.data.last_page || 1,
                            from: data.data.from || 0,
                            to: data.data.to || 0,
                            total: data.data.total || 0,
                            per_page: data.data.per_page || 10
                        };
                        this.stats = data.stats || {};
                    } else if (data.data && Array.isArray(data.data)) {
                        // Alternative format: { data: [...] }
                        this.socialCircles = data.data || [];
                        this.pagination = {
                            current_page: data.current_page || 1,
                            last_page: data.last_page || 1,
                            from: data.from || 0,
                            to: data.to || 0,
                            total: data.total || 0,
                            per_page: data.per_page || 10
                        };
                        this.stats = data.stats || {};
                    } else if (Array.isArray(data)) {
                        // Simple array format: [...]
                        this.socialCircles = data;
                        this.pagination = {
                            current_page: 1,
                            last_page: 1,
                            from: 1,
                            to: data.length,
                            total: data.length,
                            per_page: data.length
                        };
                        this.stats = {};
                    } else {
                        // Default fallback
                        this.socialCircles = [];
                        this.pagination = {
                            current_page: 1,
                            last_page: 1,
                            from: 0,
                            to: 0,
                            total: 0,
                            per_page: 10
                        };
                        this.stats = {};
                    }

                    this.selectedCircles = [];
                } catch (error) {
                    console.error('Failed to load social circles:', error);
                    this.showError('Failed to load social circles: ' + error.message);
                } finally {
                    this.loading = false;
                }
            },

            debounceSearch() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.loadSocialCircles();
                }, 500);
            },

            changePage(page) {
                if (page >= 1 && page <= this.pagination.last_page) {
                    this.loadSocialCircles(page);
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
                    this.selectedCircles = [];
                } else {
                    this.selectedCircles = this.socialCircles.map(circle => circle.id);
                }
            },

            isAllSelected() {
                return this.socialCircles.length > 0 && this.selectedCircles.length === this.socialCircles.length;
            },

            async updateCircleStatus(circleId, isActive) {
                try {
                    const response = await fetch(`/admin/social-circles/${circleId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ is_active: isActive })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message || 'Circle status updated successfully');
                        this.loadSocialCircles();
                    } else {
                        this.showError(data.message || 'Failed to update circle status');
                    }
                } catch (error) {
                    this.showError('Failed to update circle status');
                }
            },

            async deleteCircle(circleId) {
                if (!confirm('Are you sure you want to delete this social circle? This action cannot be undone.')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/social-circles/${circleId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (response.ok) {
                        this.showSuccess('Social circle deleted successfully');
                        this.loadSocialCircles();
                    } else {
                        this.showError('Failed to delete social circle');
                    }
                } catch (error) {
                    this.showError('Failed to delete social circle');
                }
            },

            formatDate(dateString) {
                return new Date(dateString).toLocaleDateString();
            },

            showSuccess(message) {
                // You can implement a toast notification here
                alert(message);
            },

            showError(message) {
                // You can implement a toast notification here
                alert(message);
            }
        }
    }

    function exportSocialCircles() {
        window.location.href = '/admin/social-circles/export';
    }
</script>
@endpush
