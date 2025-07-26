@extends('admin.layouts.app')

@section('title', 'Subscription Management')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Subscription Management</h1>
            <p class="text-gray-600">Manage user subscriptions and payments</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.subscriptions.plans.index') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-layer-group mr-2"></i>
                Manage Plans
            </a>
            <button type="button"
                    onclick="exportSubscriptions()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-download mr-2"></i>
                Export
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="subscriptionManagement()" x-init="loadSubscriptions(); loadStats(); loadAvailablePlans()">>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <input type="text"
                                   id="search"
                                   x-model="filters.search"
                                   @input="debounceSearch()"
                                   placeholder="Search users, plans..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <div x-show="filters.search"
                                 @click="filters.search = ''; loadSubscriptions()"
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
                                @change="loadSubscriptions()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- Payment Status Filter -->
                    <div>
                        <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                        <select id="payment_status"
                                x-model="filters.payment_status"
                                @change="loadSubscriptions()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Payments</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- Plan Filter -->
                    <div>
                        <label for="subscription_plan" class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                        <select id="subscription_plan"
                                x-model="filters.subscription_plan"
                                @change="loadSubscriptions()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Plans</option>
                            <template x-for="plan in availablePlans" :key="plan.id">
                                <option :value="plan.id" x-text="plan.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Payment Method Filter -->
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select id="payment_method"
                                x-model="filters.payment_method"
                                @change="loadSubscriptions()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Methods</option>
                            <option value="stripe">Stripe</option>
                            <option value="nomba">Nomba</option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div>
                        <label for="date_range" class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                        <select id="date_range"
                                x-model="filters.date_range"
                                @change="loadSubscriptions()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="active">Currently Active</option>
                            <option value="expiring_soon">Expiring Soon (7 days)</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>

                </div>

                <!-- Quick Stats -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-8 gap-4">
                    <div class="text-center p-3 bg-blue-50 rounded-md">
                        <p class="text-sm text-blue-600">Total</p>
                        <p class="text-xl font-bold text-blue-900" x-text="stats.total_subscriptions || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-md">
                        <p class="text-sm text-green-600">Active</p>
                        <p class="text-xl font-bold text-green-900" x-text="stats.active_subscriptions || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-md">
                        <p class="text-sm text-red-600">Expired</p>
                        <p class="text-xl font-bold text-red-900" x-text="stats.expired_subscriptions || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 rounded-md">
                        <p class="text-sm text-yellow-600">Cancelled</p>
                        <p class="text-xl font-bold text-yellow-900" x-text="stats.cancelled_subscriptions || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-md">
                        <p class="text-sm text-purple-600">Total Revenue</p>
                        <p class="text-xl font-bold text-purple-900" x-text="'$' + (stats.total_revenue || '0')">$0</p>
                    </div>
                    <div class="text-center p-3 bg-indigo-50 rounded-md">
                        <p class="text-sm text-indigo-600">Monthly Revenue</p>
                        <p class="text-xl font-bold text-indigo-900" x-text="'$' + (stats.monthly_revenue || '0')">$0</p>
                    </div>
                    <div class="text-center p-3 bg-orange-50 rounded-md">
                        <p class="text-sm text-orange-600">Pending</p>
                        <p class="text-xl font-bold text-orange-900" x-text="stats.pending_payments || '0'">0</p>
                    </div>
                    <div class="text-center p-3 bg-pink-50 rounded-md">
                        <p class="text-sm text-pink-600">Expiring Soon</p>
                        <p class="text-xl font-bold text-pink-900" x-text="stats.expiring_soon || '0'">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscriptions Table -->
        <div class="bg-white rounded-lg shadow-md">

            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-lg font-medium text-gray-900">
                        User Subscriptions
                    </div>

                    <!-- Pagination Info -->
                    <div class="text-sm text-gray-600">
                        Showing <span x-text="pagination.from || 0"></span> to <span x-text="pagination.to || 0"></span>
                        of <span x-text="pagination.total || 0"></span> subscriptions
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="p-8 text-center">
                <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">Loading subscriptions...</p>
            </div>

            <!-- Table Content -->
            <div x-show="!loading" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Plan
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Payment
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Expires
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="subscription in subscriptions" :key="subscription.id">
                            <tr class="hover:bg-gray-50">
                                <!-- User -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full"
                                                 :src="subscription.user?.profile_picture || '/images/default-avatar.png'"
                                                 :alt="subscription.user?.username"
                                                 onerror="this.src='/images/default-avatar.png'">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900" x-text="subscription.user?.username || 'N/A'"></div>
                                            <div class="text-sm text-gray-500" x-text="subscription.user?.email || 'N/A'"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Plan -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900" x-text="subscription.subscription?.name || 'N/A'"></div>
                                    <div class="text-sm text-gray-500" x-text="subscription.subscription?.duration_days + ' days' || 'N/A'"></div>
                                </td>

                                <!-- Amount -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <span x-text="subscription.currency || 'USD'"></span>
                                        <span x-text="subscription.amount || '0'"></span>
                                    </div>
                                    <div class="text-sm text-gray-500" x-text="subscription.payment_method || 'N/A'"></div>
                                </td>

                                <!-- Payment Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                          :class="{
                                              'bg-green-100 text-green-800': subscription.payment_status === 'completed',
                                              'bg-yellow-100 text-yellow-800': subscription.payment_status === 'pending',
                                              'bg-red-100 text-red-800': subscription.payment_status === 'failed',
                                              'bg-gray-100 text-gray-800': subscription.payment_status === 'cancelled'
                                          }"
                                          x-text="subscription.payment_status ? subscription.payment_status.charAt(0).toUpperCase() + subscription.payment_status.slice(1) : 'N/A'">
                                    </span>
                                </td>

                                <!-- Subscription Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                          :class="{
                                              'bg-green-100 text-green-800': subscription.status === 'active',
                                              'bg-red-100 text-red-800': subscription.status === 'expired',
                                              'bg-gray-100 text-gray-800': subscription.status === 'cancelled'
                                          }"
                                          x-text="subscription.status ? subscription.status.charAt(0).toUpperCase() + subscription.status.slice(1) : 'N/A'">
                                    </span>
                                </td>

                                <!-- Expires At -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div x-text="subscription.expires_at ? new Date(subscription.expires_at).toLocaleDateString() : 'N/A'"></div>
                                    <div class="text-xs text-gray-500"
                                         x-text="subscription.expires_at ? new Date(subscription.expires_at).toLocaleTimeString() : ''"></div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <button @click="viewSubscription(subscription.id)"
                                                class="text-blue-600 hover:text-blue-900 transition-colors">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button @click="editSubscription(subscription)"
                                                class="text-yellow-600 hover:text-yellow-900 transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="extendSubscription(subscription)"
                                                class="text-green-600 hover:text-green-900 transition-colors"
                                                x-show="subscription.status === 'active'">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="!loading && subscriptions.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-credit-card text-4xl text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No subscriptions found</h3>
                                <p class="text-gray-600">No subscriptions match your current filters.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
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

        <!-- Edit Subscription Modal -->
        <div x-show="showEditModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             @click.self="showEditModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 text-center mb-4">Update Subscription Status</h3>
                    <form @submit.prevent="updateSubscriptionStatus()">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select x-model="editForm.status" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                                <option value="active">Active</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                            <textarea x-model="editForm.reason" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"
                                      placeholder="Optional reason for status change..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showEditModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-dark">
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Extend Subscription Modal -->
        <div x-show="showExtendModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             @click.self="showExtendModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 text-center mb-4">Extend Subscription</h3>
                    <form @submit.prevent="submitExtendSubscription()">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Days to Add</label>
                            <input type="number" x-model="extendForm.days" min="1" max="365" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                            <textarea x-model="extendForm.reason" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"
                                      placeholder="Optional reason for extension..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showExtendModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                                Extend Subscription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        function subscriptionManagement() {
            return {
                loading: false,
                subscriptions: [],
                availablePlans: [],
                stats: {},
                pagination: {},
                searchTimeout: null,
                showEditModal: false,
                showExtendModal: false,
                currentSubscription: null,
                editForm: {
                    status: '',
                    reason: ''
                },
                extendForm: {
                    days: '',
                    reason: ''
                },
                filters: {
                    search: '',
                    status: '',
                    payment_status: '',
                    subscription_plan: '',
                    payment_method: '',
                    date_range: '',
                    page: 1
                },

                loadSubscriptions() {
                    this.loading = true;
                    const params = new URLSearchParams();

                    Object.keys(this.filters).forEach(key => {
                        if (this.filters[key]) {
                            params.append(key, this.filters[key]);
                        }
                    });

                    fetch(`{{ route('admin.subscriptions.get') }}?${params}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.subscriptions = data.subscriptions.data;
                                this.pagination = {
                                    current_page: data.subscriptions.current_page,
                                    last_page: data.subscriptions.last_page,
                                    per_page: data.subscriptions.per_page,
                                    total: data.subscriptions.total,
                                    from: data.subscriptions.from,
                                    to: data.subscriptions.to
                                };
                            }
                        })
                        .catch(error => {
                            console.error('Error loading subscriptions:', error);
                            alert('Error loading subscriptions');
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },

                loadStats() {
                    fetch('{{ route('admin.subscriptions.stats') }}')
                        .then(response => response.json())
                        .then(data => {
                            this.stats = data;
                        })
                        .catch(error => {
                            console.error('Error loading stats:', error);
                        });
                },

                loadAvailablePlans() {
                    fetch('{{ route('admin.subscriptions.plans.get') }}?per_page=100')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.availablePlans = data.plans.data;
                            }
                        })
                        .catch(error => {
                            console.error('Error loading available plans:', error);
                        });
                },

                loadAvailablePlans() {
                    fetch('{{ route('admin.subscriptions.plans.get') }}?per_page=100')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.availablePlans = data.plans.data;
                            }
                        })
                        .catch(error => {
                            console.error('Error loading available plans:', error);
                        });
                },

                debounceSearch() {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.filters.page = 1;
                        this.loadSubscriptions();
                    }, 500);
                },

                changePage(page) {
                    if (page >= 1 && page <= this.pagination.last_page) {
                        this.filters.page = page;
                        this.loadSubscriptions();
                    }
                },

                viewSubscription(id) {
                    window.location.href = `{{ route('admin.subscriptions.show', ':id') }}`.replace(':id', id);
                },

                editSubscription(subscription) {
                    this.currentSubscription = subscription;
                    this.editForm.status = subscription.status;
                    this.editForm.reason = '';
                    this.showEditModal = true;
                },

                updateSubscriptionStatus() {
                    if (!this.currentSubscription) return;

                    fetch(`{{ route('admin.subscriptions.update-status', ':id') }}`.replace(':id', this.currentSubscription.id), {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.editForm)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showEditModal = false;
                            this.loadSubscriptions();
                            this.loadStats();
                            alert('Subscription status updated successfully');
                        } else {
                            alert(data.message || 'Error updating subscription status');
                        }
                    })
                    .catch(error => {
                        console.error('Error updating subscription:', error);
                        alert('Error updating subscription status');
                    });
                },

                extendSubscription(subscription) {
                    this.currentSubscription = subscription;
                    this.extendForm.days = '';
                    this.extendForm.reason = '';
                    this.showExtendModal = true;
                },

                submitExtendSubscription() {
                    if (!this.currentSubscription) return;

                    fetch(`{{ route('admin.subscriptions.extend', ':id') }}`.replace(':id', this.currentSubscription.id), {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.extendForm)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showExtendModal = false;
                            this.loadSubscriptions();
                            this.loadStats();
                            alert(data.message);
                        } else {
                            alert(data.message || 'Error extending subscription');
                        }
                    })
                    .catch(error => {
                        console.error('Error extending subscription:', error);
                        alert('Error extending subscription');
                    });
                }
            }
        }

        function exportSubscriptions() {
            window.location.href = '{{ route('admin.subscriptions.export') }}';
        }
    </script>
@endsection
