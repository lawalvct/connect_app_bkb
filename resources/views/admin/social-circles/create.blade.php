@extends('admin.layouts.app')

@section('title', 'Create Social Circle')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Social Circle</h1>
            <p class="text-gray-600 mt-1">Add a new social circle to the system</p>
        </div>
        <a href="{{ route('admin.social-circles.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Social Circles
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form action="{{ route('admin.social-circles.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="Enter social circle name">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                    <input type="color" name="color"
                           class="w-full h-10 rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           value="#6B7280">
                    @error('color')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                              placeholder="Enter description (optional)"></textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Logo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                    <input type="file" name="logo" accept="image/*"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    <p class="text-sm text-gray-500 mt-1">Max size: 2MB. Formats: jpeg, png, jpg, gif, svg</p>
                    @error('logo')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                    <input type="number" name="order_by"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="0" min="0">
                    @error('order_by')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Settings -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Settings</label>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_default" value="1"
                                   class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring-primary">
                            <label class="ml-2 text-sm text-gray-700">Default social circle</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" checked
                                   class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring-primary">
                            <label class="ml-2 text-sm text-gray-700">Active</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="is_private" value="1"
                                   class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring-primary">
                            <label class="ml-2 text-sm text-gray-700">Private circle</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.social-circles.index') }}" class="btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Create Social Circle
                </button>
            </div>
        </form>
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
@endsection
