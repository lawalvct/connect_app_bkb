@extends('admin.layouts.app')

@section('title', 'Stream Management')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Stream Management</h1>
            <p class="text-gray-600">Manage live streams, broadcasts and monitor viewers</p>
        </div>
        <div class="flex space-x-3">
            <button @click="exportStreams()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-download mr-2"></i>
                Export CSV
            </button>
            <a href="{{ route('admin.streams.create') }}"
               class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Create Stream
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="streamManagement()" x-init="loadStreams(); loadStats()">

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Total Streams -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="fas fa-video text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Streams</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_streams || '0'"></p>
                    </div>
                </div>
            </div>

            <!-- Live Streams -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-lg">
                        <i class="fas fa-broadcast-tower text-2xl text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Live Now</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.live_streams || '0'"></p>
                    </div>
                </div>
            </div>

            <!-- Upcoming Streams -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-2xl text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Upcoming</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.upcoming_streams || '0'"></p>
                    </div>
                </div>
            </div>

            <!-- Active Viewers -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-users text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Viewers</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.active_viewers || '0'"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interaction Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Total Likes -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-thumbs-up text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Likes</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_likes || '0'"></p>
                        <p class="text-xs text-gray-500" x-text="'Avg: ' + (stats.avg_likes_per_stream || '0') + ' per stream'"></p>
                    </div>
                </div>
            </div>

            <!-- Total Dislikes -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-lg">
                        <i class="fas fa-thumbs-down text-2xl text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Dislikes</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_dislikes || '0'"></p>
                        <p class="text-xs text-gray-500" x-text="'Avg: ' + (stats.avg_dislikes_per_stream || '0') + ' per stream'"></p>
                    </div>
                </div>
            </div>

            <!-- Total Shares -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="fas fa-share text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Shares</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_shares || '0'"></p>
                        <p class="text-xs text-gray-500" x-text="'Avg: ' + (stats.avg_shares_per_stream || '0') + ' per stream'"></p>
                    </div>
                </div>
            </div>

            <!-- Total Interactions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-heart text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Interactions</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_interactions || '0'"></p>
                        <p class="text-xs text-gray-500">All engagement combined</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <div class="relative">
                            <input type="text"
                                   x-model="filters.search"
                                   @input="debounceSearch()"
                                   placeholder="Search streams..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select x-model="filters.status" @change="loadStreams()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Status</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="live">Live</option>
                            <option value="ended">Ended</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stream Type</label>
                        <select x-model="filters.stream_type" @change="loadStreams()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Types</option>
                            <option value="immediate">Immediate</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <input type="text"
                               x-model="filters.date_range"
                               @change="loadStreams()"
                               placeholder="Select date range"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="mt-4 flex justify-between items-center">
                    <button @click="clearFilters()"
                            class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-times-circle mr-1"></i>
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Streams Table -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Streams</h3>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                <p class="mt-2 text-gray-600">Loading streams...</p>
            </div>

            <!-- Streams List -->
            <div x-show="!loading">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stream</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creator</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Viewers</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Interactions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="stream in streams.data" :key="stream.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <img class="h-12 w-12 rounded-lg object-cover"
                                                     :src="stream.banner_image_url || '/images/placeholder-stream.jpg'"
                                                     :alt="stream.title">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900" x-text="stream.title"></div>
                                                <div class="text-sm text-gray-500" x-text="stream.description ? stream.description.substring(0, 50) + '...' : 'No description'"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900" x-text="stream.user ? stream.user.name : 'Admin'"></div>
                                        <div class="text-sm text-gray-500" x-text="stream.user ? stream.user.email : 'admin@system.com'"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                              :class="getStatusBadge(stream.status)"
                                              x-text="stream.status.charAt(0).toUpperCase() + stream.status.slice(1)"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                              :class="getTypeBadge(stream.stream_type)"
                                              x-text="stream.stream_type ? stream.stream_type.charAt(0).toUpperCase() + stream.stream_type.slice(1) : 'Immediate'"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center">
                                            <i class="fas fa-users text-gray-400 mr-1"></i>
                                            <span x-text="stream.current_viewers || 0"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="space-y-1">
                                            <div class="flex items-center text-xs">
                                                <i class="fas fa-thumbs-up text-green-500 mr-1"></i>
                                                <span x-text="stream.likes_count || 0" class="text-green-600 font-medium"></span>
                                                <i class="fas fa-thumbs-down text-red-500 ml-3 mr-1"></i>
                                                <span x-text="stream.dislikes_count || 0" class="text-red-600 font-medium"></span>
                                            </div>
                                            <div class="flex items-center text-xs">
                                                <i class="fas fa-share text-blue-500 mr-1"></i>
                                                <span x-text="stream.shares_count || 0" class="text-blue-600 font-medium"></span>
                                                <span class="text-gray-400 ml-2" x-text="'(' + (stream.interaction_stats?.total_interactions || 0) + ' total)'"></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center">
                                            <span x-text="stream.currency || 'NGN'"></span>
                                            <span class="ml-1" x-text="stream.price || '0.00'"></span>
                                            <span x-show="stream.free_minutes > 0" class="text-xs text-gray-500 ml-1">
                                                (<span x-text="stream.free_minutes"></span>m free)
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(stream.created_at)"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a :href="`/admin/streams/${stream.id}`"
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a :href="`/admin/streams/${stream.id}/edit`"
                                               class="text-indigo-600 hover:text-indigo-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button x-show="stream.status === 'upcoming'"
                                                    @click="startStream(stream.id)"
                                                    class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <button x-show="stream.status === 'live'"
                                                    @click="endStream(stream.id)"
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-stop"></i>
                                            </button>
                                            <button @click="deleteStream(stream.id)"
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div x-show="streams.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span x-text="((streams.current_page - 1) * streams.per_page) + 1"></span>
                            to <span x-text="Math.min(streams.current_page * streams.per_page, streams.total)"></span>
                            of <span x-text="streams.total"></span> results
                        </div>
                        <div class="flex space-x-2">
                            <button @click="changePage(streams.current_page - 1)"
                                    :disabled="streams.current_page === 1"
                                    class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Previous
                            </button>
                            <template x-for="page in getPageNumbers()" :key="page">
                                <button @click="changePage(page)"
                                        :class="page === streams.current_page ? 'bg-primary text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
                                        class="px-3 py-1 text-sm border rounded-md"
                                        x-text="page"></button>
                            </template>
                            <button @click="changePage(streams.current_page + 1)"
                                    :disabled="streams.current_page === streams.last_page"
                                    class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
    function streamManagement() {
        return {
            streams: { data: [], total: 0, current_page: 1, last_page: 1 },
            stats: {},
            loading: false,
            filters: {
                search: '',
                status: '',
                stream_type: '',
                date_range: ''
            },
            searchTimeout: null,

            async loadStreams(page = 1) {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: page,
                        per_page: 15,
                        ...this.filters
                    });

                    const response = await fetch(`/admin/api/streams?${params}`);
                    const data = await response.json();

                    if (data.success) {
                        this.streams = {
                            data: data.data,
                            current_page: data.pagination.current_page,
                            last_page: data.pagination.last_page,
                            per_page: data.pagination.per_page,
                            total: data.pagination.total
                        };
                    }
                } catch (error) {
                    console.error('Error loading streams:', error);
                } finally {
                    this.loading = false;
                }
            },

            async loadStats() {
                try {
                    const response = await fetch('/admin/api/streams/stats');
                    const data = await response.json();

                    if (data.success) {
                        this.stats = data.data;
                    }
                } catch (error) {
                    console.error('Error loading stats:', error);
                }
            },

            debounceSearch() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.loadStreams();
                }, 500);
            },

            changePage(page) {
                if (page >= 1 && page <= this.streams.last_page) {
                    this.loadStreams(page);
                }
            },

            getPageNumbers() {
                const pages = [];
                const current = this.streams.current_page;
                const last = this.streams.last_page;

                for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                    pages.push(i);
                }

                return pages;
            },

            clearFilters() {
                this.filters = {
                    search: '',
                    status: '',
                    stream_type: '',
                    date_range: ''
                };
                this.loadStreams();
            },

            getStatusBadge(status) {
                const badges = {
                    'upcoming': 'bg-yellow-100 text-yellow-800',
                    'live': 'bg-red-100 text-red-800',
                    'ended': 'bg-gray-100 text-gray-800'
                };
                return badges[status] || 'bg-gray-100 text-gray-800';
            },

            getTypeBadge(type) {
                const badges = {
                    'immediate': 'bg-blue-100 text-blue-800',
                    'scheduled': 'bg-purple-100 text-purple-800'
                };
                return badges[type] || 'bg-gray-100 text-gray-800';
            },

            async startStream(streamId) {
                if (!confirm('Are you sure you want to start this stream?')) return;

                try {
                    const response = await fetch(`/admin/api/streams/${streamId}/start`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess('Stream started successfully');
                        this.loadStreams();
                        this.loadStats();
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    this.showError('Failed to start stream');
                    console.error('Error starting stream:', error);
                }
            },

            async endStream(streamId) {
                if (!confirm('Are you sure you want to end this stream?')) return;

                try {
                    const response = await fetch(`/admin/api/streams/${streamId}/end`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess('Stream ended successfully');
                        this.loadStreams();
                        this.loadStats();
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    this.showError('Failed to end stream');
                    console.error('Error ending stream:', error);
                }
            },

            async deleteStream(streamId) {
                if (!confirm('Are you sure you want to delete this stream? This action cannot be undone.')) return;

                try {
                    const response = await fetch(`/admin/streams/${streamId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess('Stream deleted successfully');
                        this.loadStreams();
                        this.loadStats();
                    } else {
                        this.showError(data.message);
                    }
                } catch (error) {
                    this.showError('Failed to delete stream');
                    console.error('Error deleting stream:', error);
                }
            },

            async exportStreams() {
                try {
                    const params = new URLSearchParams(this.filters);
                    window.open(`/admin/streams/export?${params}`, '_blank');
                } catch (error) {
                    this.showError('Failed to export streams');
                    console.error('Error exporting streams:', error);
                }
            },

            formatDate(dateString) {
                return new Intl.DateTimeFormat('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }).format(new Date(dateString));
            },

            showSuccess(message) {
                // You can implement a toast notification system here
                alert(message);
            },

            showError(message) {
                alert(message);
            }
        }
    }
</script>
@endpush
