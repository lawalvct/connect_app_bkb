@extends('admin.layouts.app')

@section('title', 'Countries Management')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Countries Management</h1>
            <p class="text-gray-600">Manage countries and their properties</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.countries.create') }}"
               class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Add Country
            </a>
            <button type="button"
                    onclick="exportCountries()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-download mr-2"></i>
                Export
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="countryManager()" x-init="loadCountries()">

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <!-- First Row - Main Filters -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Countries</label>
                        <div class="relative">
                            <input type="text"
                                   id="search"
                                   x-model="filters.search"
                                   @input="debounceSearch()"
                                   placeholder="Search by name, code or capital..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <div x-show="filters.search"
                                 @click="filters.search = ''; loadCountries()"
                                 class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                                <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Region Filter -->
                    <div>
                        <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                        <select id="region"
                                x-model="filters.region"
                                @change="loadCountries()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Regions</option>
                            <option value="Africa">Africa</option>
                            <option value="Americas">Americas</option>
                            <option value="Asia">Asia</option>
                            <option value="Europe">Europe</option>
                            <option value="Oceania">Oceania</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status"
                                x-model="filters.status"
                                @change="loadCountries()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Sort By -->
                    <div>
                        <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select id="sort_by"
                                x-model="filters.sort_by"
                                @change="loadCountries()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="name">Name</option>
                            <option value="users_count">User Count</option>
                            <option value="created_at">Created Date</option>
                            <option value="code">Country Code</option>
                        </select>
                    </div>

                </div>

                <!-- Quick Stats -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div class="text-center p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-600">Total Countries</p>
                        <p class="text-xl font-bold text-blue-900" x-text="stats.total || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-md">
                        <p class="text-sm text-green-600">Active</p>
                        <p class="text-xl font-bold text-green-900" x-text="stats.active || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-md">
                        <p class="text-sm text-red-600">Inactive</p>
                        <p class="text-xl font-bold text-red-900" x-text="(stats.total - stats.active) || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-md">
                        <p class="text-sm text-purple-600">With Users</p>
                        <p class="text-xl font-bold text-purple-900" x-text="stats.with_users || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 rounded-md">
                        <p class="text-sm text-yellow-600">Regions</p>
                        <p class="text-xl font-bold text-yellow-900" x-text="stats.regions || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-indigo-50 rounded-md">
                        <p class="text-sm text-indigo-600">Total Users</p>
                        <p class="text-xl font-bold text-indigo-900" x-text="stats.total_users || '0'">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Countries Table -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Countries</h3>
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
                    <span class="text-gray-600">Loading countries...</span>
                </div>
            </div>

            <!-- Countries List -->
            <div x-show="!loading">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Country
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Code
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Region
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Users
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Currency
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="country in countries" :key="country.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox"
                                               :value="country.id"
                                               x-model="selectedCountries"
                                               class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                <img :src="`https://flagcdn.com/w40/${country.code.toLowerCase()}.png`"
                                                     :alt="country.name"
                                                     class="h-8 w-12 object-cover rounded shadow-sm"
                                                     onerror="this.style.display='none'">
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <p class="text-sm font-medium text-gray-900" x-text="country.name"></p>
                                                    <span x-text="country.emoji" class="text-lg"></span>
                                                </div>
                                                <div class="text-sm text-gray-500" x-text="country.capital"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <span x-text="country.code" class="font-mono bg-gray-100 px-2 py-1 rounded text-xs"></span>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            +<span x-text="country.phone_code"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900" x-text="country.region"></div>
                                        <div class="text-sm text-gray-500" x-text="country.subregion"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <span x-text="country.users_count || 0"></span> users
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <span x-text="country.currency_symbol"></span>
                                            <span x-text="country.currency_code"></span>
                                        </div>
                                        <div class="text-sm text-gray-500" x-text="country.currency"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex gap-1">
                                            <span x-show="country.active"
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                            <span x-show="!country.active"
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Inactive
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a :href="`/admin/countries/${country.id}`"
                                               class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50"
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a :href="`/admin/countries/${country.id}/edit`"
                                               class="text-indigo-600 hover:text-indigo-900 p-1 rounded hover:bg-indigo-50"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button @click="toggleStatus(country)"
                                                    :class="country.active ? 'text-red-600 hover:text-red-900 hover:bg-red-50' : 'text-green-600 hover:text-green-900 hover:bg-green-50'"
                                                    class="p-1 rounded"
                                                    :title="country.active ? 'Deactivate' : 'Activate'">
                                                <i :class="country.active ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                            </button>
                                            {{-- <button @click="deleteCountry(country)"
                                                    :disabled="country.users_count > 0"
                                                    :class="country.users_count > 0 ? 'text-gray-400 cursor-not-allowed' : 'text-red-600 hover:text-red-900 hover:bg-red-50'"
                                                    class="p-1 rounded"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button> --}}
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <tr x-show="countries.length === 0 && !loading">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-globe text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-lg">No countries found</p>
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
                                                :class="page === pagination.current_page ? 'z-10 bg-primary border-primary text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                                                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium">
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
    function countryManager() {
        return {
            countries: [],
            stats: {},
            pagination: {},
            loading: false,
            selectedCountries: [],
            filters: {
                search: '',
                region: '',
                status: '',
                sort_by: 'name'
            },
            searchTimeout: null,

            async loadCountries(page = 1) {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: page,
                        per_page: 15,
                        ...this.filters
                    });

                    const response = await fetch(`/admin/api/system/countries?${params}`, {
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
                        this.countries = data.data.data || [];
                        this.pagination = {
                            current_page: data.data.current_page || 1,
                            last_page: data.data.last_page || 1,
                            from: data.data.from || 0,
                            to: data.data.to || 0,
                            total: data.data.total || 0,
                            per_page: data.data.per_page || 15
                        };
                        this.stats = {
                            total: data.stats.total || 0,
                            active: data.stats.active || 0,
                            with_users: data.stats.with_users || 0,
                            regions: data.stats.regions || 0,
                            total_users: data.stats.total_users || 0
                        };
                    } else if (data.data && Array.isArray(data.data)) {
                        // Alternative format: { data: [...] }
                        this.countries = data.data || [];
                        this.pagination = {
                            current_page: data.current_page || 1,
                            last_page: data.last_page || 1,
                            from: data.from || 0,
                            to: data.to || 0,
                            total: data.total || 0,
                            per_page: data.per_page || 15
                        };
                        this.stats = data.stats || {};
                    } else if (Array.isArray(data)) {
                        // Simple array format: [...]
                        this.countries = data;
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
                        this.countries = [];
                        this.pagination = {
                            current_page: 1,
                            last_page: 1,
                            from: 0,
                            to: 0,
                            total: 0,
                            per_page: 15
                        };
                        this.stats = {};
                    }

                    this.selectedCountries = [];
                } catch (error) {
                    console.error('Failed to load countries:', error);
                    this.showError('Failed to load countries: ' + error.message);
                } finally {
                    this.loading = false;
                }
            },

            debounceSearch() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.loadCountries();
                }, 500);
            },

            changePage(page) {
                if (page >= 1 && page <= this.pagination.last_page) {
                    this.loadCountries(page);
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
                    this.selectedCountries = [];
                } else {
                    this.selectedCountries = this.countries.map(country => country.id);
                }
            },

            isAllSelected() {
                return this.countries.length > 0 && this.selectedCountries.length === this.countries.length;
            },

            async toggleStatus(country) {
                try {
                    const response = await fetch(`/admin/countries/${country.id}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ active: !country.active })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showSuccess(data.message || 'Country status updated successfully');
                        this.loadCountries();
                    } else {
                        this.showError(data.message || 'Failed to update country status');
                    }
                } catch (error) {
                    this.showError('Failed to update country status');
                }
            },

            async deleteCountry(country) {
                if (country.users_count > 0) {
                    this.showError('Cannot delete country with associated users');
                    return;
                }

                if (!confirm('Are you sure you want to delete this country? This action cannot be undone.')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/countries/${country.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (response.ok) {
                        this.showSuccess('Country deleted successfully');
                        this.loadCountries();
                    } else {
                        this.showError('Failed to delete country');
                    }
                } catch (error) {
                    this.showError('Failed to delete country');
                }
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

    function exportCountries() {
        window.location.href = '/admin/countries/export';
    }
</script>
@endpush
