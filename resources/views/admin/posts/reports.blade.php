@extends('admin.layouts.app')

@section('title', 'Post Reports Management')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Post Reports</h1>
            <p class="text-gray-600">Manage and review all reported posts</p>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="reportManagement()" x-init="loadReports(); loadCountries()">
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <div class="px-2 py-2 border-b border-gray-200 flex justify-between items-center cursor-pointer" @click="filtersVisible = !filtersVisible">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-filter text-gray-500"></i>
                        <h3 class="text-lg font-medium text-gray-700">Filters</h3>
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full" x-show="Object.values(filters).some(val => val !== '')">Filters Applied</span>
                    </div>
                    <div>
                        <i class="fas" :class="filtersVisible ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                </div>
                <div class="p-4" x-show="filtersVisible" x-transition>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <div class="relative">
                                <input type="text" id="search" x-model="filters.search" @input.debounce.500ms="loadReports()" placeholder="Search by reason, description, reporter..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <div x-show="filters.search" @click="filters.search = ''; loadReports()" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                                    <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" x-model="filters.status" @change="loadReports()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="under_review">Under Review</option>
                                <option value="dismissed">Dismissed</option>
                                <option value="action_taken">Action Taken</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                        <!-- Country Filter -->
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                            <select id="country" x-model="filters.country" @change="loadReports()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                                <option value="">All Countries</option>
                                <template x-for="country in countries" :key="country.id">
                                    <option :value="country.id" x-text="country.name"></option>
                                </template>
                            </select>
                        </div>
                        <!-- Date Range Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reported Date Range</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" x-model="filters.date_from" @change="loadReports()" placeholder="From" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm">
                                <input type="date" x-model="filters.date_to" @change="loadReports()" placeholder="To" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm">
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 flex justify-end">
                        <button @click="clearFilters()" class="text-gray-600 hover:text-gray-800 text-xs flex items-center"><i class="fas fa-times-circle mr-1"></i>Clear Filters</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Reports Table -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Reports</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reporter</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reported At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(report, idx) in reports" :key="report.id">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="idx + 1"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a :href="'/admin/posts/' + report.post_id" class="text-blue-600 hover:underline" x-text="'Post #' + report.post_id"></a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="report.reason_text"></td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="report.description"></td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="report.reporter_name"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusBadge(report.status)">
                                            <span x-text="report.status_text"></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="report.created_at"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a :href="'/admin/posts/' + report.post_id" class="text-indigo-600 hover:text-indigo-900">View Post</a>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="reports.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-flag text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-lg">No reports found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function reportManagement() {
    return {
        reports: [],
        countries: [],
        filters: {
            search: '',
            status: '',
            country: '',
            date_from: '',
            date_to: ''
        },
        filtersVisible: true,
        async loadCountries() {
            try {
                const response = await fetch('/admin/api/countries', { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                this.countries = data.countries || [];
            } catch (e) {
                this.countries = [];
            }
        },
        async loadReports() {
            try {
                let url = '/admin/api/post-reports?';
                const params = [];
                if (this.filters.status) params.push('status=' + encodeURIComponent(this.filters.status));
                if (this.filters.search) params.push('search=' + encodeURIComponent(this.filters.search));
                if (this.filters.country) params.push('country=' + encodeURIComponent(this.filters.country));
                if (this.filters.date_from) params.push('date_from=' + encodeURIComponent(this.filters.date_from));
                if (this.filters.date_to) params.push('date_to=' + encodeURIComponent(this.filters.date_to));
                url += params.join('&');
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                this.reports = data.reports || [];
            } catch (e) {
                this.reports = [];
            }
        },
        clearFilters() {
            this.filters = { search: '', status: '', country: '', date_from: '', date_to: '' };
            this.loadReports();
        },
        getStatusBadge(status) {
            const badges = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'under_review': 'bg-blue-100 text-blue-800',
                'dismissed': 'bg-gray-100 text-gray-800',
                'action_taken': 'bg-red-100 text-red-800',
                'resolved': 'bg-green-100 text-green-800'
            };
            return badges[status] || 'bg-gray-100 text-gray-800';
        }
    }
}
</script>
@endpush
