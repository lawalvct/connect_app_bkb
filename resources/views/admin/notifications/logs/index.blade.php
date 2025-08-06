@extends('admin.layouts.app')

@section('title', 'Notification Logs')
@section('page-title', 'Notification Logs')

@section('content')
<div x-data="notificationLogs()" x-init="init()">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Notification Logs</h3>
                    <p class="text-sm text-gray-500 mt-1">Track all sent notifications and their delivery status</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="exportLogs()"
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-download mr-2"></i>
                        Export
                    </button>
                    <button @click="clearOldLogs()"
                            class="px-4 py-2 text-red-600 border border-red-300 rounded-lg hover:bg-red-50">
                        <i class="fas fa-trash mr-2"></i>
                        Clear Old Logs
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Search -->
                <div class="relative">
                    <input type="text"
                           x-model="filters.search"
                           @input="debounceSearch()"
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                           placeholder="Search logs...">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>

                <!-- Status Filter -->
                <select x-model="filters.status"
                        @change="loadLogs()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">All Status</option>
                    <option value="sent">Sent</option>
                    <option value="delivered">Delivered</option>
                    <option value="failed">Failed</option>
                    <option value="pending">Pending</option>
                </select>

                <!-- Platform Filter -->
                <select x-model="filters.platform"
                        @change="loadLogs()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">All Platforms</option>
                    <option value="android">Android</option>
                    <option value="ios">iOS</option>
                    <option value="web">Web</option>
                </select>

                <!-- Date From -->
                <input type="date"
                       x-model="filters.date_from"
                       @change="loadLogs()"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">

                <!-- Date To -->
                <input type="date"
                       x-model="filters.date_to"
                       @change="loadLogs()"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <!-- Quick Filter Buttons -->
            <div class="flex flex-wrap gap-2 mt-4">
                <button @click="setQuickFilter('today')"
                        :class="quickFilter === 'today' ? 'bg-primary text-white' : 'bg-white text-gray-700'"
                        class="px-3 py-1 text-sm border border-gray-300 rounded-full hover:bg-primary hover:text-white">
                    Today
                </button>
                <button @click="setQuickFilter('yesterday')"
                        :class="quickFilter === 'yesterday' ? 'bg-primary text-white' : 'bg-white text-gray-700'"
                        class="px-3 py-1 text-sm border border-gray-300 rounded-full hover:bg-primary hover:text-white">
                    Yesterday
                </button>
                <button @click="setQuickFilter('week')"
                        :class="quickFilter === 'week' ? 'bg-primary text-white' : 'bg-white text-gray-700'"
                        class="px-3 py-1 text-sm border border-gray-300 rounded-full hover:bg-primary hover:text-white">
                    This Week
                </button>
                <button @click="setQuickFilter('month')"
                        :class="quickFilter === 'month' ? 'bg-primary text-white' : 'bg-white text-gray-700'"
                        class="px-3 py-1 text-sm border border-gray-300 rounded-full hover:bg-primary hover:text-white">
                    This Month
                </button>
                <button @click="setQuickFilter('failed')"
                        :class="quickFilter === 'failed' ? 'bg-red-600 text-white' : 'bg-white text-gray-700'"
                        class="px-3 py-1 text-sm border border-gray-300 rounded-full hover:bg-red-600 hover:text-white">
                    Failed Only
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-paper-plane text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-600">Total Sent</p>
                            <p class="text-2xl font-semibold text-blue-900" x-text="stats.total_sent"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-600">Delivered</p>
                            <p class="text-2xl font-semibold text-green-900" x-text="stats.delivered"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-red-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-times-circle text-2xl text-red-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-600">Failed</p>
                            <p class="text-2xl font-semibold text-red-900" x-text="stats.failed"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-2xl text-yellow-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-600">Success Rate</p>
                            <p class="text-2xl font-semibold text-yellow-900" x-text="stats.success_rate + '%'"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Message
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Platform
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sent At
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-gray-50">
                            <!-- User -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm font-medium">
                                        <span x-text="log.user ? log.user.name.charAt(0).toUpperCase() : 'U'"></span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900" x-text="log.user ? log.user.name : 'Unknown User'"></p>
                                        <p class="text-sm text-gray-500" x-text="log.user ? log.user.email : 'N/A'"></p>
                                    </div>
                                </div>
                            </td>

                            <!-- Message -->
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <p class="text-sm font-medium text-gray-900" x-text="log.title"></p>
                                    <p class="text-sm text-gray-500 truncate" x-text="log.body"></p>
                                </div>
                            </td>

                            <!-- Platform -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i :class="getPlatformIcon(log.platform)" class="mr-2"></i>
                                    <span class="text-sm text-gray-900 capitalize" x-text="log.platform"></span>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getStatusBadgeClass(log.status)"
                                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                    <i :class="getStatusIcon(log.status)" class="mr-1"></i>
                                    <span class="capitalize" x-text="log.status"></span>
                                </span>
                            </td>

                            <!-- Sent At -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>
                                    <p x-text="formatDateTime(log.created_at)"></p>
                                    <p class="text-xs text-gray-400" x-text="formatRelativeTime(log.created_at)"></p>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex items-center space-x-2">
                                    <button @click="viewLogDetails(log)"
                                            class="text-blue-600 hover:text-blue-800"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button x-show="log.status === 'failed'"
                                            @click="retryNotification(log)"
                                            class="text-yellow-600 hover:text-yellow-800"
                                            title="Retry">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <button @click="deleteLog(log)"
                                            class="text-red-600 hover:text-red-800"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="logs.length === 0 && !loading">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No logs found</h3>
                            <p class="text-gray-500">No notification logs match your current filters.</p>
                        </td>
                    </tr>

                    <!-- Loading State -->
                    <tr x-show="loading">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
                            <p class="text-gray-500">Loading notification logs...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.total > pagination.per_page" class="p-6 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span>
                    of <span x-text="pagination.total"></span> logs
                </div>
                <div class="flex space-x-2">
                    <button @click="loadLogs(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Previous
                    </button>
                    <span class="px-3 py-1 text-sm text-gray-700">
                        Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
                    </span>
                    <button @click="loadLogs(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Details Modal -->
    <div x-show="showDetailsModal"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showDetailsModal = false">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Notification Details</h3>
                    <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Log Details -->
                <div x-show="selectedLog" class="space-y-6">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <span :class="getStatusBadgeClass(selectedLog.status)"
                                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1">
                                <span class="capitalize" x-text="selectedLog.status"></span>
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Platform</label>
                            <p class="mt-1 text-sm text-gray-900 capitalize" x-text="selectedLog.platform"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sent At</label>
                            <p class="mt-1 text-sm text-gray-900" x-text="formatDateTime(selectedLog.created_at)"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">FCM Message ID</label>
                            <p class="mt-1 text-sm text-gray-900" x-text="selectedLog.fcm_message_id || 'N/A'"></p>
                        </div>
                    </div>

                    <!-- Message Content -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Message Title</label>
                        <p class="mt-1 text-sm text-gray-900" x-text="selectedLog.title"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Message Body</label>
                        <p class="mt-1 text-sm text-gray-900" x-text="selectedLog.body"></p>
                    </div>

                    <!-- Additional Data -->
                    <div x-show="selectedLog.data">
                        <label class="block text-sm font-medium text-gray-700">Additional Data</label>
                        <pre class="mt-1 text-xs bg-gray-100 p-3 rounded-lg overflow-x-auto" x-text="JSON.stringify(selectedLog.data, null, 2)"></pre>
                    </div>

                    <!-- Error Details -->
                    <div x-show="selectedLog.error_message">
                        <label class="block text-sm font-medium text-red-700">Error Message</label>
                        <div class="mt-1 text-sm text-red-900 bg-red-50 p-3 rounded-lg" x-text="selectedLog.error_message"></div>
                    </div>

                    <!-- Response Details -->
                    <div x-show="selectedLog.response_data">
                        <label class="block text-sm font-medium text-gray-700">Response Data</label>
                        <pre class="mt-1 text-xs bg-gray-100 p-3 rounded-lg overflow-x-auto" x-text="JSON.stringify(selectedLog.response_data, null, 2)"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div x-show="showMessage"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed top-4 right-4 z-50 max-w-sm">
        <div :class="messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'"
             class="border px-4 py-3 rounded-lg shadow-lg">
            <div class="flex items-center">
                <i :class="messageType === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'" class="mr-2"></i>
                <span x-text="message"></span>
            </div>
        </div>
    </div>
</div>

<script>
function notificationLogs() {
    return {
        logs: [],
        loading: false,
        filters: {
            search: '',
            status: '',
            platform: '',
            date_from: '',
            date_to: ''
        },
        quickFilter: '',
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 50,
            total: 0,
            from: 0,
            to: 0
        },
        stats: {
            total_sent: 0,
            delivered: 0,
            failed: 0,
            success_rate: 0
        },
        showDetailsModal: false,
        selectedLog: null,
        showMessage: false,
        message: '',
        messageType: 'success',
        searchTimeout: null,

        init() {
            this.loadLogs();
            this.loadStats();
        },

        async loadLogs(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    search: this.filters.search,
                    status: this.filters.status,
                    platform: this.filters.platform,
                    date_from: this.filters.date_from,
                    date_to: this.filters.date_to
                });

                const response = await fetch(`/admin/api/notifications/logs?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.logs = data.logs.data;
                    this.pagination = {
                        current_page: data.logs.current_page,
                        last_page: data.logs.last_page,
                        per_page: data.logs.per_page,
                        total: data.logs.total,
                        from: data.logs.from,
                        to: data.logs.to
                    };
                }
            } catch (error) {
                console.error('Failed to load logs:', error);
                this.showError('Failed to load notification logs');
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const params = new URLSearchParams({
                    date_from: this.filters.date_from,
                    date_to: this.filters.date_to
                });

                const response = await fetch(`/admin/api/notifications/logs/stats?${params}`);
                const data = await response.json();
                if (data.success) {
                    this.stats = data.stats;
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        debounceSearch() {
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            this.searchTimeout = setTimeout(() => {
                this.loadLogs();
            }, 500);
        },

        setQuickFilter(filter) {
            this.quickFilter = filter;
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            switch (filter) {
                case 'today':
                    this.filters.date_from = today.toISOString().split('T')[0];
                    this.filters.date_to = today.toISOString().split('T')[0];
                    this.filters.status = '';
                    break;
                case 'yesterday':
                    this.filters.date_from = yesterday.toISOString().split('T')[0];
                    this.filters.date_to = yesterday.toISOString().split('T')[0];
                    this.filters.status = '';
                    break;
                case 'week':
                    const weekAgo = new Date(today);
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    this.filters.date_from = weekAgo.toISOString().split('T')[0];
                    this.filters.date_to = today.toISOString().split('T')[0];
                    this.filters.status = '';
                    break;
                case 'month':
                    const monthAgo = new Date(today);
                    monthAgo.setMonth(monthAgo.getMonth() - 1);
                    this.filters.date_from = monthAgo.toISOString().split('T')[0];
                    this.filters.date_to = today.toISOString().split('T')[0];
                    this.filters.status = '';
                    break;
                case 'failed':
                    this.filters.status = 'failed';
                    this.filters.date_from = '';
                    this.filters.date_to = '';
                    break;
            }
            this.loadLogs();
            this.loadStats();
        },

        viewLogDetails(log) {
            this.selectedLog = log;
            this.showDetailsModal = true;
        },

        async retryNotification(log) {
            if (!confirm('Are you sure you want to retry this notification?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/api/notifications/logs/${log.id}/retry`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('Notification queued for retry');
                    this.loadLogs();
                } else {
                    this.showError(data.message || 'Failed to retry notification');
                }
            } catch (error) {
                console.error('Retry notification error:', error);
                this.showError('Failed to retry notification');
            }
        },

        async deleteLog(log) {
            if (!confirm('Are you sure you want to delete this log entry?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/api/notifications/logs/${log.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('Log entry deleted successfully');
                    this.loadLogs();
                    this.loadStats();
                } else {
                    this.showError(data.message || 'Failed to delete log entry');
                }
            } catch (error) {
                console.error('Delete log error:', error);
                this.showError('Failed to delete log entry');
            }
        },

        async exportLogs() {
            try {
                const params = new URLSearchParams({
                    search: this.filters.search,
                    status: this.filters.status,
                    platform: this.filters.platform,
                    date_from: this.filters.date_from,
                    date_to: this.filters.date_to,
                    export: 'csv'
                });

                window.open(`/admin/api/notifications/logs/export?${params}`, '_blank');
                this.showSuccess('Export started. Download will begin shortly.');
            } catch (error) {
                console.error('Export logs error:', error);
                this.showError('Failed to export logs');
            }
        },

        async clearOldLogs() {
            if (!confirm('Are you sure you want to delete logs older than 30 days? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('/admin/api/notifications/logs/cleanup', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(`Deleted ${data.deleted_count} old log entries`);
                    this.loadLogs();
                    this.loadStats();
                } else {
                    this.showError(data.message || 'Failed to clear old logs');
                }
            } catch (error) {
                console.error('Clear logs error:', error);
                this.showError('Failed to clear old logs');
            }
        },

        getPlatformIcon(platform) {
            const icons = {
                android: 'fab fa-android text-green-600',
                ios: 'fab fa-apple text-gray-800',
                web: 'fas fa-globe text-blue-600'
            };
            return icons[platform] || 'fas fa-mobile-alt text-gray-600';
        },

        getStatusIcon(status) {
            const icons = {
                sent: 'fas fa-check',
                delivered: 'fas fa-check-double',
                failed: 'fas fa-times',
                pending: 'fas fa-clock'
            };
            return icons[status] || 'fas fa-question';
        },

        getStatusBadgeClass(status) {
            const classes = {
                sent: 'bg-blue-100 text-blue-800',
                delivered: 'bg-green-100 text-green-800',
                failed: 'bg-red-100 text-red-800',
                pending: 'bg-yellow-100 text-yellow-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString();
        },

        formatRelativeTime(dateString) {
            const now = new Date();
            const date = new Date(dateString);
            const diffInSeconds = Math.floor((now - date) / 1000);

            if (diffInSeconds < 60) return `${diffInSeconds}s ago`;
            if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
            if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
            return `${Math.floor(diffInSeconds / 86400)}d ago`;
        },

        showSuccess(msg) {
            this.message = msg;
            this.messageType = 'success';
            this.showMessage = true;
            setTimeout(() => {
                this.showMessage = false;
            }, 5000);
        },

        showError(msg) {
            this.message = msg;
            this.messageType = 'error';
            this.showMessage = true;
            setTimeout(() => {
                this.showMessage = false;
            }, 5000);
        }
    }
}
</script>
@endsection
