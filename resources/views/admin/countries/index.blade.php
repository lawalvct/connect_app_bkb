@extends('admin.layouts.app')

@section('title', 'Countries Management')

@section('content')
<div x-data="countriesManager()" x-init="loadCountries()">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Countries Management</h1>
            <p class="text-gray-600 mt-1">Manage countries and their properties</p>
        </div>
        <div class="flex gap-3">
            <button @click="exportData" class="btn-secondary">
                <i class="fas fa-download mr-2"></i>
                Export
            </button>
            <button @click="showCreateModal = true" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Add Country
            </button>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input type="text" x-model="filters.search" @input="loadCountries()"
                       placeholder="Search countries..."
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
            </div>
            <div>
                <select x-model="filters.region" @change="loadCountries()"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">All Regions</option>
                    <option value="Africa">Africa</option>
                    <option value="Americas">Americas</option>
                    <option value="Asia">Asia</option>
                    <option value="Europe">Europe</option>
                    <option value="Oceania">Oceania</option>
                </select>
            </div>
            <div>
                <select x-model="filters.status" @change="loadCountries()"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div>
                <button @click="resetFilters()" class="btn-secondary w-full">
                    <i class="fas fa-undo mr-2"></i>
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-globe text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Countries</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.total || 0"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Active</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.active || 0"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-users text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">With Users</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.with_users || 0"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-map text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Regions</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.regions || 0"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Countries Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div x-show="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>

        <div x-show="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
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
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="country in countries" :key="country.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-12">
                                            <img :src="`https://flagcdn.com/w40/${country.code.toLowerCase()}.png`"
                                                 :alt="country.name"
                                                 class="h-6 w-8 object-cover rounded shadow-sm"
                                                 onerror="this.style.display='none'">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900" x-text="country.name"></div>
                                            <div class="text-sm text-gray-500">
                                                <span x-text="country.emoji"></span>
                                                <span x-text="country.capital"></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <span x-text="country.code" class="font-mono bg-gray-100 px-2 py-1 rounded"></span>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        +<span x-text="country.phone_code"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" x-text="country.region"></div>
                                    <div class="text-sm text-gray-500" x-text="country.subregion"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span x-text="country.users_count || 0"></span> users
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <span x-text="country.currency_symbol"></span>
                                        <span x-text="country.currency_code"></span>
                                    </div>
                                    <div class="text-sm text-gray-500" x-text="country.currency"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button @click="toggleStatus(country)"
                                            :class="country.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        <span x-text="country.active ? 'Active' : 'Inactive'"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <button @click="viewCountry(country)" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button @click="editCountry(country)" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="deleteCountry(country)" class="text-red-600 hover:text-red-900"
                                                :class="country.users_count > 0 ? 'opacity-50 cursor-not-allowed' : ''">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && pagination.total > pagination.per_page" class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-700">
            Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span> of <span x-text="pagination.total"></span> results
        </div>
        <div class="flex gap-2">
            <button @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1"
                    class="btn-secondary"
                    :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : ''">
                Previous
            </button>
            <button @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page"
                    class="btn-secondary"
                    :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : ''">
                Next
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
    .btn-primary {
        @apply inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg shadow-sm hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary;
    }
    .btn-secondary {
        @apply inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500;
    }
</style>
@endpush

@push('scripts')
<script>
function countriesManager() {
    return {
        countries: [],
        loading: false,
        showCreateModal: false,
        filters: {
            search: '',
            region: '',
            status: ''
        },
        stats: {
            total: 0,
            active: 0,
            with_users: 0,
            regions: 0
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
            from: 0,
            to: 0
        },

        async loadCountries() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    ...this.filters
                });

                const response = await fetch(`/admin/api/system/countries?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.countries = data.data.data;
                    this.pagination = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        per_page: data.data.per_page,
                        total: data.data.total,
                        from: data.data.from,
                        to: data.data.to
                    };
                    this.stats = data.stats;
                }
            } catch (error) {
                console.error('Error loading countries:', error);
            } finally {
                this.loading = false;
            }
        },

        async toggleStatus(country) {
            try {
                const response = await fetch(`/admin/countries/${country.id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        active: !country.active
                    })
                });

                const data = await response.json();
                if (data.success) {
                    country.active = !country.active;
                    this.loadCountries(); // Refresh stats
                } else {
                    alert('Error updating status: ' + data.message);
                }
            } catch (error) {
                console.error('Error updating status:', error);
                alert('Error updating status');
            }
        },

        async deleteCountry(country) {
            if (country.users_count > 0) {
                alert('Cannot delete country with associated users');
                return;
            }

            if (!confirm('Are you sure you want to delete this country?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/countries/${country.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.loadCountries();
                } else {
                    alert('Error deleting country: ' + data.message);
                }
            } catch (error) {
                console.error('Error deleting country:', error);
                alert('Error deleting country');
            }
        },

        viewCountry(country) {
            window.location.href = `/admin/countries/${country.id}`;
        },

        editCountry(country) {
            window.location.href = `/admin/countries/${country.id}/edit`;
        },

        exportData() {
            window.open('/admin/countries/export', '_blank');
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                this.loadCountries();
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                region: '',
                status: ''
            };
            this.pagination.current_page = 1;
            this.loadCountries();
        }
    }
}
</script>
@endpush
@endsection
