@extends('admin.layouts.app')

@section('title', 'Subscription Plans Management')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Subscription Plans</h1>
            <p class="text-gray-600">Manage subscription plans and pricing</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.subscriptions.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Subscriptions
            </a>
            <a href="{{ route('admin.subscriptions.plans.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Create New Plan
            </a>
            <button type="button"
                    onclick="exportPlans()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-download mr-2"></i>
                Export Plans
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="planManagement()" x-init="loadPlans(); loadStats()">

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Plans</label>
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
                                 @click="filters.search = ''; loadPlans()"
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
                                @change="loadPlans()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Plans</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div>
                        <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select id="sort"
                                x-model="filters.sort"
                                @change="loadPlans()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="sort_order">Sort Order</option>
                            <option value="price_asc">Price (Low to High)</option>
                            <option value="price_desc">Price (High to Low)</option>
                            <option value="popularity">Popularity</option>
                            <option value="created_at">Newest First</option>
                        </select>
                    </div>

                </div>

                <!-- Quick Stats -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-8 gap-4">
                    <div class="text-center p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-600">Total Plans</p>
                        <p class="text-xl font-bold text-blue-900" x-text="stats.total_plans || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-md">
                        <p class="text-sm text-green-600">Active Plans</p>
                        <p class="text-xl font-bold text-green-900" x-text="stats.active_plans || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-md">
                        <p class="text-sm text-red-600">Inactive Plans</p>
                        <p class="text-xl font-bold text-red-900" x-text="stats.inactive_plans || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-md">
                        <p class="text-sm text-purple-600">Total Subscribers</p>
                        <p class="text-xl font-bold text-purple-900" x-text="stats.total_subscribers || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-indigo-50 rounded-md">
                        <p class="text-sm text-indigo-600">Total Revenue</p>
                        <p class="text-xl font-bold text-indigo-900" x-text="'$' + (stats.total_plan_revenue || '0')">$0</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 rounded-md">
                        <p class="text-sm text-yellow-600">Avg Price</p>
                        <p class="text-xl font-bold text-yellow-900" x-text="'$' + (stats.avg_plan_price ? Math.round(stats.avg_plan_price) : '0')">$0</p>
                    </div>
                    <div class="text-center p-3 bg-pink-50 rounded-md">
                        <p class="text-sm text-pink-600">Popular Plan</p>
                        <p class="text-sm font-bold text-pink-900" x-text="stats.most_popular_plan || 'N/A'">N/A</p>
                    </div>
                    <div class="text-center p-3 bg-orange-50 rounded-md">
                        <p class="text-sm text-orange-600">Growth</p>
                        <p class="text-xl font-bold text-orange-900">
                            <span x-text="stats.subscription_growth ? (stats.subscription_growth > 0 ? '+' : '') + stats.subscription_growth + '%' : '0%'">0%</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plans Grid -->
        <div class="bg-white rounded-lg shadow-md">

            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-lg font-medium text-gray-900">
                        Subscription Plans
                    </div>

                    <!-- Pagination Info -->
                    <div class="text-sm text-gray-600">
                        Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                        of <span x-text="pagination.total || 0"></span> plans
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="p-8 text-center">
                <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">Loading plans...</p>
            </div>

            <!-- Plans Grid -->
            <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                <template x-for="plan in plans" :key="plan.id">
                    <div class="border rounded-lg p-6 hover:shadow-lg transition-shadow"
                         :class="plan.is_active ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'">

                        <!-- Plan Header -->
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900" x-text="plan.name"></h3>
                                <div class="flex items-center mt-1">
                                    <span class="text-2xl font-bold text-primary" x-text="'$' + plan.price"></span>
                                    <span class="text-sm text-gray-500 ml-1">/ <span x-text="plan.duration_days"></span> days</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <!-- Status Toggle -->
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           :checked="plan.is_active"
                                           @change="togglePlanStatus(plan)"
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Plan Description -->
                        <p class="text-gray-600 text-sm mb-4" x-text="plan.description"></p>

                        <!-- Features -->
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Features:</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <template x-for="feature in (plan.features || [])" :key="feature">
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-2"></i>
                                        <span x-text="feature"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Statistics -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="text-center p-3 bg-blue-100 rounded-lg">
                                <div class="text-xs text-blue-600 font-medium">Total Subscribers</div>
                                <div class="text-xl font-bold text-blue-900" x-text="plan.user_subscriptions_count || 0"></div>
                                <div class="text-xs text-blue-500">All time</div>
                            </div>
                            <div class="text-center p-3 bg-green-100 rounded-lg">
                                <div class="text-xs text-green-600 font-medium">Active Subscribers</div>
                                <div class="text-xl font-bold text-green-900" x-text="plan.active_user_subscriptions_count || 0"></div>
                                <div class="text-xs text-green-500">Currently paying</div>
                            </div>
                        </div>

                        <!-- Revenue Info -->
                        <div class="mb-4 p-3 bg-yellow-100 rounded-lg">
                            <div class="text-center">
                                <div class="text-xs text-yellow-600 font-medium">Monthly Revenue</div>
                                <div class="text-lg font-bold text-yellow-900">
                                    $<span x-text="((plan.active_user_subscriptions_count || 0) * parseFloat(plan.price || 0)).toFixed(2)"></span>
                                </div>
                                <div class="text-xs text-yellow-500">From active users</div>
                            </div>
                        </div>

                        <!-- Payment Gateway Info -->
                        <div class="mb-4 text-xs text-gray-500">
                            <div x-show="plan.stripe_price_id">
                                <i class="fab fa-stripe mr-1"></i>
                                Stripe ID: <span x-text="plan.stripe_price_id"></span>
                            </div>
                            <div x-show="plan.nomba_plan_id">
                                <i class="fas fa-credit-card mr-1"></i>
                                Nomba ID: <span x-text="plan.nomba_plan_id"></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex space-x-2">
                            <button @click="viewPlan(plan.id)"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-eye mr-1"></i>
                                View Details
                            </button>
                            <button @click="editPlan(plan)"
                                    class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-2 rounded text-sm font-medium transition-colors"
                                    title="Edit Plan">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click="deletePlan(plan)"
                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-medium transition-colors"
                                    title="Delete Plan"
                                    :disabled="plan.active_user_subscriptions_count > 0">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div x-show="!loading && plans.length === 0" class="col-span-full">
                    <div class="text-center py-12">
                        <i class="fas fa-layer-group text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No plans found</h3>
                        <p class="text-gray-600">No subscription plans match your current filters.</p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div x-show="!loading && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing
                        <span class="font-medium" x-text="pagination.from"></span>
                        to
                        <span class="font-medium" x-text="pagination.to"></span>
                        of
                        <span class="font-medium" x-text="pagination.total"></span>
                        results
                    </div>
                    <div class="flex space-x-2">
                        <button @click="changePage(pagination.current_page - 1)"
                                :disabled="pagination.current_page <= 1"
                                :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                                class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md">
                            Previous
                        </button>
                        <template x-for="page in Array.from({length: pagination.last_page}, (_, i) => i + 1)" :key="page">
                            <button x-show="page === 1 || page === pagination.last_page || (page >= pagination.current_page - 2 && page <= pagination.current_page + 2)"
                                    @click="changePage(page)"
                                    :class="page === pagination.current_page ? 'bg-primary text-white' : 'text-gray-700 bg-white hover:bg-gray-100'"
                                    class="px-3 py-2 text-sm font-medium border border-gray-300 rounded-md"
                                    x-text="page">
                            </button>
                        </template>
                        <button @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page >= pagination.last_page"
                                :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                                class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function planManagement() {
            return {
                loading: false,
                plans: [],
                stats: {},
                pagination: {},
                searchTimeout: null,
                filters: {
                    search: '',
                    status: '',
                    sort: 'sort_order',
                    page: 1
                },

                loadPlans() {
                    console.log('loadPlans called');
                    this.loading = true;
                    const params = new URLSearchParams();

                    Object.keys(this.filters).forEach(key => {
                        if (this.filters[key]) {
                            params.append(key, this.filters[key]);
                        }
                    });

                    const url = `{{ route('admin.subscriptions.plans.get') }}?${params}`;
                    console.log('Fetching URL:', url);

                    fetch(url)
                        .then(response => {
                            console.log('Response status:', response.status);
                            console.log('Response headers:', response.headers);
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Response data:', data);
                            if (data.success) {
                                this.plans = data.plans.data;
                                this.pagination = {
                                    current_page: data.plans.current_page,
                                    last_page: data.plans.last_page,
                                    per_page: data.plans.per_page,
                                    total: data.plans.total,
                                    from: data.plans.from,
                                    to: data.plans.to
                                };
                                console.log('Plans loaded:', this.plans.length);
                            } else {
                                console.error('API returned success=false:', data);
                                alert('API returned an error: ' + (data.message || data.error || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error loading plans:', error);
                            alert('Error loading plans: ' + error.message);
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },                loadStats() {
                    fetch('{{ route('admin.subscriptions.plans.stats') }}')
                        .then(response => response.json())
                        .then(data => {
                            this.stats = data;
                        })
                        .catch(error => {
                            console.error('Error loading stats:', error);
                        });
                },

                debounceSearch() {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.filters.page = 1;
                        this.loadPlans();
                    }, 500);
                },

                changePage(page) {
                    if (page >= 1 && page <= this.pagination.last_page) {
                        this.filters.page = page;
                        this.loadPlans();
                    }
                },

                viewPlan(id) {
                    window.location.href = `{{ route('admin.subscriptions.plans.show', ':id') }}`.replace(':id', id);
                },

                editPlan(plan) {
                    window.location.href = `{{ route('admin.subscriptions.plans.edit', ':id') }}`.replace(':id', plan.id);
                },

                deletePlan(plan) {
                    if (plan.active_user_subscriptions_count > 0) {
                        alert('Cannot delete plan with active subscriptions. Please wait for subscriptions to expire or transfer users to another plan.');
                        return;
                    }

                    if (confirm(`Are you sure you want to delete the plan "${plan.name}"? This action cannot be undone.`)) {
                        fetch(`{{ route('admin.subscriptions.plans.destroy', ':id') }}`.replace(':id', plan.id), {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Plan deleted successfully!');
                                this.loadPlans();
                                this.loadStats();
                            } else {
                                alert('Error: ' + (data.message || 'Failed to delete plan'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the plan');
                        });
                    }
                },

                togglePlanStatus(plan) {
                    const newStatus = !plan.is_active;

                    fetch(`{{ route('admin.subscriptions.plans.update-status', ':id') }}`.replace(':id', plan.id), {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            is_active: newStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            plan.is_active = newStatus;
                            this.loadStats();
                            alert('Plan status updated successfully');
                        } else {
                            alert(data.message || 'Error updating plan status');
                            // Revert the toggle
                            plan.is_active = !newStatus;
                        }
                    })
                    .catch(error => {
                        console.error('Error updating plan status:', error);
                        alert('Error updating plan status');
                        // Revert the toggle
                        plan.is_active = !newStatus;
                    });
                }
            }
        }

        function exportPlans() {
            window.location.href = '{{ route('admin.subscriptions.plans.export') }}';
        }
    </script>
@endsection
