@extends('admin.layouts.app')

@section('title', 'SMS Settings')
@section('page-title', 'SMS Settings')

@section('content')
<div x-data="smsSettings()" x-init="init()">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- SMS Configuration -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Provider Configuration -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">SMS Provider Configuration</h3>
                    <p class="text-sm text-gray-500 mt-1">Configure your SMS service provider</p>
                </div>

                <form @submit.prevent="saveConfiguration()" class="p-6 space-y-6">
                    <!-- Provider Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SMS Provider *
                        </label>
                        <select x-model="config.provider"
                                @change="updateProviderFields()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Select Provider</option>
                            <option value="twilio">Twilio</option>
                            <option value="nexmo">Vonage (Nexmo)</option>
                            <option value="aws_sns">AWS SNS</option>
                            <option value="custom">Custom API</option>
                        </select>
                    </div>

                    <!-- Twilio Configuration -->
                    <div x-show="config.provider === 'twilio'" class="space-y-4">
                        <div>
                            <label for="twilio_sid" class="block text-sm font-medium text-gray-700 mb-2">
                                Account SID *
                            </label>
                            <input type="text"
                                   id="twilio_sid"
                                   x-model="config.twilio.account_sid"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter Twilio Account SID">
                        </div>
                        <div>
                            <label for="twilio_token" class="block text-sm font-medium text-gray-700 mb-2">
                                Auth Token *
                            </label>
                            <input type="password"
                                   id="twilio_token"
                                   x-model="config.twilio.auth_token"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter Twilio Auth Token">
                        </div>
                        <div>
                            <label for="twilio_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                From Phone Number *
                            </label>
                            <input type="text"
                                   id="twilio_phone"
                                   x-model="config.twilio.from_number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="+1234567890">
                        </div>
                    </div>

                    <!-- Nexmo Configuration -->
                    <div x-show="config.provider === 'nexmo'" class="space-y-4">
                        <div>
                            <label for="nexmo_key" class="block text-sm font-medium text-gray-700 mb-2">
                                API Key *
                            </label>
                            <input type="text"
                                   id="nexmo_key"
                                   x-model="config.nexmo.api_key"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter Vonage API Key">
                        </div>
                        <div>
                            <label for="nexmo_secret" class="block text-sm font-medium text-gray-700 mb-2">
                                API Secret *
                            </label>
                            <input type="password"
                                   id="nexmo_secret"
                                   x-model="config.nexmo.api_secret"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter Vonage API Secret">
                        </div>
                        <div>
                            <label for="nexmo_from" class="block text-sm font-medium text-gray-700 mb-2">
                                From Name/Number *
                            </label>
                            <input type="text"
                                   id="nexmo_from"
                                   x-model="config.nexmo.from"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="ConnectApp or +1234567890">
                        </div>
                    </div>

                    <!-- AWS SNS Configuration -->
                    <div x-show="config.provider === 'aws_sns'" class="space-y-4">
                        <div>
                            <label for="aws_region" class="block text-sm font-medium text-gray-700 mb-2">
                                AWS Region *
                            </label>
                            <select id="aws_region"
                                    x-model="config.aws_sns.region"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="us-east-1">US East (N. Virginia)</option>
                                <option value="us-west-2">US West (Oregon)</option>
                                <option value="eu-west-1">Europe (Ireland)</option>
                                <option value="ap-southeast-1">Asia Pacific (Singapore)</option>
                            </select>
                        </div>
                        <div>
                            <label for="aws_access_key" class="block text-sm font-medium text-gray-700 mb-2">
                                Access Key ID *
                            </label>
                            <input type="text"
                                   id="aws_access_key"
                                   x-model="config.aws_sns.access_key_id"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter AWS Access Key ID">
                        </div>
                        <div>
                            <label for="aws_secret_key" class="block text-sm font-medium text-gray-700 mb-2">
                                Secret Access Key *
                            </label>
                            <input type="password"
                                   id="aws_secret_key"
                                   x-model="config.aws_sns.secret_access_key"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter AWS Secret Access Key">
                        </div>
                    </div>

                    <!-- Custom API Configuration -->
                    <div x-show="config.provider === 'custom'" class="space-y-4">
                        <div>
                            <label for="custom_endpoint" class="block text-sm font-medium text-gray-700 mb-2">
                                API Endpoint *
                            </label>
                            <input type="url"
                                   id="custom_endpoint"
                                   x-model="config.custom.endpoint"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="https://api.example.com/sms/send">
                        </div>
                        <div>
                            <label for="custom_method" class="block text-sm font-medium text-gray-700 mb-2">
                                HTTP Method *
                            </label>
                            <select id="custom_method"
                                    x-model="config.custom.method"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="POST">POST</option>
                                <option value="GET">GET</option>
                                <option value="PUT">PUT</option>
                            </select>
                        </div>
                        <div>
                            <label for="custom_headers" class="block text-sm font-medium text-gray-700 mb-2">
                                Headers (JSON)
                            </label>
                            <textarea id="custom_headers"
                                      x-model="config.custom.headers"
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                      placeholder='{"Authorization": "Bearer YOUR_TOKEN", "Content-Type": "application/json"}'></textarea>
                        </div>
                        <div>
                            <label for="custom_body" class="block text-sm font-medium text-gray-700 mb-2">
                                Request Body Template (JSON)
                            </label>
                            <textarea id="custom_body"
                                      x-model="config.custom.body_template"
                                      rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                      placeholder='{"to": "{phone}", "message": "{message}", "from": "ConnectApp"}'></textarea>
                            <p class="text-xs text-gray-500 mt-1">Use {phone} and {message} as placeholders</p>
                        </div>
                    </div>

                    <!-- General Settings -->
                    <div class="space-y-4 pt-6 border-t border-gray-200">
                        <h4 class="text-md font-semibold text-gray-900">General Settings</h4>

                        <div class="flex items-center">
                            <input type="checkbox"
                                   id="sms_enabled"
                                   x-model="config.enabled"
                                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                            <label for="sms_enabled" class="ml-2 block text-sm text-gray-700">
                                Enable SMS notifications
                            </label>
                        </div>

                        <div>
                            <label for="daily_limit" class="block text-sm font-medium text-gray-700 mb-2">
                                Daily SMS Limit
                            </label>
                            <input type="number"
                                   id="daily_limit"
                                   x-model="config.daily_limit"
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="1000">
                            <p class="text-xs text-gray-500 mt-1">Maximum SMS messages per day (0 = unlimited)</p>
                        </div>

                        <div>
                            <label for="rate_limit" class="block text-sm font-medium text-gray-700 mb-2">
                                Rate Limit (per minute)
                            </label>
                            <input type="number"
                                   id="rate_limit"
                                   x-model="config.rate_limit"
                                   min="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="60">
                            <p class="text-xs text-gray-500 mt-1">Maximum SMS messages per minute</p>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end pt-6 border-t border-gray-200">
                        <button type="submit"
                                :disabled="saving"
                                class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!saving">
                                <i class="fas fa-save mr-2"></i>
                                Save Configuration
                            </span>
                            <span x-show="saving">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Test SMS -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Test SMS</h3>
                    <p class="text-sm text-gray-500 mt-1">Send a test SMS to verify your configuration</p>
                </div>

                <form @submit.prevent="sendTestSMS()" class="p-6 space-y-4">
                    <div>
                        <label for="test_phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone Number *
                        </label>
                        <input type="tel"
                               id="test_phone"
                               x-model="testForm.phone"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="+1234567890"
                               required>
                    </div>

                    <div>
                        <label for="test_message" class="block text-sm font-medium text-gray-700 mb-2">
                            Test Message *
                        </label>
                        <textarea id="test_message"
                                  x-model="testForm.message"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                  placeholder="This is a test SMS from ConnectApp"
                                  required></textarea>
                        <div class="text-xs text-gray-500 mt-1">
                            Characters: <span x-text="testForm.message.length"></span>/160
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                :disabled="!config.enabled || sendingTest"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!sendingTest">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Send Test SMS
                            </span>
                            <span x-show="sendingTest">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Sending...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics & Information -->
        <div class="space-y-6">
            <!-- Connection Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Connection Status</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">SMS Service</span>
                        <span :class="config.enabled ? 'text-green-600' : 'text-red-600'"
                              class="text-sm font-medium">
                            <i :class="config.enabled ? 'fas fa-check-circle' : 'fas fa-times-circle'" class="mr-1"></i>
                            <span x-text="config.enabled ? 'Enabled' : 'Disabled'"></span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Provider</span>
                        <span class="text-sm font-medium text-gray-900" x-text="config.provider || 'Not set'"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Last Test</span>
                        <span class="text-sm text-gray-500" x-text="lastTestStatus || 'Never'"></span>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Usage Statistics</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Today</span>
                        <span class="text-lg font-semibold text-gray-900" x-text="stats.today"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">This Week</span>
                        <span class="text-lg font-semibold text-blue-600" x-text="stats.week"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">This Month</span>
                        <span class="text-lg font-semibold text-green-600" x-text="stats.month"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Success Rate</span>
                        <span class="text-lg font-semibold text-primary" x-text="stats.success_rate + '%'"></span>
                    </div>
                </div>
            </div>

            <!-- SMS Templates -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Common Templates</h3>
                <div class="space-y-2">
                    <button @click="useTemplate('verification')"
                            class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm">
                        <div class="font-medium text-gray-900">Verification Code</div>
                        <div class="text-gray-500">OTP verification</div>
                    </button>
                    <button @click="useTemplate('welcome')"
                            class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm">
                        <div class="font-medium text-gray-900">Welcome Message</div>
                        <div class="text-gray-500">New user welcome</div>
                    </button>
                    <button @click="useTemplate('alert')"
                            class="w-full text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm">
                        <div class="font-medium text-gray-900">Security Alert</div>
                        <div class="text-gray-500">Account security</div>
                    </button>
                </div>
            </div>

            <!-- Provider Documentation -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Setup Guides</h3>
                <div class="space-y-2 text-sm">
                    <a href="https://www.twilio.com/docs/sms" target="_blank"
                       class="flex items-center text-blue-600 hover:text-blue-800">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Twilio SMS Setup
                    </a>
                    <a href="https://developer.vonage.com/messaging/sms" target="_blank"
                       class="flex items-center text-blue-600 hover:text-blue-800">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Vonage SMS API
                    </a>
                    <a href="https://docs.aws.amazon.com/sns/latest/dg/sns-mobile-phone-number-as-subscriber.html" target="_blank"
                       class="flex items-center text-blue-600 hover:text-blue-800">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        AWS SNS SMS
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div x-show="showMessage"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed top-4 right-4 z-50 max-w-sm">
        <div :class="messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'"
             class="border px-4 py-3 rounded-lg shadow-lg">
            <div class="flex items-center">
                <i :class="messageType === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'" class="mr-2"></i>
                <span x-text="message"></span>
            </div>
        </div>
    </div>
</div>

<script>
function smsSettings() {
    return {
        config: {
            provider: '',
            enabled: false,
            daily_limit: 1000,
            rate_limit: 60,
            twilio: {
                account_sid: '',
                auth_token: '',
                from_number: ''
            },
            nexmo: {
                api_key: '',
                api_secret: '',
                from: ''
            },
            aws_sns: {
                region: 'us-east-1',
                access_key_id: '',
                secret_access_key: ''
            },
            custom: {
                endpoint: '',
                method: 'POST',
                headers: '',
                body_template: ''
            }
        },
        testForm: {
            phone: '',
            message: 'This is a test SMS from ConnectApp. If you received this, your SMS configuration is working correctly!'
        },
        stats: {
            today: 0,
            week: 0,
            month: 0,
            success_rate: 0
        },
        saving: false,
        sendingTest: false,
        lastTestStatus: null,
        showMessage: false,
        message: '',
        messageType: 'success',

        init() {
            this.loadConfiguration();
            this.loadStats();
        },

        async loadConfiguration() {
            try {
                const response = await fetch('/admin/api/notifications/sms/config');
                const data = await response.json();
                if (data.success) {
                    this.config = { ...this.config, ...data.config };
                }
            } catch (error) {
                console.error('Failed to load configuration:', error);
            }
        },

        async loadStats() {
            try {
                const response = await fetch('/admin/api/notifications/sms/stats');
                const data = await response.json();
                if (data.success) {
                    this.stats = data.stats;
                    this.lastTestStatus = data.last_test_status;
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        updateProviderFields() {
            // Reset provider-specific fields when provider changes
            Object.keys(this.config).forEach(key => {
                if (['twilio', 'nexmo', 'aws_sns', 'custom'].includes(key)) {
                    // Reset the provider config
                }
            });
        },

        async saveConfiguration() {
            this.saving = true;
            try {
                const response = await fetch('/admin/api/notifications/sms/config', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.config)
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('SMS configuration saved successfully');
                } else {
                    this.showError(data.message || 'Failed to save configuration');
                }
            } catch (error) {
                console.error('Save configuration error:', error);
                this.showError('Failed to save configuration');
            } finally {
                this.saving = false;
            }
        },

        async sendTestSMS() {
            if (!this.testForm.phone || !this.testForm.message) {
                this.showError('Please fill in all fields');
                return;
            }

            this.sendingTest = true;
            try {
                const response = await fetch('/admin/api/notifications/sms/test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.testForm)
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccess('Test SMS sent successfully!');
                    this.lastTestStatus = 'Success - ' + new Date().toLocaleString();
                } else {
                    this.showError(data.message || 'Failed to send test SMS');
                    this.lastTestStatus = 'Failed - ' + new Date().toLocaleString();
                }
            } catch (error) {
                console.error('Send test SMS error:', error);
                this.showError('Failed to send test SMS');
                this.lastTestStatus = 'Failed - ' + new Date().toLocaleString();
            } finally {
                this.sendingTest = false;
                this.loadStats();
            }
        },

        useTemplate(type) {
            const templates = {
                verification: 'Your ConnectApp verification code is: 123456. This code will expire in 10 minutes.',
                welcome: 'Welcome to ConnectApp! Start connecting with friends and sharing your moments. Download our app now!',
                alert: 'Security Alert: Your ConnectApp account was accessed from a new device. If this wasn\'t you, please secure your account immediately.'
            };

            if (templates[type]) {
                this.testForm.message = templates[type];
            }
        },

        showSuccess(msg) {
            this.message = msg;
            this.messageType = 'success';
            this.showMessage = true;
            setTimeout(() => {
                this.showMessage = false;
            }, 5000);
        },

        showError(msg) {
            this.message = msg;
            this.messageType = 'error';
            this.showMessage = true;
            setTimeout(() => {
                this.showMessage = false;
            }, 5000);
        }
    }
}
</script>
@endsection
