<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware(function ($request, $next) {
            if (!auth('admin')->user()->hasRole('super_admin')) {
                abort(403, 'Unauthorized access to system settings.');
            }
            return $next($request);
        });
    }

    /**
     * Display the system settings page
     */
    public function index()
    {
        // Get all settings grouped by category
        $settings = [
            'general' => [
                'app_name' => Setting::getValue('app_name', 'ConnectApp'),
                'app_description' => Setting::getValue('app_description', 'Connect with friends and share your moments'),
                'app_logo' => Setting::getValue('app_logo', ''),
                'app_favicon' => Setting::getValue('app_favicon', ''),
                'maintenance_mode' => Setting::getValue('maintenance_mode', 'false'),
                'maintenance_message' => Setting::getValue('maintenance_message', 'We are currently performing maintenance. Please check back later.'),
                'company_email' => Setting::getValue('company_email', ''),
                'company_phone' => Setting::getValue('company_phone', ''),
                'company_address' => Setting::getValue('company_address', ''),
                'mobile_app_version' => Setting::getValue('mobile_app_version', '1.0.0'),
                'web_app_version' => Setting::getValue('web_app_version', '1.0.0'),
            ],
            'email' => [
                'smtp_host' => Setting::getValue('smtp_host', ''),
                'smtp_port' => Setting::getValue('smtp_port', '587'),
                'smtp_username' => Setting::getValue('smtp_username', ''),
                'smtp_password' => Setting::getValue('smtp_password', ''),
                'smtp_encryption' => Setting::getValue('smtp_encryption', 'tls'),
                'mail_from_address' => Setting::getValue('mail_from_address', ''),
                'mail_from_name' => Setting::getValue('mail_from_name', 'ConnectApp'),
            ],
            'notifications' => [
                'firebase_server_key' => Setting::getValue('firebase_server_key', ''),
                'firebase_sender_id' => Setting::getValue('firebase_sender_id', ''),
                'push_notifications_enabled' => Setting::getValue('push_notifications_enabled', 'true'),
                'email_notifications_enabled' => Setting::getValue('email_notifications_enabled', 'true'),
            ],
            'social' => [
                'facebook_url' => Setting::getValue('facebook_url', ''),
                'twitter_url' => Setting::getValue('twitter_url', ''),
                'instagram_url' => Setting::getValue('instagram_url', ''),
                'linkedin_url' => Setting::getValue('linkedin_url', ''),
                'privacy_policy_url' => Setting::getValue('privacy_policy_url', ''),
                'terms_of_service_url' => Setting::getValue('terms_of_service_url', ''),
            ],
            'features' => [
                'user_registration_enabled' => Setting::getValue('user_registration_enabled', 'true'),
                'email_verification_required' => Setting::getValue('email_verification_required', 'true'),
                'social_login_enabled' => Setting::getValue('social_login_enabled', 'false'),
                'subscription_features_enabled' => Setting::getValue('subscription_features_enabled', 'true'),
                'streaming_features_enabled' => Setting::getValue('streaming_features_enabled', 'true'),
                'content_moderation_enabled' => Setting::getValue('content_moderation_enabled', 'true'),
            ],
            'payments' => [
                'nomba_client_id' => Setting::getValue('nomba_client_id', ''),
                'nomba_private_key' => Setting::getValue('nomba_private_key', ''),
                'nomba_account_id' => Setting::getValue('nomba_account_id', ''),
                'nomba_enabled' => Setting::getValue('nomba_enabled', 'false'),
                'paystack_public_key' => Setting::getValue('paystack_public_key', ''),
                'paystack_secret_key' => Setting::getValue('paystack_secret_key', ''),
                'paystack_enabled' => Setting::getValue('paystack_enabled', 'false'),
                'stripe_public_key' => Setting::getValue('stripe_public_key', ''),
                'stripe_secret_key' => Setting::getValue('stripe_secret_key', ''),
                'stripe_enabled' => Setting::getValue('stripe_enabled', 'false'),
            ],
            'apis' => [
                'agora_app_id' => Setting::getValue('agora_app_id', ''),
                'agora_app_certificate' => Setting::getValue('agora_app_certificate', ''),
                'pusher_app_id' => Setting::getValue('pusher_app_id', ''),
                'pusher_app_key' => Setting::getValue('pusher_app_key', ''),
                'pusher_app_secret' => Setting::getValue('pusher_app_secret', ''),
                'pusher_app_cluster' => Setting::getValue('pusher_app_cluster', 'us2'),
            ],
            'limits' => [
                'max_file_upload_size' => Setting::getValue('max_file_upload_size', '10'),
                'max_video_duration' => Setting::getValue('max_video_duration', '300'),
                'max_posts_per_day' => Setting::getValue('max_posts_per_day', '50'),
                'max_story_duration' => Setting::getValue('max_story_duration', '30'),
                'max_concurrent_streams' => Setting::getValue('max_concurrent_streams', '5'),
            ],
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update system settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'app_description' => 'nullable|string|max:500',
            'app_logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'app_favicon' => 'nullable|image|mimes:png,ico|max:1024',
            'maintenance_mode' => 'required|in:true,false',
            'maintenance_message' => 'nullable|string|max:500',

            // General settings - Company Information
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:500',
            'mobile_app_version' => 'nullable|string|max:20',
            'web_app_version' => 'nullable|string|max:20',

            // Email settings
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:tls,ssl,none',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',

            // Notification settings
            'firebase_server_key' => 'nullable|string',
            'firebase_sender_id' => 'nullable|string',
            'push_notifications_enabled' => 'required|in:true,false',
            'email_notifications_enabled' => 'required|in:true,false',

            // Social links
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'privacy_policy_url' => 'nullable|url|max:255',
            'terms_of_service_url' => 'nullable|url|max:255',

            // Feature toggles
            'user_registration_enabled' => 'required|in:true,false',
            'email_verification_required' => 'required|in:true,false',
            'social_login_enabled' => 'required|in:true,false',
            'subscription_features_enabled' => 'required|in:true,false',
            'streaming_features_enabled' => 'required|in:true,false',
            'content_moderation_enabled' => 'required|in:true,false',

            // Payment Gateway settings
            'nomba_client_id' => 'nullable|string|max:255',
            'nomba_private_key' => 'nullable|string',
            'nomba_account_id' => 'nullable|string|max:255',
            'nomba_enabled' => 'required|in:true,false',
            'paystack_public_key' => 'nullable|string|max:255',
            'paystack_secret_key' => 'nullable|string|max:255',
            'paystack_enabled' => 'required|in:true,false',
            'stripe_public_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
            'stripe_enabled' => 'required|in:true,false',

            // API Keys settings
            'agora_app_id' => 'nullable|string|max:255',
            'agora_app_certificate' => 'nullable|string|max:255',
            'pusher_app_id' => 'nullable|string|max:255',
            'pusher_app_key' => 'nullable|string|max:255',
            'pusher_app_secret' => 'nullable|string|max:255',
            'pusher_app_cluster' => 'nullable|string|max:50',

            // Limits
            'max_file_upload_size' => 'required|integer|min:1|max:100',
            'max_video_duration' => 'required|integer|min:30|max:3600',
            'max_posts_per_day' => 'required|integer|min:1|max:1000',
            'max_story_duration' => 'required|integer|min:5|max:300',
            'max_concurrent_streams' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Handle file uploads
            if ($request->hasFile('app_logo')) {
                $logoPath = $request->file('app_logo')->store('settings/logos', 'public');
                $this->createOrUpdateSetting('app_logo', $logoPath, 'Application Logo');
            }

            if ($request->hasFile('app_favicon')) {
                $faviconPath = $request->file('app_favicon')->store('settings/favicons', 'public');
                $this->createOrUpdateSetting('app_favicon', $faviconPath, 'Application Favicon');
            }

            // Define settings with their human-readable names
            $settingsDefinitions = [
                // General settings
                'app_name' => 'Application Name',
                'app_description' => 'Application Description',
                'maintenance_mode' => 'Maintenance Mode',
                'maintenance_message' => 'Maintenance Message',
                'company_email' => 'Company Email Address',
                'company_phone' => 'Company Phone Number',
                'company_address' => 'Company Address',
                'mobile_app_version' => 'Mobile App Version',
                'web_app_version' => 'Web App Version',

                // Email settings
                'smtp_host' => 'SMTP Host',
                'smtp_port' => 'SMTP Port',
                'smtp_username' => 'SMTP Username',
                'smtp_password' => 'SMTP Password',
                'smtp_encryption' => 'SMTP Encryption',
                'mail_from_address' => 'Mail From Address',
                'mail_from_name' => 'Mail From Name',

                // Notification settings
                'firebase_server_key' => 'Firebase Server Key',
                'firebase_sender_id' => 'Firebase Sender ID',
                'push_notifications_enabled' => 'Push Notifications Enabled',
                'email_notifications_enabled' => 'Email Notifications Enabled',

                // Social & Legal settings
                'facebook_url' => 'Facebook URL',
                'twitter_url' => 'Twitter URL',
                'instagram_url' => 'Instagram URL',
                'linkedin_url' => 'LinkedIn URL',
                'privacy_policy_url' => 'Privacy Policy URL',
                'terms_of_service_url' => 'Terms of Service URL',

                // Feature settings
                'user_registration_enabled' => 'User Registration Enabled',
                'email_verification_required' => 'Email Verification Required',
                'social_login_enabled' => 'Social Login Enabled',
                'subscription_features_enabled' => 'Subscription Features Enabled',
                'streaming_features_enabled' => 'Streaming Features Enabled',
                'content_moderation_enabled' => 'Content Moderation Enabled',

                // Payment Gateway settings
                'nomba_client_id' => 'Nomba Client ID',
                'nomba_private_key' => 'Nomba Private Key',
                'nomba_account_id' => 'Nomba Account ID',
                'nomba_enabled' => 'Nomba Payment Gateway Enabled',
                'paystack_public_key' => 'Paystack Public Key',
                'paystack_secret_key' => 'Paystack Secret Key',
                'paystack_enabled' => 'Paystack Payment Gateway Enabled',
                'stripe_public_key' => 'Stripe Public Key',
                'stripe_secret_key' => 'Stripe Secret Key',
                'stripe_enabled' => 'Stripe Payment Gateway Enabled',

                // API Keys settings
                'agora_app_id' => 'Agora.io App ID',
                'agora_app_certificate' => 'Agora.io App Certificate',
                'pusher_app_id' => 'Pusher App ID',
                'pusher_app_key' => 'Pusher App Key',
                'pusher_app_secret' => 'Pusher App Secret',
                'pusher_app_cluster' => 'Pusher App Cluster',

                // Limit settings
                'max_file_upload_size' => 'Max File Upload Size (MB)',
                'max_video_duration' => 'Max Video Duration (seconds)',
                'max_posts_per_day' => 'Max Posts Per Day',
                'max_story_duration' => 'Max Story Duration (seconds)',
                'max_concurrent_streams' => 'Max Concurrent Streams'
            ];

            // Update or create each setting
            foreach ($settingsDefinitions as $slug => $name) {
                if ($request->has($slug)) {
                    $value = $request->input($slug);
                    $this->createOrUpdateSetting($slug, $value, $name);
                }
            }

            return redirect()->route('admin.settings.index')
                ->with('success', 'System settings updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update settings: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid email address.'
            ]);
        }

        try {
            // Update mail configuration temporarily
            config([
                'mail.mailers.smtp.host' => Setting::getValue('smtp_host'),
                'mail.mailers.smtp.port' => Setting::getValue('smtp_port'),
                'mail.mailers.smtp.username' => Setting::getValue('smtp_username'),
                'mail.mailers.smtp.password' => Setting::getValue('smtp_password'),
                'mail.mailers.smtp.encryption' => Setting::getValue('smtp_encryption'),
                'mail.from.address' => Setting::getValue('mail_from_address'),
                'mail.from.name' => Setting::getValue('mail_from_name'),
            ]);

            // Send test email
            Mail::raw('This is a test email from ConnectApp admin settings.', function ($message) use ($request) {
                $message->to($request->test_email)
                    ->subject('ConnectApp - Test Email Configuration');
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete uploaded file
     */
    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'setting_key' => 'required|string|in:app_logo,app_favicon'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid setting key.'
            ]);
        }

        try {
            $settingKey = $request->setting_key;
            $currentValue = Setting::getValue($settingKey);

            if ($currentValue && Storage::disk('public')->exists($currentValue)) {
                Storage::disk('public')->delete($currentValue);
            }

            Setting::setValue($settingKey, '');

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create or update a setting with proper slug and name
     */
    private function createOrUpdateSetting($slug, $value, $name)
    {
        return Setting::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'value' => $value
            ]
        );
    }
}
