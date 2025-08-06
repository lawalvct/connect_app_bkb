@extends('admin.layouts.app')

@section('title', 'Email Templates')
@section('page-title', 'Email Templates')

@section('content')
<div x-data="emailTemplateManager()" x-init="init()">
    <!-- Header Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Email Templates</h1>
                    <p class="mt-1 text-sm text-gray-600">Create and manage email notification templates</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <button @click="openCreateModal()"
                            class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        New Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Templates List -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Filters -->
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <div class="relative">
                                <input type="text"
                                       x-model="search"
                                       @input.debounce.300ms="loadTemplates()"
                                       placeholder="Search templates..."
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <select x-model="statusFilter"
                                @change="loadTemplates()"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                            <option value="">All Templates</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">Inactive Only</option>
                        </select>

                        <!-- Refresh Button -->
                        <button @click="loadTemplates()"
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- Templates Grid -->
                <div class="p-6">
                    <template x-if="loading">
                        <div class="flex items-center justify-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                            <span class="ml-2 text-gray-600">Loading templates...</span>
                        </div>
                    </template>

                    <template x-if="!loading && templates.length === 0">
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No templates found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating your first email template.</p>
                            <div class="mt-6">
                                <button @click="openCreateModal()"
                                        class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-red-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Create Template
                                </button>
                            </div>
                        </div>
                    </template>

                    <div x-show="!loading && templates.length > 0" class="space-y-4">
                        <template x-for="template in templates" :key="template.id">
                            <div class="border border-gray-200 rounded-lg p-6 hover:border-gray-300 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <!-- Template Header -->
                                        <div class="flex items-center space-x-3 mb-3">
                                            <h3 class="text-lg font-semibold text-gray-900" x-text="template.name"></h3>
                                            <span :class="template.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                <span x-text="template.is_active ? 'Active' : 'Inactive'"></span>
                                            </span>
                                        </div>

                                        <!-- Subject -->
                                        <p class="text-sm text-gray-600 mb-2">
                                            <span class="font-medium">Subject:</span>
                                            <span x-text="template.subject"></span>
                                        </p>

                                        <!-- Description -->
                                        <p x-show="template.description"
                                           class="text-sm text-gray-500 mb-3"
                                           x-text="template.description"></p>

                                        <!-- Variables -->
                                        <div x-show="template.variables && template.variables.length > 0" class="mb-3">
                                            <p class="text-xs font-medium text-gray-700 mb-2">Available Variables:</p>
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="variable in template.variables" :key="variable">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                                                        @{{ <span x-text="variable"></span> @}}
                                                    </span>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Meta Info -->
                                        <div class="flex items-center space-x-4 text-xs text-gray-500">
                                            <span>
                                                Created: <span x-text="formatDate(template.created_at)"></span>
                                            </span>
                                            <span x-show="template.updated_at !== template.created_at">
                                                Updated: <span x-text="formatDate(template.updated_at)"></span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        <button @click="previewTemplate(template)"
                                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                title="Preview">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>

                                        <button @click="editTemplate(template)"
                                                class="p-2 text-gray-400 hover:text-primary hover:bg-red-50 rounded-lg transition-colors"
                                                title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>

                                        <button @click="toggleStatus(template)"
                                                class="p-2 text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 rounded-lg transition-colors"
                                                :title="template.is_active ? 'Deactivate' : 'Activate'">
                                            <svg x-show="template.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <svg x-show="!template.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m-10-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>

                                        <button @click="deleteTemplate(template)"
                                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Pagination -->
                    <div x-show="pagination.total > pagination.per_page" class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span x-text="pagination.from"></span> to <span x-text="pagination.to"></span>
                            of <span x-text="pagination.total"></span> results
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="loadTemplates(pagination.current_page - 1)"
                                    :disabled="pagination.current_page <= 1"
                                    class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Previous
                            </button>
                            <button @click="loadTemplates(pagination.current_page + 1)"
                                    :disabled="pagination.current_page >= pagination.last_page"
                                    class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Stats Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total Templates</span>
                        <span class="text-lg font-bold text-gray-900" x-text="stats.total || 0"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Active</span>
                        <span class="text-lg font-bold text-green-600" x-text="stats.active || 0"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Inactive</span>
                        <span class="text-lg font-bold text-red-600" x-text="stats.inactive || 0"></span>
                    </div>
                </div>
            </div>

            <!-- Variable Guide -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Common Variables</h3>
                <div class="space-y-3">
                    <div class="text-sm">
                        <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">@{{user_name@}}</code>
                        <p class="text-gray-600 mt-1">User's display name</p>
                    </div>
                    <div class="text-sm">
                        <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">@{{user_email@}}</code>
                        <p class="text-gray-600 mt-1">User's email address</p>
                    </div>
                    <div class="text-sm">
                        <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">@{{app_name@}}</code>
                        <p class="text-gray-600 mt-1">Application name</p>
                    </div>
                    <div class="text-sm">
                        <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">@{{app_url@}}</code>
                        <p class="text-gray-600 mt-1">Application URL</p>
                    </div>
                    <div class="text-sm">
                        <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">@{{support_email@}}</code>
                        <p class="text-gray-600 mt-1">Support contact email</p>
                    </div>
                </div>
            </div>

            <!-- Quick Templates -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Start</h3>
                <div class="space-y-2">
                    <button @click="createFromTemplate('welcome')"
                            class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <div class="font-medium text-gray-900">Welcome Email</div>
                        <div class="text-sm text-gray-500">New user onboarding</div>
                    </button>
                    <button @click="createFromTemplate('verification')"
                            class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <div class="font-medium text-gray-900">Email Verification</div>
                        <div class="text-sm text-gray-500">Account verification</div>
                    </button>
                    <button @click="createFromTemplate('password_reset')"
                            class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <div class="font-medium text-gray-900">Password Reset</div>
                        <div class="text-sm text-gray-500">Reset password link</div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="closeModal()">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-900">
                    <span x-text="isEditing ? 'Edit Template' : 'Create New Template'"></span>
                </h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form @submit.prevent="saveTemplate()" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Template Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Template Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               x-model="form.name"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Enter template name"
                               required>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select x-model="form.is_active"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option :value="true">Active</option>
                            <option :value="false">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Subject -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email Subject <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           x-model="form.subject"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                           placeholder="Enter email subject"
                           required>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <input type="text"
                           x-model="form.description"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                           placeholder="Brief description of this template">
                </div>

                <!-- Variables -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Template Variables</label>
                    <div class="flex gap-2 mb-3">
                        <input type="text"
                               x-model="newVariable"
                               @keydown.enter.prevent="addVariable()"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Add variable (e.g., user_name)">
                        <button type="button"
                                @click="addVariable()"
                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            Add
                        </button>
                    </div>
                    <div x-show="form.variables.length > 0" class="flex flex-wrap gap-2">
                        <template x-for="(variable, index) in form.variables" :key="index">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                @{{ <span x-text="variable"></span> @}}
                                <button type="button"
                                        @click="removeVariable(index)"
                                        class="ml-2 text-blue-600 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Content -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email Content <span class="text-red-500">*</span>
                    </label>
                    <textarea x-model="form.content"
                              rows="12"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary font-mono text-sm"
                              placeholder="Enter email content (HTML supported)"
                              required></textarea>
                    <p class="mt-2 text-sm text-gray-500">You can use HTML tags and template variables like @{{user_name@}}</p>
                </div>

                <!-- Modal Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <button type="button"
                            @click="closeModal()"
                            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="saving"
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span x-show="!saving" x-text="isEditing ? 'Update Template' : 'Create Template'"></span>
                        <span x-show="saving" class="flex items-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Modal -->
    <div x-show="showPreview"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showPreview = false">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-900">Template Preview</h3>
                <button @click="showPreview = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-6">
                <!-- Subject Preview -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Subject:</h4>
                    <p class="text-gray-700" x-text="previewData.subject"></p>
                </div>

                <!-- Content Preview -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="font-semibold text-gray-900 mb-4">Content:</h4>
                    <div x-html="previewData.content" class="prose max-w-none"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div x-show="showToast"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed top-4 right-4 z-50 max-w-sm">
        <div :class="toastType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'"
             class="border px-4 py-3 rounded-lg shadow-lg">
            <div class="flex items-center">
                <svg x-show="toastType === 'success'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="toastType === 'error'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-text="toastMessage"></span>
            </div>
        </div>
    </div>
</div>

<script>
function emailTemplateManager() {
    return {
        // Data
        templates: [],
        stats: { total: 0, active: 0, inactive: 0 },
        loading: false,
        saving: false,

        // Filters
        search: '',
        statusFilter: '',

        // Pagination
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: 0,
            to: 0
        },

        // Modals
        showModal: false,
        showPreview: false,
        isEditing: false,

        // Forms
        form: this.getEmptyForm(),
        newVariable: '',

        // Preview
        previewData: {},

        // Toast
        showToast: false,
        toastMessage: '',
        toastType: 'success',

        // Initialize
        init() {
            this.loadTemplates();
            this.loadStats();
        },

        // Get empty form structure
        getEmptyForm() {
            return {
                id: null,
                name: '',
                subject: '',
                content: '',
                description: '',
                is_active: true,
                variables: []
            };
        },

        // Load templates
        async loadTemplates(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    search: this.search,
                    status: this.statusFilter
                });

                const response = await fetch(`/admin/api/notifications/email/templates?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.templates = data.templates.data || [];
                    this.pagination = {
                        current_page: data.templates.current_page || 1,
                        last_page: data.templates.last_page || 1,
                        per_page: data.templates.per_page || 20,
                        total: data.templates.total || 0,
                        from: data.templates.from || 0,
                        to: data.templates.to || 0
                    };
                } else {
                    this.showError('Failed to load templates');
                }
            } catch (error) {
                console.error('Load templates error:', error);
                this.showError('Failed to load templates');
            } finally {
                this.loading = false;
            }
        },

        // Load stats
        async loadStats() {
            try {
                const response = await fetch('/admin/api/notifications/email/stats');
                const data = await response.json();
                if (data.success) {
                    this.stats = data.stats || { total: 0, active: 0, inactive: 0 };
                }
            } catch (error) {
                console.error('Load stats error:', error);
            }
        },

        // Modal methods
        openCreateModal() {
            this.form = this.getEmptyForm();
            this.isEditing = false;
            this.showModal = true;
        },

        editTemplate(template) {
            this.form = {
                id: template.id,
                name: template.name,
                subject: template.subject,
                content: template.content,
                description: template.description || '',
                is_active: template.is_active,
                variables: template.variables || []
            };
            this.isEditing = true;
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.form = this.getEmptyForm();
            this.newVariable = '';
            this.isEditing = false;
        },

        // Variable management
        addVariable() {
            const variable = this.newVariable.trim();
            if (variable && !this.form.variables.includes(variable)) {
                this.form.variables.push(variable);
                this.newVariable = '';
            }
        },

        removeVariable(index) {
            this.form.variables.splice(index, 1);
        },

        // Template actions
        async saveTemplate() {
            this.saving = true;
            try {
                const url = this.isEditing
                    ? `/admin/api/notifications/email/templates/${this.form.id}`
                    : '/admin/api/notifications/email/templates';

                const method = this.isEditing ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(data.message || 'Template saved successfully');
                    this.closeModal();
                    this.loadTemplates();
                    this.loadStats();
                } else {
                    this.showError(data.message || 'Failed to save template');
                }
            } catch (error) {
                console.error('Save template error:', error);
                this.showError('Failed to save template');
            } finally {
                this.saving = false;
            }
        },

        async toggleStatus(template) {
            try {
                const response = await fetch(`/admin/api/notifications/email/templates/${template.id}/toggle`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    template.is_active = !template.is_active;
                    this.showSuccess(`Template ${template.is_active ? 'activated' : 'deactivated'}`);
                    this.loadStats();
                } else {
                    this.showError(data.message || 'Failed to update status');
                }
            } catch (error) {
                console.error('Toggle status error:', error);
                this.showError('Failed to update status');
            }
        },

        async deleteTemplate(template) {
            if (!confirm(`Are you sure you want to delete "${template.name}"?`)) return;

            try {
                const response = await fetch(`/admin/api/notifications/email/templates/${template.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('Template deleted successfully');
                    this.loadTemplates();
                    this.loadStats();
                } else {
                    this.showError(data.message || 'Failed to delete template');
                }
            } catch (error) {
                console.error('Delete template error:', error);
                this.showError('Failed to delete template');
            }
        },

        // Preview
        previewTemplate(template) {
            this.previewData = {
                subject: template.subject,
                content: template.content
            };
            this.showPreview = true;
        },

        // Quick templates
        createFromTemplate(type) {
            const varStart = '{{';
            const varEnd = '}}';

            const templates = {
                welcome: {
                    name: 'Welcome Email',
                    subject: `Welcome to ${varStart}app_name${varEnd}!`,
                    description: 'Welcome email for new users',
                    content: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h1 style="color: #A20030;">Welcome to ${varStart}app_name${varEnd}, ${varStart}user_name${varEnd}!</h1>
    <p>We're excited to have you on board. Here are some next steps to get you started:</p>
    <ul>
        <li>Complete your profile</li>
        <li>Connect with friends</li>
        <li>Start sharing your moments</li>
    </ul>
    <p>If you have any questions, feel free to contact us at ${varStart}support_email${varEnd}.</p>
    <p>Best regards,<br>The ${varStart}app_name${varEnd} Team</p>
</div>`,
                    variables: ['app_name', 'user_name', 'support_email']
                },
                verification: {
                    name: 'Email Verification',
                    subject: 'Verify your email address',
                    description: 'Email verification template',
                    content: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h1>Verify your email address</h1>
    <p>Hi ${varStart}user_name${varEnd},</p>
    <p>Please click the button below to verify your email address:</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="${varStart}verification_url${varEnd}" style="background-color: #A20030; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Verify Email</a>
    </div>
    <p>If you didn't create an account, you can safely ignore this email.</p>
    <p>Best regards,<br>The ${varStart}app_name${varEnd} Team</p>
</div>`,
                    variables: ['user_name', 'verification_url', 'app_name']
                },
                password_reset: {
                    name: 'Password Reset',
                    subject: 'Reset your password',
                    description: 'Password reset email template',
                    content: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h1>Reset your password</h1>
    <p>Hi ${varStart}user_name${varEnd},</p>
    <p>You requested a password reset. Click the button below to set a new password:</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="${varStart}reset_url${varEnd}" style="background-color: #A20030; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Reset Password</a>
    </div>
    <p>This link will expire in ${varStart}expiry_time${varEnd} minutes.</p>
    <p>If you didn't request this, you can safely ignore this email.</p>
    <p>Best regards,<br>The ${varStart}app_name${varEnd} Team</p>
</div>`,
                    variables: ['user_name', 'reset_url', 'expiry_time', 'app_name']
                }
            };

            if (templates[type]) {
                this.form = { ...this.getEmptyForm(), ...templates[type] };
                this.isEditing = false;
                this.showModal = true;
            }
        },

        // Utility methods
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        showSuccess(message) {
            this.toastMessage = message;
            this.toastType = 'success';
            this.showToast = true;
            setTimeout(() => this.showToast = false, 5000);
        },

        showError(message) {
            this.toastMessage = message;
            this.toastType = 'error';
            this.showToast = true;
            setTimeout(() => this.showToast = false, 5000);
        }
    }
}
</script>
@endsection
