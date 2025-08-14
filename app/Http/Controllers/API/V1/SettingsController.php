<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get all public settings (no authentication required)
     */
    public function getPublicSettings()
    {
        try {
            $publicSettings = [
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
                'facebook_url' => Setting::getValue('facebook_url', ''),
                'twitter_url' => Setting::getValue('twitter_url', ''),
                'instagram_url' => Setting::getValue('instagram_url', ''),
                'linkedin_url' => Setting::getValue('linkedin_url', ''),
                'privacy_policy_url' => Setting::getValue('privacy_policy_url', ''),
                'terms_of_service_url' => Setting::getValue('terms_of_service_url', ''),
                'user_registration_enabled' => Setting::getValue('user_registration_enabled', 'true'),
                'email_verification_required' => Setting::getValue('email_verification_required', 'true'),
                'social_login_enabled' => Setting::getValue('social_login_enabled', 'false'),
                'subscription_features_enabled' => Setting::getValue('subscription_features_enabled', 'true'),
                'streaming_features_enabled' => Setting::getValue('streaming_features_enabled', 'true'),
                'max_file_upload_size' => Setting::getValue('max_file_upload_size', '10'),
                'max_video_duration' => Setting::getValue('max_video_duration', '300'),
                'max_posts_per_day' => Setting::getValue('max_posts_per_day', '50'),
                'max_story_duration' => Setting::getValue('max_story_duration', '30'),
               // 'max_concurrent_streams' => Setting::getValue('max_concurrent_streams', '5'),
            ];

            return response()->json([
                'success' => true,
                'data' => $publicSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email configuration settings (authenticated users only)
     */
    public function getEmailSettings()
    {
        try {
            $emailSettings = [
                'mail_from_address' => Setting::getValue('mail_from_address', ''),
                'mail_from_name' => Setting::getValue('mail_from_name', 'ConnectApp'),
                'email_notifications_enabled' => Setting::getValue('email_notifications_enabled', 'true'),
            ];

            return response()->json([
                'success' => true,
                'data' => $emailSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get email settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification settings (authenticated users only)
     */
    public function getNotificationSettings()
    {
        try {
            $notificationSettings = [
                'push_notifications_enabled' => Setting::getValue('push_notifications_enabled', 'true'),
                'email_notifications_enabled' => Setting::getValue('email_notifications_enabled', 'true'),
            ];

            return response()->json([
                'success' => true,
                'data' => $notificationSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment gateway settings (authenticated users only)
     */
    public function getPaymentSettings()
    {
        try {
            $paymentSettings = [
                'nomba_enabled' => Setting::getValue('nomba_enabled', 'false'),
                'paystack_enabled' => Setting::getValue('paystack_enabled', 'false'),
                'stripe_enabled' => Setting::getValue('stripe_enabled', 'false'),
                // Only return public keys for security
                'paystack_public_key' => Setting::getValue('paystack_enabled', 'false') === 'true' ? Setting::getValue('paystack_public_key', '') : '',
                'stripe_public_key' => Setting::getValue('stripe_enabled', 'false') === 'true' ? Setting::getValue('stripe_public_key', '') : '',
            ];

            return response()->json([
                'success' => true,
                'data' => $paymentSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get API configuration settings (authenticated users only)
     */
    public function getApiSettings()
    {
        try {
            $apiSettings = [
                'agora_app_id' => Setting::getValue('agora_app_id', ''),
                'pusher_app_key' => Setting::getValue('pusher_app_key', ''),
                'pusher_app_cluster' => Setting::getValue('pusher_app_cluster', 'us2'),
            ];

            return response()->json([
                'success' => true,
                'data' => $apiSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get API settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get feature settings (authenticated users only)
     */
    public function getFeatureSettings()
    {
        try {
            $featureSettings = [
                'user_registration_enabled' => Setting::getValue('user_registration_enabled', 'true'),
                'email_verification_required' => Setting::getValue('email_verification_required', 'true'),
                'social_login_enabled' => Setting::getValue('social_login_enabled', 'false'),
                'subscription_features_enabled' => Setting::getValue('subscription_features_enabled', 'true'),
                'streaming_features_enabled' => Setting::getValue('streaming_features_enabled', 'true'),
                'content_moderation_enabled' => Setting::getValue('content_moderation_enabled', 'true'),
            ];

            return response()->json([
                'success' => true,
                'data' => $featureSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get feature settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get limit settings (authenticated users only)
     */
    public function getLimitSettings()
    {
        try {
            $limitSettings = [
                'max_file_upload_size' => (int) Setting::getValue('max_file_upload_size', '10'),
                'max_video_duration' => (int) Setting::getValue('max_video_duration', '300'),
                'max_posts_per_day' => (int) Setting::getValue('max_posts_per_day', '50'),
                'max_story_duration' => (int) Setting::getValue('max_story_duration', '30'),
                'max_concurrent_streams' => (int) Setting::getValue('max_concurrent_streams', '5'),
            ];

            return response()->json([
                'success' => true,
                'data' => $limitSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get limit settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all settings (authenticated users only)
     */
    public function getAllSettings()
    {
        try {
            $allSettings = [
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
                    'mail_from_address' => Setting::getValue('mail_from_address', ''),
                    'mail_from_name' => Setting::getValue('mail_from_name', 'ConnectApp'),
                    'email_notifications_enabled' => Setting::getValue('email_notifications_enabled', 'true'),
                ],
                'notifications' => [
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
                    'nomba_enabled' => Setting::getValue('nomba_enabled', 'false'),
                    'paystack_enabled' => Setting::getValue('paystack_enabled', 'false'),
                    'stripe_enabled' => Setting::getValue('stripe_enabled', 'false'),
                    'paystack_public_key' => Setting::getValue('paystack_enabled', 'false') === 'true' ? Setting::getValue('paystack_public_key', '') : '',
                    'stripe_public_key' => Setting::getValue('stripe_enabled', 'false') === 'true' ? Setting::getValue('stripe_public_key', '') : '',
                ],
                'apis' => [
                    'agora_app_id' => Setting::getValue('agora_app_id', ''),
                    'pusher_app_key' => Setting::getValue('pusher_app_key', ''),
                    'pusher_app_cluster' => Setting::getValue('pusher_app_cluster', 'us2'),
                ],
                'limits' => [
                    'max_file_upload_size' => (int) Setting::getValue('max_file_upload_size', '10'),
                    'max_video_duration' => (int) Setting::getValue('max_video_duration', '300'),
                    'max_posts_per_day' => (int) Setting::getValue('max_posts_per_day', '50'),
                    'max_story_duration' => (int) Setting::getValue('max_story_duration', '30'),
                    'max_concurrent_streams' => (int) Setting::getValue('max_concurrent_streams', '5'),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $allSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific setting value
     */
    public function getSetting($key)
    {
        try {
            // Define which settings are publicly accessible
            $publicSettings = [
                'app_name', 'app_description', 'app_logo', 'app_favicon',
                'maintenance_mode', 'maintenance_message', 'company_email',
                'company_phone', 'company_address', 'mobile_app_version',
                'web_app_version', 'facebook_url', 'twitter_url', 'instagram_url',
                'linkedin_url', 'privacy_policy_url', 'terms_of_service_url',
                'user_registration_enabled', 'email_verification_required',
                'social_login_enabled', 'subscription_features_enabled',
                'streaming_features_enabled', 'max_file_upload_size',
                'max_video_duration', 'max_posts_per_day', 'max_story_duration',
                'max_concurrent_streams'
            ];

            // Check if the setting is publicly accessible or user is authenticated
            if (!in_array($key, $publicSettings) && !auth('sanctum')->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required for this setting.'
                ], 401);
            }

            $value = Setting::getValue($key);

            return response()->json([
                'success' => true,
                'key' => $key,
                'value' => $value
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get setting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check maintenance mode status
     */
    public function getMaintenanceStatus()
    {
        try {
            $maintenanceMode = Setting::getValue('maintenance_mode', 'false');
            $maintenanceMessage = Setting::getValue('maintenance_message', 'We are currently performing maintenance. Please check back later.');

            return response()->json([
                'success' => true,
                'maintenance_mode' => $maintenanceMode === 'true',
                'maintenance_message' => $maintenanceMessage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get maintenance status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get app version information
     */
    public function getAppVersions()
    {
        try {
            $versions = [
                'mobile_app_version' => Setting::getValue('mobile_app_version', '1.0.0'),
                'web_app_version' => Setting::getValue('web_app_version', '1.0.0'),
            ];

            return response()->json([
                'success' => true,
                'data' => $versions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get app versions: ' . $e->getMessage()
            ], 500);
        }
    }
}
