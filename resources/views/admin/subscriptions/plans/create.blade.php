@extends('admin.layouts.app')

@section('title', 'Create Subscription Plan')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Subscription Plan</h1>
            <nav class="flex mt-2" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li><a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-primary">Dashboard</a></li>
                    <li><span class="text-gray-400">/</span></li>
                    <li><a href="{{ route('admin.subscriptions.plans.index') }}" class="text-gray-500 hover:text-primary">Subscription Plans</a></li>
                    <li><span class="text-gray-400">/</span></li>
                    <li><span class="text-gray-900">Create Plan</span></li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.subscriptions.plans.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Plans
        </a>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Plan Information</h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('admin.subscriptions.plans.store') }}" method="POST" x-data="planForm()">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="md:col-span-3">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2 required">Plan Name</label>
                                <input type="text"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('name') border-red-500 @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name') }}"
                                       x-model="form.name"
                                       @input="generateSlug()"
                                       required>
                                @error('name')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                                <input type="text"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('slug') border-red-500 @enderror"
                                       id="slug"
                                       name="slug"
                                       value="{{ old('slug') }}"
                                       x-model="form.slug"
                                       placeholder="Auto-generated">
                                @error('slug')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-gray-500 text-xs mt-1">Leave empty to auto-generate from name</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2 required">Description</label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('description') border-red-500 @enderror"
                                      id="description"
                                      name="description"
                                      rows="4"
                                      required>{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 mb-2 required">Price</label>
                                <input type="number"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('price') border-red-500 @enderror"
                                       id="price"
                                       name="price"
                                       value="{{ old('price') }}"
                                       step="0.01"
                                       min="0"
                                       required>
                                @error('price')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700 mb-2 required">Currency</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('currency') border-red-500 @enderror"
                                        id="currency"
                                        name="currency"
                                        required>
                                    <option value="">Select Currency</option>
                                    <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                                    {{-- <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                                    <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP</option> --}}
                                    <option value="NGN" {{ old('currency') === 'NGN' ? 'selected' : '' }}>NGN</option>
                                </select>
                                @error('currency')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="duration_days" class="block text-sm font-medium text-gray-700 mb-2 required">Duration (Days)</label>
                                <input type="number"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('duration_days') border-red-500 @enderror"
                                       id="duration_days"
                                       name="duration_days"
                                       value="{{ old('duration_days') }}"
                                       min="1"
                                       required>
                                @error('duration_days')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Features</label>
                            <div x-data="{ features: {{ json_encode(old('features', [''])) }} }">
                                <template x-for="(feature, index) in features" :key="index">
                                    <div class="flex mb-2">
                                        <input type="text"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                               :name="'features[' + index + ']'"
                                               x-model="features[index]"
                                               placeholder="Enter feature description">
                                        <button type="button"
                                                class="px-3 py-2 bg-red-600 text-white rounded-r-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                                                @click="features.splice(index, 1)"
                                                x-show="features.length > 1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </template>
                                <button type="button"
                                        class="mt-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        @click="features.push('')">
                                    <i class="fas fa-plus mr-1"></i>Add Feature
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label for="stripe_price_id" class="block text-sm font-medium text-gray-700 mb-2">Stripe Price ID</label>
                                <input type="text"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('stripe_price_id') border-red-500 @enderror"
                                       id="stripe_price_id"
                                       name="stripe_price_id"
                                       value="{{ old('stripe_price_id') }}"
                                       placeholder="price_xxxxxxxxxxxxx">
                                @error('stripe_price_id')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="nomba_plan_id" class="block text-sm font-medium text-gray-700 mb-2">Nomba Plan ID</label>
                                <input type="text"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('nomba_plan_id') border-red-500 @enderror"
                                       id="nomba_plan_id"
                                       name="nomba_plan_id"
                                       value="{{ old('nomba_plan_id') }}"
                                       placeholder="nomba_plan_xxxxxxxxxxxxx">
                                @error('nomba_plan_id')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                            <div>
                                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                                <input type="number"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('sort_order') border-red-500 @enderror"
                                       id="sort_order"
                                       name="sort_order"
                                       value="{{ old('sort_order', 0) }}"
                                       min="0">
                                @error('sort_order')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="badge_color" class="block text-sm font-medium text-gray-700 mb-2">Badge Color</label>
                                <input type="color"
                                       class="w-full h-10 px-1 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('badge_color') border-red-500 @enderror"
                                       id="badge_color"
                                       name="badge_color"
                                       value="{{ old('badge_color', '#007bff') }}">
                                @error('badge_color')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="icon" class="block text-sm font-medium text-gray-700 mb-2">Icon Class</label>
                                <input type="text"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('icon') border-red-500 @enderror"
                                       id="icon"
                                       name="icon"
                                       value="{{ old('icon') }}"
                                       placeholder="fas fa-star">
                                @error('icon')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-center">
                                <input type="checkbox"
                                       class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                                       id="is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                    Active Plan
                                </label>
                            </div>
                            <p class="text-gray-500 text-sm mt-1">Only active plans will be available for new subscriptions</p>
                        </div>

                        <div class="flex justify-between items-center pt-6 border-t border-gray-200 mt-8">
                            <a href="{{ route('admin.subscriptions.plans.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-primary">
                                <i class="fas fa-save mr-2"></i>Create Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-primary">Plan Preview</h3>
                </div>
                <div class="p-6" x-data="{
                    previewName: '{{ old('name', 'Plan Name') }}',
                    previewPrice: '{{ old('price', '0.00') }}',
                    previewCurrency: '{{ old('currency', 'USD') }}',
                    previewDuration: '{{ old('duration_days', '30') }}'
                }">
                    <div class="text-center">
                        <h5 class="text-xl font-semibold text-gray-900 mb-2" x-text="previewName || 'Plan Name'"></h5>
                        <div class="text-3xl font-bold text-primary mb-2">
                            <span x-text="previewCurrency"></span>
                            <span x-text="parseFloat(previewPrice || 0).toFixed(2)"></span>
                        </div>
                        <p class="text-gray-500">
                            for <span x-text="previewDuration || '30'"></span> days
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-blue-600">Tips</h3>
                </div>
                <div class="p-6">
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                            <span class="text-sm text-gray-600">Use clear, descriptive plan names</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                            <span class="text-sm text-gray-600">Set competitive pricing based on features</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                            <span class="text-sm text-gray-600">List key features that users will get</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                            <span class="text-sm text-gray-600">Use payment gateway IDs for automated billing</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function planForm() {
    return {
        form: {
            name: '{{ old('name') }}',
            slug: '{{ old('slug') }}'
        },
        generateSlug() {
            if (!this.form.slug && this.form.name) {
                this.form.slug = this.form.name
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        }
    }
}
</script>

<style>
.required::after {
    content: " *";
    color: #e53e3e;
}
</style>
@endsection
