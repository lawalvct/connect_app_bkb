@extends('admin.layouts.app')

@section('title', 'Stories Management')

@section('content')
<div x-data="storyManagement()" x-init="loadStories(); loadStats(); loadCountries()">
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Stories Management</h1>
                <p class="text-gray-600">Manage user stories and view analytics</p>
            </div>
            <div class="flex space-x-3">
                <button @click="exportStories()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Export CSV
                </button>
                <button @click="showCleanupModal = true"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    <i class="fas fa-trash-alt mr-2"></i>
                    Cleanup Expired
                </button>
            </div>
        </div>
    </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Stories</label>
                        <div class="relative">
                            <input type="text"
                                   id="search"
                                   x-model="filters.search"
                                   @input="debounceSearch()"
                                   placeholder="Search by caption, content or user..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <div x-show="filters.search"
                                 @click="filters.search = ''; loadStories()"
                                 class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                                <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Country Filter -->
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <select id="country"
                                x-model="filters.country"
                                @change="loadStories()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Countries</option>
                            <template x-for="country in countries" :key="country.id">
                                <option :value="country.id" x-text="country.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Story Type</label>
                        <select id="type"
                                x-model="filters.type"
                                @change="loadStories()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Types</option>
                            <option value="text">Text</option>
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status"
                                x-model="filters.status"
                                @change="loadStories()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Stories</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>

                    <!-- Privacy Filter -->
                    <!-- <div>
                        <label for="privacy" class="block text-sm font-medium text-gray-700 mb-1">Privacy</label>
                        <select id="privacy"
                                x-model="filters.privacy"
                                @change="loadStories()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Privacy</option>
                            <option value="all_connections">All Connections</option>
                            <option value="close_friends">Close Friends</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div> -->

                </div>

                <!-- Date Range Filter Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date"
                               id="date_from"
                               x-model="filters.date_from"
                               @change="loadStories()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date"
                               id="date_to"
                               x-model="filters.date_to"
                               @change="loadStories()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Quick Date Presets -->
                    {{-- <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quick Presets</label>
                        <div class="flex flex-wrap gap-2">
                         <button @click="setDateRange('today')"
                                    class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
                                Today
                            </button>
                            <button @click="setDateRange('yesterday')"
                                    class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
                                Yesterday
                            </button>
                            <button @click="setDateRange('this_week')"
                                    class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
                                This Week
                            </button>
                            <button @click="setDateRange('this_month')"
                                    class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
                                This Month
                            </button>
                        </div>
                    </div> --}}

                    <!-- Clear Filters -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Actions</label>
                        <button @click="clearFilters()"
                                class="w-full px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm">
                            <i class="fas fa-times mr-1"></i>
                            Clear All Filters
                        </button>
                    </div>

                </div>

                <!-- Quick Stats -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-8 gap-4">
                    <div class="text-center p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-600">Total Stories</p>
                        <p class="text-xl font-bold text-blue-900" x-text="stats.total_stories || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-md">
                        <p class="text-sm text-green-600">Active</p>
                        <p class="text-xl font-bold text-green-900" x-text="stats.active_stories || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-md">
                        <p class="text-sm text-red-600">Expired</p>
                        <p class="text-xl font-bold text-red-900" x-text="stats.expired_stories || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-md">
                        <p class="text-sm text-purple-600">Total Views</p>
                        <p class="text-xl font-bold text-purple-900" x-text="stats.total_views || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-indigo-50 rounded-md">
                        <p class="text-sm text-indigo-600">Today</p>
                        <p class="text-xl font-bold text-indigo-900" x-text="stats.today_stories || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-pink-50 rounded-md">
                        <p class="text-sm text-pink-600">This Week</p>
                        <p class="text-xl font-bold text-pink-900" x-text="stats.this_week_stories || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 rounded-md">
                        <p class="text-sm text-yellow-600">Text Stories</p>
                        <p class="text-xl font-bold text-yellow-900" x-text="stats.type_breakdown?.text || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-orange-50 rounded-md">
                        <p class="text-sm text-orange-600">Media Stories</p>
                        <p class="text-xl font-bold text-orange-900" x-text="(stats.type_breakdown?.image || 0) + (stats.type_breakdown?.video || 0)">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stories Table -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Stories</h3>
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
                    <span class="text-gray-600">Loading stories...</span>
                </div>
            </div>

            <!-- Stories List -->
            <div x-show="!loading">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Story
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Author
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Country
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Privacy
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
                            <template x-for="story in stories.data" :key="story.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox"
                                               :value="story.id"
                                               x-model="selectedStories"
                                               class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="h-12 w-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                                                    <template x-if="story.type === 'image' && story.file_url">
                                                        <img :src="story.file_url" :alt="story.caption" class="h-12 w-12 object-cover">
                                                    </template>
                                                    <template x-if="story.type === 'video' && story.file_url">
                                                        <div class="relative">
                                                            <video class="h-12 w-12 object-cover">
                                                                <source :src="story.file_url">
                                                            </video>
                                                            <div class="absolute inset-0 flex items-center justify-center">
                                                                <i class="fas fa-play text-white text-lg"></i>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template x-if="story.type === 'text'">
                                                        <div class="h-12 w-12 rounded-lg flex items-center justify-center text-white text-xs font-medium"
                                                             :style="`background-color: ${story.background_color || '#000000'}`">
                                                            <i class="fas fa-font"></i>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                {{-- <p class="text-sm font-medium text-gray-900 truncate" x-text="story.caption || story.content || 'No caption'"></p>
                                                <p class="text-xs text-gray-500" x-text="'ID: ' + story.id"></p> --}}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img :src="story.user.profile_url || '/images/default-avatar.png'"
                                                     :alt="story.user.name"
                                                     class="h-10 w-10 rounded-full">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900" x-text="story.user.name"></div>
                                                <div class="text-sm text-gray-500" x-text="story.user.username || story.user.email"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Country Column -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900" x-text="story.user.country ? story.user.country.name : 'N/A'"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="getTypeBadge(story.type)">
                                            <template x-if="story.type === 'text'">
                                                <i class="fas fa-font mr-1"></i>
                                            </template>
                                            <template x-if="story.type === 'image'">
                                                <i class="fas fa-image mr-1"></i>
                                            </template>
                                            <template x-if="story.type === 'video'">
                                                <i class="fas fa-video mr-1"></i>
                                            </template>
                                            <span x-text="story.type.charAt(0).toUpperCase() + story.type.slice(1)"></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="getPrivacyBadge(story.privacy)">
                                            <span x-text="story.privacy.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-4">
                                            <div class="flex items-center">
                                                <i class="fas fa-eye text-purple-400 mr-1"></i>
                                                <span x-text="story.views_count"></span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-reply text-blue-400 mr-1"></i>
                                                <span x-text="story.replies_count"></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                              :class="getStatusBadge(story.is_expired)">
                                            <template x-if="story.is_expired">
                                                <i class="fas fa-clock mr-1"></i>
                                            </template>
                                            <template x-if="!story.is_expired">
                                                <i class="fas fa-play mr-1"></i>
                                            </template>
                                            <span x-text="story.is_expired ? 'Expired' : 'Active'"></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div x-text="story.created_at_human"></div>
                                        <div class="text-xs text-gray-400" x-text="'Expires: ' + formatDate(story.expires_at)"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a :href="'/admin/stories/' + story.id"
                                               class="text-indigo-600 hover:text-indigo-900 transition-colors">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <div class="relative" x-data="{ open: false }">
                                                <button @click="open = !open"
                                                        class="text-gray-400 hover:text-gray-600 transition-colors">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div x-show="open"
                                                     @click.away="open = false"
                                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                                    <div class="py-1">
                                                        <button @click="deleteStory(story.id); open = false"
                                                                class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                            <i class="fas fa-trash mr-2"></i>
                                                            Delete Story
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <tr x-show="stories.data && stories.data.length === 0 && !loading">
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-photo-video text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-lg">No stories found</p>
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
                                                       'bg-primary text-white border-primary' :
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

        <!-- Cleanup Expired Modal -->
        <div x-show="showCleanupModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             @click.self="showCleanupModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-4">Cleanup Expired Stories</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            This will permanently delete all expired stories and their associated files. This action cannot be undone.
                        </p>
                    </div>
                    <div class="flex justify-center space-x-3 mt-4">
                        <button @click="showCleanupModal = false"
                                class="px-4 py-2 bg-gray-300 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button @click="cleanupExpiredStories()"
                                class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                            Cleanup Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function storyManagement() {
        return {
            stories: { data: [], total: 0, current_page: 1, last_page: 1 },
            stats: {},
            pagination: {},
            loading: false,
            selectedStories: [],
            showCleanupModal: false,
            countries: [],
            filters: {
                search: '',
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

            async loadStories(page = 1) {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: page,
                        ...this.filters
                    });

                    const response = await fetch(`/admin/api/stories?${params}`, {
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

                    this.stories = data.stories || data;
                    this.pagination = {
                        current_page: this.stories.current_page || 1,
                        last_page: this.stories.last_page || 1,
                        from: this.stories.from || 0,
                        to: this.stories.to || 0,
                        total: this.stories.total || 0
                    };
                    this.selectedStories = [];
                } catch (error) {
                    console.error('Failed to load stories:', error);
                    this.showError('Failed to load stories: ' + error.message);
                } finally {
                    this.loading = false;
                }
            },

            async loadStats() {
                try {
                    const response = await fetch('/admin/api/stories/stats', {
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
                    this.stats = data;
                } catch (error) {
                    console.error('Failed to load stats:', error);
                    this.stats = {};
                }
            },

            debounceSearch() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.loadStories();
                }, 500);
            },

            changePage(page) {
                if (page >= 1 && page <= this.pagination.last_page) {
                    this.loadStories(page);
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
                    this.selectedStories = [];
                } else {
                    this.selectedStories = this.stories.data.map(story => story.id);
                }
            },

            isAllSelected() {
                return this.stories.data && this.stories.data.length > 0 && this.selectedStories.length === this.stories.data.length;
            },

            clearFilters() {
                this.filters = {
                    search: '',
                    type: '',
                    status: '',
                    date_from: '',
                    date_to: ''
                };
                this.loadStories();
            },

            setDateRange(preset) {
                const today = new Date();
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);

                const startOfWeek = new Date(today);
                const day = startOfWeek.getDay();
                const diff = startOfWeek.getDate() - day + (day === 0 ? -6 : 1);
                startOfWeek.setDate(diff);

                const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

                switch (preset) {
                    case 'today':
                        this.filters.date_from = this.formatDateForInput(today);
                        this.filters.date_to = this.formatDateForInput(today);
                        break;
                    case 'yesterday':
                        this.filters.date_from = this.formatDateForInput(yesterday);
                        this.filters.date_to = this.formatDateForInput(yesterday);
                        break;
                    case 'this_week':
                        this.filters.date_from = this.formatDateForInput(startOfWeek);
                        this.filters.date_to = this.formatDateForInput(today);
                        break;
                    case 'this_month':
                        this.filters.date_from = this.formatDateForInput(startOfMonth);
                        this.filters.date_to = this.formatDateForInput(today);
                        break;
                }
                this.loadStories();
            },

            formatDateForInput(date) {
                return date.toISOString().split('T')[0];
            },

            getStatusBadge(isExpired) {
                return isExpired ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
            },

            getTypeBadge(type) {
                const badges = {
                    'text': 'bg-gray-100 text-gray-800',
                    'image': 'bg-blue-100 text-blue-800',
                    'video': 'bg-purple-100 text-purple-800'
                };
                return badges[type] || 'bg-gray-100 text-gray-800';
            },

            getPrivacyBadge(privacy) {
                const badges = {
                    'all_connections': 'bg-green-100 text-green-800',
                    'close_friends': 'bg-yellow-100 text-yellow-800',
                    'custom': 'bg-blue-100 text-blue-800'
                };
                return badges[privacy] || 'bg-gray-100 text-gray-800';
            },

            async deleteStory(storyId) {
                if (!confirm('Are you sure you want to delete this story?')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/stories/${storyId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message);
                        this.loadStories(this.pagination.current_page);
                        this.loadStats();
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    console.error('Error deleting story:', error);
                    this.showError('Failed to delete story');
                }
            },

            async bulkDeleteStories() {
                if (this.selectedStories.length === 0) {
                    return;
                }

                if (!confirm(`Are you sure you want to delete ${this.selectedStories.length} selected stories?`)) {
                    return;
                }

                try {
                    const response = await fetch('/admin/api/stories/bulk-delete', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            story_ids: this.selectedStories
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message);
                        this.loadStories(this.pagination.current_page);
                        this.loadStats();
                        this.selectedStories = [];
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    console.error('Error bulk deleting stories:', error);
                    this.showError('Failed to delete stories');
                }
            },

            async cleanupExpiredStories() {
                this.showCleanupModal = false;

                try {
                    // Show loading state
                    const loadingToast = this.showLoadingToast('Cleaning up expired stories...');

                    const response = await fetch('/admin/api/stories/cleanup-expired', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    });

                    // Remove loading toast
                    if (loadingToast) {
                        loadingToast.remove();
                    }

                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Server response:', response.status, errorText);
                        throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();
                    console.log('Cleanup response:', data);

                    if (data.success) {
                        this.showSuccess(data.message || 'Expired stories cleaned up successfully');
                        // Reload data to reflect changes
                        await this.loadStories(this.pagination.current_page);
                        await this.loadStats();
                    } else {
                        this.showError(data.message || 'Failed to cleanup expired stories');
                    }
                } catch (error) {
                    console.error('Error cleaning up expired stories:', error);
                    this.showError('Failed to cleanup expired stories: ' + error.message);
                }
            },

            async exportStories() {
                try {
                    const response = await fetch('/admin/stories/export', {
                        method: 'GET',
                    });

                    if (response.ok) {
                        const blob = await response.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `stories_export_${new Date().toISOString().slice(0, 10)}.csv`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        this.showSuccess('Stories exported successfully');
                    } else {
                        this.showError('Failed to export stories');
                    }
                } catch (error) {
                    console.error('Error exporting stories:', error);
                    this.showError('Failed to export stories');
                }
            },

            formatDate(dateString) {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            showLoadingToast(message) {
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 border border-blue-600';
                toast.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        ${message}
                    </div>
                `;
                document.body.appendChild(toast);
                return toast;
            },

            showSuccess(message) {
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 border border-green-600';
                toast.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        ${message}
                    </div>
                `;
                document.body.appendChild(toast);
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 5000);
            },

            showError(message) {
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 border border-red-600';
                toast.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        ${message}
                    </div>
                `;
                document.body.appendChild(toast);
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 8000);
            },

            getStatusBadge(isExpired) {
                return isExpired ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
            },

            getTypeBadge(type) {
                const badges = {
                    'text': 'bg-blue-100 text-blue-800',
                    'image': 'bg-green-100 text-green-800',
                    'video': 'bg-purple-100 text-purple-800'
                };
                return badges[type] || 'bg-gray-100 text-gray-800';
            },

            getPrivacyBadge(privacy) {
                const badges = {
                    'all_connections': 'bg-blue-100 text-blue-800',
                    'close_friends': 'bg-yellow-100 text-yellow-800',
                    'custom': 'bg-purple-100 text-purple-800'
                };
                return badges[privacy] || 'bg-gray-100 text-gray-800';
            }
        }
    }
</script>
@endpush
