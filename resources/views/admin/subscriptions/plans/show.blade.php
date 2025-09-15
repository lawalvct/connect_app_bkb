@extends('admin.layouts.app')

@section('title', 'Plan Details')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $plan->name }} Plan</h1>
            <p class="text-gray-600">View plan details and subscribers</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.subscriptions.plans.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Plans
            </a>
            <button type="button"
                    onclick="togglePlanStatus()"
                    class="bg-{{ $plan->is_active ? 'red' : 'green' }}-600 hover:bg-{{ $plan->is_active ? 'red' : 'green' }}-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                <i class="fas fa-{{ $plan->is_active ? 'ban' : 'check' }} mr-2"></i>
                {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div x-data="planDetails()" x-init="init()">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Main Plan Details -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Plan Information -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Plan Information</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Plan Name</label>
                                    <div class="mt-1 text-lg font-semibold text-gray-900">{{ $plan->name }}</div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Description</label>
                                    <div class="mt-1 text-gray-600">{{ $plan->description }}</div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Slug</label>
                                    <div class="mt-1 text-gray-900 font-mono">{{ $plan->slug }}</div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Price</label>
                                    <div class="mt-1">
                                        <span class="text-2xl font-bold text-primary">{{ $plan->currency }} {{ number_format($plan->price, 2) }}</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Duration</label>
                                    <div class="mt-1 text-lg font-medium text-gray-900">{{ $plan->duration_days }} days</div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Sort Order</label>
                                    <div class="mt-1 text-gray-900">{{ $plan->sort_order }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Features -->
                        @if($plan->features && count($plan->features) > 0)
                            <div class="mt-6">
                                <label class="text-sm font-medium text-gray-700 block mb-3">Features</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($plan->features as $feature)
                                        <div class="flex items-center">
                                            <i class="fas fa-check text-green-500 mr-2"></i>
                                            <span class="text-gray-600">{{ $feature }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Payment Gateway Integration -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($plan->stripe_price_id)
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <i class="fab fa-stripe text-blue-600 mr-2"></i>
                                        <span class="font-medium text-gray-900">Stripe Integration</span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Price ID: <span class="font-mono">{{ $plan->stripe_price_id }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($plan->nomba_plan_id)
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-credit-card text-green-600 mr-2"></i>
                                        <span class="font-medium text-gray-900">Nomba Integration</span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Plan ID: <span class="font-mono">{{ $plan->nomba_plan_id }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Subscribers -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Subscribers</h3>
                            <div class="flex space-x-2">
                                <button @click="filterSubscribers('all')"
                                        :class="subscriberFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700'"
                                        class="px-3 py-1 rounded text-sm font-medium">
                                    All ({{ $plan->userSubscriptions->count() }})
                                </button>
                                <button @click="filterSubscribers('active')"
                                        :class="subscriberFilter === 'active' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'"
                                        class="px-3 py-1 rounded text-sm font-medium">
                                    Active ({{ $plan->activeUserSubscriptions->count() }})
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Started
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
                                <template x-for="subscription in filteredSubscriptions" :key="subscription.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8">
                                                    <img class="h-8 w-8 rounded-full"
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div x-text="subscription.started_at ? new Date(subscription.started_at).toLocaleDateString() : 'N/A'"></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div x-text="subscription.expires_at ? new Date(subscription.expires_at).toLocaleDateString() : 'N/A'"></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button @click="viewSubscription(subscription.id)"
                                                    class="text-blue-600 hover:text-blue-900 transition-colors">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr x-show="filteredSubscriptions.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">No subscribers found</h3>
                                        <p class="text-gray-600">No subscribers match the current filter.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Status Card -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Status</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Plan Status</label>
                                <div class="mt-1">
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            @if($plan->badge_color)
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Badge Color</label>
                                    <div class="mt-1 flex items-center">
                                        <div class="w-4 h-4 rounded" style="background-color: {{ $plan->badge_color }}"></div>
                                        <span class="ml-2 text-sm text-gray-600">{{ $plan->badge_color }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($plan->icon)
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Icon</label>
                                    <div class="mt-1">
                                        <i class="{{ $plan->icon }} text-lg text-primary"></i>
                                        <span class="ml-2 text-sm text-gray-600">{{ $plan->icon }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Statistics</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Subscribers</span>
                            <span class="text-lg font-bold text-gray-900">{{ $plan->userSubscriptions->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Active Subscribers</span>
                            <span class="text-lg font-bold text-green-600">{{ $plan->activeUserSubscriptions->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Revenue</span>
                            <span class="text-lg font-bold text-primary">
                                ${{ number_format($plan->userSubscriptions->where('payment_status', 'completed')->sum('amount'), 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Monthly Revenue</span>
                            <span class="text-lg font-bold text-purple-600">
                                ${{ number_format($plan->userSubscriptions->where('payment_status', 'completed')->filter(function($subscription) {
                                    return $subscription->created_at->month === now()->month;
                                })->sum('amount'), 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                {{-- <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <button onclick="editPlan()"
                                class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Plan
                        </button>

                        <button onclick="duplicatePlan()"
                                class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-copy mr-2"></i>
                            Duplicate Plan
                        </button>

                        <button onclick="exportSubscribers()"
                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            Export Subscribers
                        </button>
                    </div>
                </div> --}}

                <!-- Metadata -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Metadata</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">ID:</span>
                                <span class="font-medium text-gray-900">#{{ $plan->id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Created:</span>
                                <span class="font-medium text-gray-900">{{ $plan->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Updated:</span>
                                <span class="font-medium text-gray-900">{{ $plan->updated_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <script>
        function planDetails() {
            return {
                subscriberFilter: 'all',
                allSubscriptions: @json($plan->userSubscriptions),

                init() {
                    // Initialize with user data
                },

                get filteredSubscriptions() {
                    if (this.subscriberFilter === 'all') {
                        return this.allSubscriptions;
                    } else if (this.subscriberFilter === 'active') {
                        return this.allSubscriptions.filter(sub => sub.status === 'active');
                    }
                    return this.allSubscriptions;
                },

                filterSubscribers(filter) {
                    this.subscriberFilter = filter;
                },

                viewSubscription(id) {
                    window.location.href = `{{ route('admin.subscriptions.show', ':id') }}`.replace(':id', id);
                }
            }
        }

        function togglePlanStatus() {
            const isActive = {{ $plan->is_active ? 'true' : 'false' }};
            const action = isActive ? 'deactivate' : 'activate';

            if (confirm(`Are you sure you want to ${action} this plan?`)) {
                fetch(`{{ route('admin.subscriptions.plans.update-status', $plan->id) }}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        is_active: !isActive
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Plan ${action}d successfully`);
                        location.reload();
                    } else {
                        alert(data.message || `Error ${action}ing plan`);
                    }
                })
                .catch(error => {
                    console.error(`Error ${action}ing plan:`, error);
                    alert(`Error ${action}ing plan`);
                });
            }
        }

        function editPlan() {
            alert('Edit plan functionality would be implemented here');
        }

        function duplicatePlan() {
            alert('Duplicate plan functionality would be implemented here');
        }

        function exportSubscribers() {
            alert('Export subscribers functionality would be implemented here');
        }
    </script>
@endsection
