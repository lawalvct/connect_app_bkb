@extends('admin.layouts.app')

@section('title', 'Create Country')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Country</h1>
            <p class="text-gray-600 mt-1">Add a new country to the system</p>
        </div>
        <a href="{{ route('admin.countries.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Countries
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form action="{{ route('admin.countries.store') }}" method="POST" class="p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Basic Info -->
                <div class="md:col-span-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Country Name *</label>
                    <input type="text" name="name" required
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. United States">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ISO Code 2 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ISO Code (2) *</label>
                    <input type="text" name="code" required maxlength="2"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. US" style="text-transform: uppercase;">
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- ISO Code 3 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ISO Code (3)</label>
                    <input type="text" name="code3" maxlength="3"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. USA" style="text-transform: uppercase;">
                    @error('code3')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Code</label>
                    <input type="text" name="phone_code"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. 1">
                    @error('phone_code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Capital -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Capital</label>
                    <input type="text" name="capital"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. Washington D.C.">
                    @error('capital')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Emoji -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Flag Emoji</label>
                    <input type="text" name="emoji"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. 🇺🇸">
                    @error('emoji')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Currency Info -->
                <div class="md:col-span-3 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Currency Information</h3>
                </div>

                <!-- Currency -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                    <input type="text" name="currency"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. US Dollar">
                    @error('currency')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Currency Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Currency Code</label>
                    <input type="text" name="currency_code" maxlength="3"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. USD" style="text-transform: uppercase;">
                    @error('currency_code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Currency Symbol -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol</label>
                    <input type="text" name="currency_symbol"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. $">
                    @error('currency_symbol')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Geographic Info -->
                <div class="md:col-span-3 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Geographic Information</h3>
                </div>

                <!-- Region -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Region</label>
                    <select name="region" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Select Region</option>
                        <option value="Africa">Africa</option>
                        <option value="Americas">Americas</option>
                        <option value="Asia">Asia</option>
                        <option value="Europe">Europe</option>
                        <option value="Oceania">Oceania</option>
                    </select>
                    @error('region')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subregion -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Subregion</label>
                    <input type="text" name="subregion"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. Northern America">
                    @error('subregion')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Timezone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Timezone</label>
                    <input type="text" name="timezone"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. America/New_York">
                    @error('timezone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Coordinates -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                    <input type="number" name="latitude" step="any" min="-90" max="90"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. 37.0902">
                    @error('latitude')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                    <input type="number" name="longitude" step="any" min="-180" max="180"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="e.g. -95.7129">
                    @error('longitude')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div class="md:col-span-3 mt-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="active" value="1" checked
                               class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring-primary">
                        <label class="ml-2 text-sm text-gray-700">Active</label>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.countries.index') }}" class="btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Create Country
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
