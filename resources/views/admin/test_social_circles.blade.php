@extends('admin.layouts.app')

@section('title', 'Social Circles Test')

@section('content')
    <div x-data="socialCirclesTest()" x-init="init()">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold mb-6">Social Circles Loading Test</h1>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <strong>Purpose:</strong> Test the social circles API endpoint that the admin interface uses
            </div>

            <div class="flex space-x-4 mb-6">
                <button @click="testSocialCircles()" :disabled="loading"
                        class="bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white px-4 py-2 rounded">
                    <span x-show="!loading">Test Social Circles API</span>
                    <span x-show="loading">Loading...</span>
                </button>

                <button @click="clearLogs()"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    Clear Logs
                </button>
            </div>

            <div class="rounded-lg p-4 mb-6"
                 :class="status === 'success' ? 'bg-green-50 border border-green-200 text-green-800' :
                         status === 'error' ? 'bg-red-50 border border-red-200 text-red-800' :
                         'bg-blue-50 border border-blue-200 text-blue-800'">
                <strong>Status:</strong> <span x-text="statusMessage"></span>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <label for="socialCircleSelect" class="block text-sm font-medium text-gray-700 mb-2">
                    <strong>Social Circles Dropdown Test:</strong>
                </label>
                <select id="socialCircleSelect" x-model="selectedCircle"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Users</option>
                    <option value="has_circles">With Circles</option>
                    <option value="no_circles">No Circles</option>
                    <optgroup label="Specific Circles">
                        <template x-for="circle in socialCircles" :key="circle.id">
                            <option :value="circle.id" x-text="circle.name"></option>
                        </template>
                    </optgroup>
                </select>
                <div class="mt-2 text-sm text-gray-600" x-text="`Selected: ${selectedCircle || 'None'}`"></div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <strong class="block text-lg font-medium mb-4">Social Circles Data:</strong>
                <div x-show="socialCircles.length > 0">
                    <p class="mb-3">Found <span x-text="socialCircles.length" class="font-bold text-green-600"></span> social circles:</p>
                    <ul class="space-y-1">
                        <template x-for="circle in socialCircles" :key="circle.id">
                            <li class="text-sm bg-gray-50 p-2 rounded"
                                x-text="`ID: ${circle.id}, Name: ${circle.name}, Color: ${circle.color}`"></li>
                        </template>
                    </ul>
                </div>
                <div x-show="socialCircles.length === 0" class="text-red-600">
                    No social circles loaded
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <strong class="block text-lg font-medium mb-4">Debug Logs:</strong>
                <div class="bg-gray-100 border rounded p-4 h-64 overflow-y-auto text-sm font-mono"
                     x-html="logs" id="logs"></div>
            </div>
        </div>
    </div>

    <script>
        function socialCirclesTest() {
            return {
                loading: false,
                status: 'info',
                statusMessage: 'Ready to test',
                socialCircles: [],
                selectedCircle: '',
                logs: '',

                init() {
                    this.log('Page loaded. Ready to test social circles API.');

                    // Auto-test on load
                    setTimeout(() => {
                        this.testSocialCircles();
                    }, 1000);
                },

                log(message) {
                    const timestamp = new Date().toLocaleTimeString();
                    this.logs += `[${timestamp}] ${message}<br>`;

                    // Auto-scroll to bottom
                    setTimeout(() => {
                        const logsElement = document.getElementById('logs');
                        if (logsElement) {
                            logsElement.scrollTop = logsElement.scrollHeight;
                        }
                    }, 100);
                },

                clearLogs() {
                    this.logs = '';
                },

                async testSocialCircles() {
                    this.loading = true;
                    this.status = 'info';
                    this.statusMessage = 'Testing...';
                    this.log('Starting social circles API test...');

                    try {
                        this.log('Making fetch request to /admin/api/social-circles');

                        const response = await fetch('/admin/api/social-circles', {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            credentials: 'same-origin'
                        });

                        this.log(`Response status: ${response.status} ${response.statusText}`);

                        // Log response headers
                        const headers = {};
                        for (let [key, value] of response.headers.entries()) {
                            headers[key] = value;
                        }
                        this.log(`Response headers: ${JSON.stringify(headers, null, 2)}`);

                        if (!response.ok) {
                            const errorText = await response.text();
                            this.log(`Error response body: ${errorText}`);
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }

                        const data = await response.json();
                        this.log(`Response data: ${JSON.stringify(data, null, 2)}`);

                        if (data.success && data.social_circles) {
                            this.socialCircles = data.social_circles;
                            this.status = 'success';
                            this.statusMessage = `Successfully loaded ${this.socialCircles.length} social circles`;
                            this.log(`SUCCESS: Loaded ${this.socialCircles.length} social circles`);
                        } else {
                            this.status = 'error';
                            this.statusMessage = 'API response format unexpected';
                            this.log('ERROR: Response does not contain expected social_circles data');
                        }

                    } catch (error) {
                        this.log(`ERROR: ${error.message}`);
                        this.status = 'error';
                        this.statusMessage = `Failed: ${error.message}`;
                        this.socialCircles = [];
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
@endsection
