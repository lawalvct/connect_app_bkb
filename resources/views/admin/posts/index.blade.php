@extends('admin.layouts.app')

@section('title', 'Post Management')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Post Management</h1>
            <p class="text-gray-600">Manage all user posts and content</p>
        </div>
        <div class="flex space-x-3">
            <a href="/admin/posts/reports" id="reports-btn"
               class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                <i class="fas fa-flag mr-2"></i>
                Reports
                <span id="pending-reports-count" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-white text-yellow-700" style="display:none"></span>
            </a>
            <button type="button"
                    onclick="exportPosts()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-download mr-2"></i>
                Export
            </button>
            {{-- <button type="button"
                    onclick="bulkAction()"
                    class="bg-primary hover:bg-primary text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-tasks mr-2"></i>
                Bulk Actions
            </button> --}}
        </div>
    </div>
@endsection

@section('content')
    <div x-data="postManagement()" x-init="loadPosts(); loadSocialCircles(); loadCountries()">

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <!-- First Row - Main Filters -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Posts</label>
                        <div class="relative">
                            <input type="text"
                                   id="search"
                                   x-model="filters.search"
                                   @input="debounceSearch()"
                                   placeholder="Search by content or user..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <div x-show="filters.search"
                                 @click="filters.search = ''; loadPosts()"
                                 class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                                <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Social Circle Filter -->
                    <div>
                        <label for="social_circle" class="block text-sm font-medium text-gray-700 mb-1">Social Circle</label>
                        <select id="social_circle"
                                x-model="filters.social_circle"
                                @change="loadPosts()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Circles</option>
                            <template x-for="circle in socialCircles" :key="circle.id">
                                <option :value="circle.id" x-text="circle.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Country Filter -->
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <select id="country"
                                x-model="filters.country"
                                @change="loadPosts()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Countries</option>
                            <template x-for="country in countries" :key="country.id">
                                <option :value="country.id" x-text="country.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Post Type</label>
                        <select id="type"
                                x-model="filters.type"
                                @change="loadPosts()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Types</option>
                            <option value="text">Text</option>
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                            {{-- <option value="mixed">Mixed</option> --}}
                        </select>
                    </div>



                </div>

                <!-- Second Row - Date Range Filter -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
 <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status"
                                x-model="filters.status"
                                @change="loadPosts()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Posts</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            {{-- <option value="scheduled">Scheduled</option> --}}
                        </select>
                    </div>
                    <!-- Date Range Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Post Date Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <input type="date"
                                       x-model="filters.date_from"
                                       @change="loadPosts()"
                                       placeholder="From"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm">
                            </div>
                            <div>
                                <input type="date"
                                       x-model="filters.date_to"
                                       @change="loadPosts()"
                                       placeholder="To"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm">
                            </div>
                        </div>
                        <div x-show="filters.date_from || filters.date_to" class="mt-1">
                            <button @click="filters.date_from = ''; filters.date_to = ''; loadPosts()"
                                    class="text-xs text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times mr-1"></i>Clear dates
                            </button>
                        </div>
                    </div>

                    <!-- Quick Date Range Presets -->
                    {{-- <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quick Filters</label>
                        <div class="flex flex-wrap gap-2">
                            <button @click="setDateRange('today')"
                                    class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                                Today
                            </button>
                            <button @click="setDateRange('yesterday')"
                                    class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                                Yesterday
                            </button>
                            <button @click="setDateRange('this_week')"
                                    class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                                This Week
                            </button>
                            <button @click="setDateRange('this_month')"
                                    class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors">
                                This Month
                            </button>
                        </div>
                    </div> --}}

                </div>

                <!-- Quick Stats -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-8 gap-4">
                    <div class="text-center p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-600">Total Posts</p>
                        <p class="text-xl font-bold text-blue-900" x-text="stats.total || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-md">
                        <p class="text-sm text-green-600">Published</p>
                        <p class="text-xl font-bold text-green-900" x-text="stats.published || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 rounded-md">
                        <p class="text-sm text-yellow-600">Draft</p>
                        <p class="text-xl font-bold text-yellow-900" x-text="stats.draft || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-md">
                        <p class="text-sm text-purple-600">Scheduled</p>
                        <p class="text-xl font-bold text-purple-900" x-text="stats.scheduled || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-indigo-50 rounded-md">
                        <p class="text-sm text-indigo-600">Today</p>
                        <p class="text-xl font-bold text-indigo-900" x-text="stats.today || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-pink-50 rounded-md">
                        <p class="text-sm text-pink-600">This Week</p>
                        <p class="text-xl font-bold text-pink-900" x-text="stats.this_week || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-md">
                        <p class="text-sm text-red-600">Total Likes</p>
                        <p class="text-xl font-bold text-red-900" x-text="stats.total_likes || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-orange-50 rounded-md">
                        <p class="text-sm text-orange-600">Total Comments</p>
                        <p class="text-xl font-bold text-orange-900" x-text="stats.total_comments || '0'">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts Table -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Posts</h3>
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
                    <span class="text-gray-600">Loading posts...</span>
                </div>
            </div>

            <!-- Posts List -->
            <div x-show="!loading">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Post
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Author
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Social Circle
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Country
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Engagement
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="post in posts" :key="post.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox"
                                               :value="post.id"
                                               x-model="selectedPosts"
                                               class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-start space-x-3">
                                            <div x-show="post.media && post.media.length > 0" class="flex-shrink-0">
                                                <img :src="getMediaThumbnail(post.media[0])"
                                                     :alt="'Post media'"
                                                     class="w-12 h-12 rounded object-cover">
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900" x-text="post.content_preview || 'No content'"></p>
                                                <div class="flex items-center mt-1 space-x-2">
                                                    <span x-show="post.type === 'image'" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <i class="fas fa-image mr-1"></i> Image
                                                    </span>
                                                    <span x-show="post.type === 'video'" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        <i class="fas fa-video mr-1"></i> Video
                                                    </span>
                                                    <span x-show="post.type === 'text'" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        <i class="fas fa-font mr-1"></i> Text
                                                    </span>
                                                    <span x-show="post.type === 'mixed'" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-th-large mr-1"></i> Mixed
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img :src="post.user?.avatar || '/images/default-avatar.png'"
                                                     :alt="post.user?.name"
                                                     class="h-10 w-10 rounded-full">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900" x-text="post.user?.name || 'Unknown'"></div>
                                                <div class="text-sm text-gray-500" x-text="post.user?.email || 'N/A'"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-4 h-4 rounded-full mr-2"
                                                 :style="'background-color: ' + (post.social_circle?.color || '#6B7280')"></div>
                                            <span class="text-sm text-gray-900" x-text="post.social_circle?.name || 'N/A'"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-4 h-4 rounded-full mr-2"
                                                 :style="'background-color: ' + (post.country?.color || '#6B7280')"></div>
                                            <span class="text-sm text-gray-900" x-text="post.user?.country?.name || 'N/A'"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="getTypeBadge(post.type)" x-text="post.type"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-4">
                                            <div class="flex items-center">
                                                <i class="fas fa-heart text-red-400 mr-1"></i>
                                                <span x-text="post.likes_count || 0"></span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-comment text-blue-400 mr-1"></i>
                                                <span x-text="post.comments_count || 0"></span>
                                            </div>
                                            <div class="flex items-center" x-show="post.reports_count > 0">
                                                <i class="fas fa-flag text-red-500 mr-1"></i>
                                                <span x-text="post.reports_count"></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="getStatusBadge(post.status)" x-text="post.status"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div x-text="post.created_at_human"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a :href="'/admin/posts/' + post.id"
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
                                                        <button @click="updatePostStatus(post.id, 'published')"
                                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                                            Publish
                                                        </button>
                                                        <button @click="updatePostStatus(post.id, 'draft')"
                                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">
                                                            Draft
                                                        </button>
                                                        <button @click="deletePost(post.id)"
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
                            <tr x-show="posts.length === 0 && !loading">
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-edit text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-lg">No posts found</p>
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
@endsection

@push('scripts')
<script>
// Fetch pending reports count and update badge
document.addEventListener('DOMContentLoaded', function() {
    fetch('/admin/api/post-reports?status=pending', { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            const count = data.total || (data.reports ? data.reports.length : 0);
            const badge = document.getElementById('pending-reports-count');
            if (badge && count > 0) {
                badge.textContent = count;
                badge.style.display = '';
            }
        });
});
</script>
<script>
    function postManagement() {
        return {
            posts: [],
            stats: {},
            pagination: {},
            socialCircles: [],
            countries: [],
            loading: false,
            selectedPosts: [],
            filters: {
                search: '',
                social_circle: '',
                country: '',
                type: '',
                status: '',
                date_from: '',
                date_to: ''
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
            async loadPosts(page = 1) {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: page,
                        ...this.filters
                    });

                    const response = await fetch(`/admin/api/posts?${params}`, {
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

                    this.posts = data.posts.data || [];
                    this.pagination = {
                        current_page: data.posts.current_page || 1,
                        last_page: data.posts.last_page || 1,
                        from: data.posts.from || 0,
                        to: data.posts.to || 0,
                        total: data.posts.total || 0
                    };
                    this.stats = data.stats || {};
                    this.selectedPosts = [];
                } catch (error) {
                    console.error('Failed to load posts:', error);
                    this.showError('Failed to load posts: ' + error.message);
                } finally {
                    this.loading = false;
                }
            },

            async loadSocialCircles() {
                try {
                    const response = await fetch('/admin/api/social-circles', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();

                    // Handle both the new format and legacy format
                    if (data && data.social_circles && Array.isArray(data.social_circles)) {
                        this.socialCircles = data.social_circles;
                    } else if (Array.isArray(data)) {
                        this.socialCircles = data;
                    } else {
                        this.socialCircles = [];
                    }
                } catch (error) {
                    console.error('Failed to load social circles:', error);
                    this.socialCircles = [];
                }
            },

            debounceSearch() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.loadPosts();
                }, 500);
            },

            changePage(page) {
                if (page >= 1 && page <= this.pagination.last_page) {
                    this.loadPosts(page);
                }
            },

            setDateRange(range) {
                const now = new Date();
                let fromDate = null;
                let toDate = null;

                switch (range) {
                    case 'today':
                        fromDate = now.toISOString().split('T')[0];
                        toDate = now.toISOString().split('T')[0];
                        break;
                    case 'yesterday':
                        const yesterday = new Date(now);
                        yesterday.setDate(now.getDate() - 1);
                        fromDate = yesterday.toISOString().split('T')[0];
                        toDate = yesterday.toISOString().split('T')[0];
                        break;
                    case 'this_week':
                        const startOfWeek = new Date(now);
                        startOfWeek.setDate(now.getDate() - now.getDay());
                        fromDate = startOfWeek.toISOString().split('T')[0];
                        toDate = now.toISOString().split('T')[0];
                        break;
                    case 'this_month':
                        const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
                        fromDate = startOfMonth.toISOString().split('T')[0];
                        toDate = now.toISOString().split('T')[0];
                        break;
                }

                if (fromDate && toDate) {
                    this.filters.date_from = fromDate;
                    this.filters.date_to = toDate;
                    this.loadPosts();
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
                    this.selectedPosts = [];
                } else {
                    this.selectedPosts = this.posts.map(post => post.id);
                }
            },

            isAllSelected() {
                return this.posts.length > 0 && this.selectedPosts.length === this.posts.length;
            },

            getStatusBadge(status) {
                const badges = {
                    'published': 'bg-green-100 text-green-800',
                    'draft': 'bg-yellow-100 text-yellow-800',
                    'scheduled': 'bg-blue-100 text-blue-800'
                };
                return badges[status] || 'bg-gray-100 text-gray-800';
            },

            getTypeBadge(type) {
                const badges = {
                    'text': 'bg-gray-100 text-gray-800',
                    'image': 'bg-blue-100 text-blue-800',
                    'video': 'bg-purple-100 text-purple-800',
                    'mixed': 'bg-green-100 text-green-800'
                };
                return badges[type] || 'bg-gray-100 text-gray-800';
            },

            getMediaThumbnail(media) {
                if (media.thumbnail_path) {
                    return media.thumbnail_path;
                }
                return media.file_path || '/images/placeholder.jpg';
            },

            async updatePostStatus(postId, status) {
                try {
                    const response = await fetch(`/admin/api/posts/${postId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ status: status })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message);
                        this.loadPosts();
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    this.showError('Failed to update post status');
                }
            },

            async deletePost(postId) {
                if (!confirm('Are you sure you want to delete this post?')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/posts/${postId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (response.ok) {
                        this.showSuccess('Post deleted successfully');
                        this.loadPosts();
                    } else {
                        this.showError('Failed to delete post');
                    }
                } catch (error) {
                    this.showError('Failed to delete post');
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

    function exportPosts() {
        window.location.href = '/admin/posts/export';
    }

    function bulkAction() {
        // Implement bulk actions modal/functionality
        alert('Bulk actions functionality');
    }
</script>
@endpush
