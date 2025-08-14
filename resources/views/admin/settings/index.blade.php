@extends('admin.layouts.app')

@section('title', 'System Settings')
@section('page-title', 'System Settings')

@push('styles')
<style>
    .settings-card {
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
    }

    .settings-card:hover {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .form-input-enhanced {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 2px solid #d1d5db;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .form-input-enhanced:hover {
        border-color: #9ca3af;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .form-input-enhanced:focus {
        border-color: #A20030;
        box-shadow: 0 0 0 3px rgba(162, 0, 48, 0.1);
        background: #ffffff;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .toggle-slider {
        background-color: #A20030;
    }

    input:checked + .toggle-slider:before {
        transform: translateX(24px);
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-background">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Page Header -->
        <div class="bg-white shadow-lg rounded-2xl p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-cogs text-primary mr-3"></i>
                        System Settings
                    </h1>
                    <p class="text-gray-600 mt-2">Configure your ConnectApp system settings and preferences</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-shield-check mr-1"></i>
                        Super Admin Access
                    </span>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-lg shadow-sm" id="success-message">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3 text-green-600"></i>
                        <div class="flex-1">
                            <p class="font-medium">{{ session('success') }}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('success-message').style.display='none'" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg shadow-sm" id="error-message">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-red-600"></i>
                        <div class="flex-1">
                            <p class="font-medium">{{ session('error') }}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('error-message').style.display='none'" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg shadow-sm" id="validation-errors">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle mr-3 text-red-600 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="font-medium mb-2">Please correct the following errors:</p>
                            <ul class="list-disc list-inside space-y-1 text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" onclick="document.getElementById('validation-errors').style.display='none'" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Tab Navigation -->
            <div class="bg-white shadow-lg rounded-2xl border border-gray-100" x-data="{ activeTab: 'general' }">
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-8 pt-6">
                        <button type="button" @click="activeTab = 'general'"
                                :class="activeTab === 'general' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            <i class="fas fa-cog mr-2"></i>
                            General
                        </button>
                        <button type="button" @click="activeTab = 'email'"
                                :class="activeTab === 'email' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            <i class="fas fa-envelope mr-2"></i>
                            Email
                        </button>
                        <button type="button" @click="activeTab = 'notifications'"
                                :class="activeTab === 'notifications' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            <i class="fas fa-bell mr-2"></i>
                            Notifications
                        </button>
                        <button type="button" @click="activeTab = 'social'"
                                :class="activeTab === 'social' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            <i class="fas fa-share-alt mr-2"></i>
                            Social & Legal
                        </button>
                        <button type="button" @click="activeTab = 'features'"
                                :class="activeTab === 'features' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            <i class="fas fa-puzzle-piece mr-2"></i>
                            Features
                        </button>
                        <button type="button" @click="activeTab = 'payments'"
                                :class="activeTab === 'payments' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            <i class="fas fa-credit-card mr-2"></i>
                            Payments
                        </button>
                        <button type="button" @click="activeTab = 'apis'"
                                :class="activeTab === 'apis' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            <i class="fas fa-key mr-2"></i>
                            API Keys
                        </button>
                        <button type="button" @click="activeTab = 'limits'"
                                :class="activeTab === 'limits' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            <i class="fas fa-chart-line mr-2"></i>
                            Limits
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-8">

                    <!-- General Settings -->
                    <div x-show="activeTab === 'general'" x-transition:enter="transition ease-out duration-200">
                        <div class="settings-card bg-gray-50 rounded-xl p-6 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-info-circle text-primary mr-2"></i>
                                Application Information
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- App Name -->
                                <div>
                                    <label for="app_name" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-tag text-primary mr-2"></i>
                                        Application Name
                                    </label>
                                    <input type="text" name="app_name" id="app_name"
                                           value="{{ old('app_name', $settings['general']['app_name']) }}" required
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="Enter application name">
                                    @error('app_name')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- App Description -->
                                <div>
                                    <label for="app_description" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-align-left text-primary mr-2"></i>
                                        Application Description
                                    </label>
                                    <textarea name="app_description" id="app_description" rows="3"
                                              class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                              placeholder="Enter application description">{{ old('app_description', $settings['general']['app_description']) }}</textarea>
                                    @error('app_description')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- App Logo -->
                                <div>
                                    <label class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-image text-primary mr-2"></i>
                                        Application Logo
                                    </label>
                                    <div class="space-y-4">
                                        @if($settings['general']['app_logo'])
                                            <div class="flex items-center space-x-4">
                                                <img src="{{ Storage::url($settings['general']['app_logo']) }}"
                                                     alt="App Logo" class="w-16 h-16 object-contain rounded-lg border">
                                                <button type="button" onclick="deleteFile('app_logo')"
                                                        class="text-red-600 hover:text-red-800 text-sm">
                                                    <i class="fas fa-trash mr-1"></i>Remove
                                                </button>
                                            </div>
                                        @endif
                                        <input type="file" name="app_logo" accept="image/*"
                                               class="form-input-enhanced block w-full text-sm">
                                        <p class="text-xs text-gray-500">PNG, JPG, JPEG. Max size: 2MB</p>
                                    </div>
                                    @error('app_logo')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- App Favicon -->
                                <div>
                                    <label class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-star text-primary mr-2"></i>
                                        Application Favicon
                                    </label>
                                    <div class="space-y-4">
                                        @if($settings['general']['app_favicon'])
                                            <div class="flex items-center space-x-4">
                                                <img src="{{ Storage::url($settings['general']['app_favicon']) }}"
                                                     alt="App Favicon" class="w-8 h-8 object-contain rounded border">
                                                <button type="button" onclick="deleteFile('app_favicon')"
                                                        class="text-red-600 hover:text-red-800 text-sm">
                                                    <i class="fas fa-trash mr-1"></i>Remove
                                                </button>
                                            </div>
                                        @endif
                                        <input type="file" name="app_favicon" accept="image/*"
                                               class="form-input-enhanced block w-full text-sm">
                                        <p class="text-xs text-gray-500">PNG, ICO. Max size: 1MB</p>
                                    </div>
                                    @error('app_favicon')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Maintenance Mode -->
                            <div class="border-t pt-6">
                                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-tools text-primary mr-2"></i>
                                    Maintenance Mode
                                </h4>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Enable Maintenance Mode</label>
                                            <p class="text-xs text-gray-500">When enabled, only admins can access the site</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="maintenance_mode" value="true"
                                                   {{ old('maintenance_mode', $settings['general']['maintenance_mode']) === 'true' ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div>
                                        <label for="maintenance_message" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-comment-alt text-primary mr-2"></i>
                                            Maintenance Message
                                        </label>
                                        <textarea name="maintenance_message" id="maintenance_message" rows="3"
                                                  class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                                  placeholder="Message to display during maintenance">{{ old('maintenance_message', $settings['general']['maintenance_message']) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Company Information -->
                            <div class="border-t pt-6">
                                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-building text-primary mr-2"></i>
                                    Company Information
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Company Email -->
                                    <div>
                                        <label for="company_email" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-envelope text-primary mr-2"></i>
                                            Company Email Address
                                        </label>
                                        <input type="email" name="company_email" id="company_email"
                                               value="{{ old('company_email', $settings['general']['company_email'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="support@connectapp.com">
                                        @error('company_email')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Company Phone -->
                                    <div>
                                        <label for="company_phone" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-phone text-primary mr-2"></i>
                                            Company Phone Number
                                        </label>
                                        <input type="tel" name="company_phone" id="company_phone"
                                               value="{{ old('company_phone', $settings['general']['company_phone'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="+1 (555) 123-4567">
                                        @error('company_phone')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Company Address -->
                                    <div class="md:col-span-2">
                                        <label for="company_address" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-map-marker-alt text-primary mr-2"></i>
                                            Company Address
                                        </label>
                                        <textarea name="company_address" id="company_address" rows="3"
                                                  class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                                  placeholder="Enter company full address">{{ old('company_address', $settings['general']['company_address'] ?? '') }}</textarea>
                                        @error('company_address')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- App Versions -->
                            <div class="border-t pt-6">
                                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-code-branch text-primary mr-2"></i>
                                    Application Versions
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Mobile App Version -->
                                    <div>
                                        <label for="mobile_app_version" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-mobile-alt text-primary mr-2"></i>
                                            Mobile App Version
                                        </label>
                                        <input type="text" name="mobile_app_version" id="mobile_app_version"
                                               value="{{ old('mobile_app_version', $settings['general']['mobile_app_version'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="1.0.0">
                                        @error('mobile_app_version')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Web App Version -->
                                    <div>
                                        <label for="web_app_version" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-globe text-primary mr-2"></i>
                                            Web App Version
                                        </label>
                                        <input type="text" name="web_app_version" id="web_app_version"
                                               value="{{ old('web_app_version', $settings['general']['web_app_version'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="1.0.0">
                                        @error('web_app_version')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Settings -->
                    <div x-show="activeTab === 'email'" x-transition:enter="transition ease-out duration-200">
                        <div class="settings-card bg-gray-50 rounded-xl p-6 space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-mail-bulk text-primary mr-2"></i>
                                    SMTP Configuration
                                </h3>
                                <button type="button" onclick="testEmailConfiguration()"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                    <i class="fas fa-paper-plane mr-2"></i>Test Email
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- SMTP Host -->
                                <div>
                                    <label for="smtp_host" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-server text-primary mr-2"></i>
                                        SMTP Host
                                    </label>
                                    <input type="text" name="smtp_host" id="smtp_host"
                                           value="{{ old('smtp_host', $settings['email']['smtp_host']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="mail.example.com">
                                    @error('smtp_host')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- SMTP Port -->
                                <div>
                                    <label for="smtp_port" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-plug text-primary mr-2"></i>
                                        SMTP Port
                                    </label>
                                    <input type="number" name="smtp_port" id="smtp_port"
                                           value="{{ old('smtp_port', $settings['email']['smtp_port']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="587">
                                    @error('smtp_port')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- SMTP Username -->
                                <div>
                                    <label for="smtp_username" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-user text-primary mr-2"></i>
                                        SMTP Username
                                    </label>
                                    <input type="text" name="smtp_username" id="smtp_username"
                                           value="{{ old('smtp_username', $settings['email']['smtp_username']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="username@example.com">
                                    @error('smtp_username')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- SMTP Password -->
                                <div>
                                    <label for="smtp_password" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-key text-primary mr-2"></i>
                                        SMTP Password
                                    </label>
                                    <input type="password" name="smtp_password" id="smtp_password"
                                           value="{{ old('smtp_password', $settings['email']['smtp_password']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="Enter SMTP password">
                                    @error('smtp_password')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- SMTP Encryption -->
                                <div>
                                    <label for="smtp_encryption" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-shield-alt text-primary mr-2"></i>
                                        SMTP Encryption
                                    </label>
                                    <select name="smtp_encryption" id="smtp_encryption"
                                            class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg">
                                        <option value="tls" {{ old('smtp_encryption', $settings['email']['smtp_encryption']) === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ old('smtp_encryption', $settings['email']['smtp_encryption']) === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="none" {{ old('smtp_encryption', $settings['email']['smtp_encryption']) === 'none' ? 'selected' : '' }}>None</option>
                                    </select>
                                    @error('smtp_encryption')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Mail From Address -->
                                <div>
                                    <label for="mail_from_address" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-envelope text-primary mr-2"></i>
                                        From Email Address
                                    </label>
                                    <input type="email" name="mail_from_address" id="mail_from_address"
                                           value="{{ old('mail_from_address', $settings['email']['mail_from_address']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="noreply@connectapp.com">
                                    @error('mail_from_address')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Mail From Name -->
                                <div>
                                    <label for="mail_from_name" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-signature text-primary mr-2"></i>
                                        From Name
                                    </label>
                                    <input type="text" name="mail_from_name" id="mail_from_name"
                                           value="{{ old('mail_from_name', $settings['email']['mail_from_name']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="ConnectApp">
                                    @error('mail_from_name')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div x-show="activeTab === 'notifications'" x-transition:enter="transition ease-out duration-200">
                        <div class="settings-card bg-gray-50 rounded-xl p-6 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-bell text-primary mr-2"></i>
                                Push Notification Configuration
                            </h3>

                            <div class="space-y-6">
                                <!-- Firebase Server Key -->
                                <div>
                                    <label for="firebase_server_key" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fab fa-google text-primary mr-2"></i>
                                        Firebase Server Key
                                    </label>
                                    <textarea name="firebase_server_key" id="firebase_server_key" rows="3"
                                              class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                              placeholder="Enter Firebase Server Key">{{ old('firebase_server_key', $settings['notifications']['firebase_server_key']) }}</textarea>
                                    @error('firebase_server_key')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Firebase Sender ID -->
                                <div>
                                    <label for="firebase_sender_id" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fab fa-google text-primary mr-2"></i>
                                        Firebase Sender ID
                                    </label>
                                    <input type="text" name="firebase_sender_id" id="firebase_sender_id"
                                           value="{{ old('firebase_sender_id', $settings['notifications']['firebase_sender_id']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="Enter Firebase Sender ID">
                                    @error('firebase_sender_id')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Notification Toggles -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t pt-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Push Notifications</label>
                                            <p class="text-xs text-gray-500">Enable push notifications for the app</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="push_notifications_enabled" value="true"
                                                   {{ old('push_notifications_enabled', $settings['notifications']['push_notifications_enabled']) === 'true' ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Email Notifications</label>
                                            <p class="text-xs text-gray-500">Enable email notifications for the app</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="email_notifications_enabled" value="true"
                                                   {{ old('email_notifications_enabled', $settings['notifications']['email_notifications_enabled']) === 'true' ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social & Legal Settings -->
                    <div x-show="activeTab === 'social'" x-transition:enter="transition ease-out duration-200">
                        <div class="settings-card bg-gray-50 rounded-xl p-6 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-share-alt text-primary mr-2"></i>
                                Social Media & Legal Links
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Facebook URL -->
                                <div>
                                    <label for="facebook_url" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fab fa-facebook text-primary mr-2"></i>
                                        Facebook URL
                                    </label>
                                    <input type="url" name="facebook_url" id="facebook_url"
                                           value="{{ old('facebook_url', $settings['social']['facebook_url']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="https://facebook.com/connectapp">
                                    @error('facebook_url')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Twitter URL -->
                                <div>
                                    <label for="twitter_url" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fab fa-twitter text-primary mr-2"></i>
                                        Twitter URL
                                    </label>
                                    <input type="url" name="twitter_url" id="twitter_url"
                                           value="{{ old('twitter_url', $settings['social']['twitter_url']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="https://twitter.com/connectapp">
                                    @error('twitter_url')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Instagram URL -->
                                <div>
                                    <label for="instagram_url" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fab fa-instagram text-primary mr-2"></i>
                                        Instagram URL
                                    </label>
                                    <input type="url" name="instagram_url" id="instagram_url"
                                           value="{{ old('instagram_url', $settings['social']['instagram_url']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="https://instagram.com/connectapp">
                                    @error('instagram_url')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- LinkedIn URL -->
                                <div>
                                    <label for="linkedin_url" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fab fa-linkedin text-primary mr-2"></i>
                                        LinkedIn URL
                                    </label>
                                    <input type="url" name="linkedin_url" id="linkedin_url"
                                           value="{{ old('linkedin_url', $settings['social']['linkedin_url']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="https://linkedin.com/company/connectapp">
                                    @error('linkedin_url')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Privacy Policy URL -->
                                <div>
                                    <label for="privacy_policy_url" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-user-shield text-primary mr-2"></i>
                                        Privacy Policy URL
                                    </label>
                                    <input type="url" name="privacy_policy_url" id="privacy_policy_url"
                                           value="{{ old('privacy_policy_url', $settings['social']['privacy_policy_url']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="https://connectapp.com/privacy">
                                    @error('privacy_policy_url')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Terms of Service URL -->
                                <div>
                                    <label for="terms_of_service_url" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-file-contract text-primary mr-2"></i>
                                        Terms of Service URL
                                    </label>
                                    <input type="url" name="terms_of_service_url" id="terms_of_service_url"
                                           value="{{ old('terms_of_service_url', $settings['social']['terms_of_service_url']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                           placeholder="https://connectapp.com/terms">
                                    @error('terms_of_service_url')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature Settings -->
                    <div x-show="activeTab === 'features'" x-transition:enter="transition ease-out duration-200">
                        <div class="settings-card bg-gray-50 rounded-xl p-6 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-puzzle-piece text-primary mr-2"></i>
                                Feature Toggles
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">User Registration</label>
                                        <p class="text-xs text-gray-500">Allow new users to register</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="user_registration_enabled" value="true"
                                               {{ old('user_registration_enabled', $settings['features']['user_registration_enabled']) === 'true' ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Email Verification</label>
                                        <p class="text-xs text-gray-500">Require email verification for new accounts</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="email_verification_required" value="true"
                                               {{ old('email_verification_required', $settings['features']['email_verification_required']) === 'true' ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Social Login</label>
                                        <p class="text-xs text-gray-500">Enable social media login options</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="social_login_enabled" value="true"
                                               {{ old('social_login_enabled', $settings['features']['social_login_enabled']) === 'true' ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Subscription Features</label>
                                        <p class="text-xs text-gray-500">Enable premium subscription features</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="subscription_features_enabled" value="true"
                                               {{ old('subscription_features_enabled', $settings['features']['subscription_features_enabled']) === 'true' ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Streaming Features</label>
                                        <p class="text-xs text-gray-500">Enable live streaming functionality</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="streaming_features_enabled" value="true"
                                               {{ old('streaming_features_enabled', $settings['features']['streaming_features_enabled']) === 'true' ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Content Moderation</label>
                                        <p class="text-xs text-gray-500">Enable automatic content moderation</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="content_moderation_enabled" value="true"
                                               {{ old('content_moderation_enabled', $settings['features']['content_moderation_enabled']) === 'true' ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Gateway Settings -->
                    <div x-show="activeTab === 'payments'" x-transition:enter="transition ease-out duration-200">
                        <div class="settings-card bg-gray-50 rounded-xl p-6 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-credit-card text-primary mr-2"></i>
                                Payment Gateway Configuration
                            </h3>

                            <!-- Nomba Credentials -->
                            <div class="border-b pb-6">
                                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-university text-primary mr-2"></i>
                                    Nomba Credentials
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="nomba_client_id" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-id-card text-primary mr-2"></i>
                                            Client ID
                                        </label>
                                        <input type="text" name="nomba_client_id" id="nomba_client_id"
                                               value="{{ old('nomba_client_id', $settings['payments']['nomba_client_id'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="Enter Nomba Client ID">
                                        @error('nomba_client_id')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="nomba_private_key" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-key text-primary mr-2"></i>
                                            Private Key
                                        </label>
                                        <input type="password" name="nomba_private_key" id="nomba_private_key"
                                               value="{{ old('nomba_private_key', $settings['payments']['nomba_private_key'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="Enter Nomba Private Key">
                                        @error('nomba_private_key')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="nomba_account_id" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-hashtag text-primary mr-2"></i>
                                            Account ID
                                        </label>
                                        <input type="text" name="nomba_account_id" id="nomba_account_id"
                                               value="{{ old('nomba_account_id', $settings['payments']['nomba_account_id'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="Enter Nomba Account ID">
                                        @error('nomba_account_id')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Enable Nomba</label>
                                            <p class="text-xs text-gray-500">Enable Nomba payment gateway</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="nomba_enabled" value="true"
                                                   {{ old('nomba_enabled', $settings['payments']['nomba_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Paystack Credentials -->
                            <div class="border-b pb-6">
                                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-credit-card text-primary mr-2"></i>
                                    Paystack Credentials
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="paystack_public_key" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-unlock text-primary mr-2"></i>
                                            Public Key
                                        </label>
                                        <input type="text" name="paystack_public_key" id="paystack_public_key"
                                               value="{{ old('paystack_public_key', $settings['payments']['paystack_public_key'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="pk_test_...">
                                        @error('paystack_public_key')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="paystack_secret_key" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-lock text-primary mr-2"></i>
                                            Secret Key
                                        </label>
                                        <input type="password" name="paystack_secret_key" id="paystack_secret_key"
                                               value="{{ old('paystack_secret_key', $settings['payments']['paystack_secret_key'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="sk_test_...">
                                        @error('paystack_secret_key')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Enable Paystack</label>
                                            <p class="text-xs text-gray-500">Enable Paystack payment gateway</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="paystack_enabled" value="true"
                                                   {{ old('paystack_enabled', $settings['payments']['paystack_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Stripe Credentials -->
                            <div>
                                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fab fa-stripe text-primary mr-2"></i>
                                    Stripe Credentials
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="stripe_public_key" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-unlock text-primary mr-2"></i>
                                            Publishable Key
                                        </label>
                                        <input type="text" name="stripe_public_key" id="stripe_public_key"
                                               value="{{ old('stripe_public_key', $settings['payments']['stripe_public_key'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="pk_test_...">
                                        @error('stripe_public_key')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="stripe_secret_key" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-lock text-primary mr-2"></i>
                                            Secret Key
                                        </label>
                                        <input type="password" name="stripe_secret_key" id="stripe_secret_key"
                                               value="{{ old('stripe_secret_key', $settings['payments']['stripe_secret_key'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="sk_test_...">
                                        @error('stripe_secret_key')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Enable Stripe</label>
                                            <p class="text-xs text-gray-500">Enable Stripe payment gateway</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="stripe_enabled" value="true"
                                                   {{ old('stripe_enabled', $settings['payments']['stripe_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- API Keys Settings -->
                    <div x-show="activeTab === 'apis'" x-transition:enter="transition ease-out duration-200">
                        <div class="settings-card bg-gray-50 rounded-xl p-6 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-key text-primary mr-2"></i>
                                API Keys Configuration
                            </h3>

                            <!-- Agora Keys -->
                            <div class="border-b pb-6">
                                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-video text-primary mr-2"></i>
                                    Agora.io Configuration
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="agora_app_id" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-id-badge text-primary mr-2"></i>
                                            App ID
                                        </label>
                                        <input type="text" name="agora_app_id" id="agora_app_id"
                                               value="{{ old('agora_app_id', $settings['apis']['agora_app_id'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="Enter Agora App ID">
                                        @error('agora_app_id')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="agora_app_certificate" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-certificate text-primary mr-2"></i>
                                            App Certificate
                                        </label>
                                        <input type="password" name="agora_app_certificate" id="agora_app_certificate"
                                               value="{{ old('agora_app_certificate', $settings['apis']['agora_app_certificate'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="Enter Agora App Certificate">
                                        @error('agora_app_certificate')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Pusher Keys -->
                            <div>
                                <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-broadcast-tower text-primary mr-2"></i>
                                    Pusher Configuration
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="pusher_app_id" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-id-badge text-primary mr-2"></i>
                                            App ID
                                        </label>
                                        <input type="text" name="pusher_app_id" id="pusher_app_id"
                                               value="{{ old('pusher_app_id', $settings['apis']['pusher_app_id'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="Enter Pusher App ID">
                                        @error('pusher_app_id')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="pusher_app_key" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-key text-primary mr-2"></i>
                                            App Key
                                        </label>
                                        <input type="text" name="pusher_app_key" id="pusher_app_key"
                                               value="{{ old('pusher_app_key', $settings['apis']['pusher_app_key'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="Enter Pusher App Key">
                                        @error('pusher_app_key')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="pusher_app_secret" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-lock text-primary mr-2"></i>
                                            App Secret
                                        </label>
                                        <input type="password" name="pusher_app_secret" id="pusher_app_secret"
                                               value="{{ old('pusher_app_secret', $settings['apis']['pusher_app_secret'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="Enter Pusher App Secret">
                                        @error('pusher_app_secret')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="pusher_app_cluster" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                            <i class="fas fa-server text-primary mr-2"></i>
                                            App Cluster
                                        </label>
                                        <input type="text" name="pusher_app_cluster" id="pusher_app_cluster"
                                               value="{{ old('pusher_app_cluster', $settings['apis']['pusher_app_cluster'] ?? '') }}"
                                               class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500"
                                               placeholder="us2">
                                        @error('pusher_app_cluster')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Limits Settings -->
                    <div x-show="activeTab === 'limits'" x-transition:enter="transition ease-out duration-200">
                        <div class="settings-card bg-gray-50 rounded-xl p-6 space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-chart-line text-primary mr-2"></i>
                                System Limits & Quotas
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Max File Upload Size -->
                                <div>
                                    <label for="max_file_upload_size" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-file-upload text-primary mr-2"></i>
                                        Max File Upload Size (MB)
                                    </label>
                                    <input type="number" name="max_file_upload_size" id="max_file_upload_size" min="1" max="100"
                                           value="{{ old('max_file_upload_size', $settings['limits']['max_file_upload_size']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500">
                                    @error('max_file_upload_size')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Max Video Duration -->
                                <div>
                                    <label for="max_video_duration" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-video text-primary mr-2"></i>
                                        Max Video Duration (seconds)
                                    </label>
                                    <input type="number" name="max_video_duration" id="max_video_duration" min="30" max="3600"
                                           value="{{ old('max_video_duration', $settings['limits']['max_video_duration']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500">
                                    @error('max_video_duration')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Max Posts Per Day -->
                                <div>
                                    <label for="max_posts_per_day" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-newspaper text-primary mr-2"></i>
                                        Max Posts Per Day
                                    </label>
                                    <input type="number" name="max_posts_per_day" id="max_posts_per_day" min="1" max="1000"
                                           value="{{ old('max_posts_per_day', $settings['limits']['max_posts_per_day']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500">
                                    @error('max_posts_per_day')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Max Story Duration -->
                                <div>
                                    <label for="max_story_duration" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-clock text-primary mr-2"></i>
                                        Max Story Duration (seconds)
                                    </label>
                                    <input type="number" name="max_story_duration" id="max_story_duration" min="5" max="300"
                                           value="{{ old('max_story_duration', $settings['limits']['max_story_duration']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500">
                                    @error('max_story_duration')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Max Concurrent Streams -->
                                <div>
                                    <label for="max_concurrent_streams" class="flex items-center text-sm font-semibold text-gray-800 mb-3">
                                        <i class="fas fa-broadcast-tower text-primary mr-2"></i>
                                        Max Concurrent Streams
                                    </label>
                                    <input type="number" name="max_concurrent_streams" id="max_concurrent_streams" min="1" max="100"
                                           value="{{ old('max_concurrent_streams', $settings['limits']['max_concurrent_streams']) }}"
                                           class="form-input-enhanced block w-full px-4 py-3 text-gray-900 rounded-lg placeholder-gray-500">
                                    @error('max_concurrent_streams')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Save Button -->
                <div class="px-8 py-6 border-t border-gray-200 bg-gray-50">
                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-primary hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i>
                            Save All Settings
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>

<!-- Test Email Modal -->
<div id="testEmailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Test Email Configuration</h3>
        <div class="space-y-4">
            <div>
                <label for="test_email" class="block text-sm font-medium text-gray-700 mb-2">Test Email Address</label>
                <input type="email" id="test_email"
                       class="form-input-enhanced block w-full px-3 py-2 text-sm"
                       placeholder="Enter email to send test">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeTestEmailModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                    Cancel
                </button>
                <button type="button" onclick="sendTestEmail()"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-red-700">
                    Send Test Email
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Test Email Functions
    function testEmailConfiguration() {
        document.getElementById('testEmailModal').classList.remove('hidden');
        document.getElementById('testEmailModal').classList.add('flex');
    }

    function closeTestEmailModal() {
        document.getElementById('testEmailModal').classList.add('hidden');
        document.getElementById('testEmailModal').classList.remove('flex');
    }

    function sendTestEmail() {
        const email = document.getElementById('test_email').value;
        if (!email) {
            alert('Please enter an email address');
            return;
        }

        fetch('{{ route("admin.settings.test-email") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ test_email: email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test email sent successfully!');
            } else {
                alert('Failed to send test email: ' + data.message);
            }
            closeTestEmailModal();
        })
        .catch(error => {
            alert('Error sending test email: ' + error.message);
            closeTestEmailModal();
        });
    }

    // Delete File Function
    function deleteFile(settingKey) {
        if (!confirm('Are you sure you want to delete this file?')) {
            return;
        }

        fetch('{{ route("admin.settings.delete-file") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ setting_key: settingKey })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete file: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error deleting file: ' + error.message);
        });
    }

    // Toggle switch handling
    document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            // Add hidden input for unchecked state
            let hiddenInput = this.parentNode.querySelector('input[type="hidden"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = this.name;
                this.parentNode.appendChild(hiddenInput);
            }
            hiddenInput.value = this.checked ? 'true' : 'false';
        });
    });
</script>
@endpush
