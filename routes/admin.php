<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Admin\StoryManagementController;
use App\Http\Controllers\Admin\AdManagementController;
use App\Http\Controllers\Admin\SubscriptionManagementController;
use App\Http\Controllers\Admin\StreamManagementController;
use App\Http\Controllers\Admin\RtmpController;
use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SocialCircleController;
use App\Http\Controllers\Admin\CountryController;

// Admin Authentication Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes (not authenticated)

        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('auth.login');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login.post');



        Route::get('/verify-otp', [AuthController::class, 'showOtpForm'])->name('auth.verify-otp');
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verify-otp.post');
        Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('auth.resend-otp');


    // Authenticated admin routes
    Route::middleware('auth:admin')->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        // API Routes for AJAX requests
        Route::prefix('api')->group(function () {
            // Post Reports Management API
            Route::get('/post-reports', [\App\Http\Controllers\Admin\PostManagementController::class, 'getPostReports']);
            Route::patch('/post-reports/{report}/status', [\App\Http\Controllers\Admin\PostManagementController::class, 'updateReportStatus']);
            Route::get('/dashboard-data', [DashboardController::class, 'getDashboardData']);
            Route::get('/dashboard-charts', [DashboardController::class, 'getChartDataByPeriod']);
            Route::get('/users', [UserManagementController::class, 'getUsers']);
            Route::get('/social-circles', [UserManagementController::class, 'getSocialCircles']);
            Route::get('/countries', [UserManagementController::class, 'getCountries']);
            Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus']);
            Route::patch('/users/bulk-status', [UserManagementController::class, 'bulkUpdateStatus']);
            Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword']);

            // Verification Management API
            Route::prefix('verifications')->group(function () {
                Route::get('/pending-count', [\App\Http\Controllers\Admin\VerificationController::class, 'getPendingCount']);
                Route::get('/pending', [\App\Http\Controllers\Admin\VerificationController::class, 'getPendingVerifications']);
                Route::get('/user/{userId}/latest', [\App\Http\Controllers\Admin\VerificationController::class, 'getUserLatest']);
                Route::post('/{verification}/approve', [\App\Http\Controllers\Admin\VerificationController::class, 'approveVerification']);
                Route::post('/{verification}/reject', [\App\Http\Controllers\Admin\VerificationController::class, 'rejectVerification']);
            });

            // Post Management API
            Route::get('/posts', [\App\Http\Controllers\Admin\PostManagementController::class, 'getPosts']);
            Route::patch('/posts/{post}/status', [\App\Http\Controllers\Admin\PostManagementController::class, 'updateStatus']);
            Route::patch('/posts/bulk-status', [\App\Http\Controllers\Admin\PostManagementController::class, 'bulkUpdateStatus']);
            Route::get('/countries', [\App\Http\Controllers\Admin\PostManagementController::class, 'getCountries']);

            // Story Management API
            Route::get('/stories', [StoryManagementController::class, 'getStories']);
            Route::get('/stories/stats', [StoryManagementController::class, 'getStats']);
            Route::post('/stories/bulk-delete', [StoryManagementController::class, 'bulkDelete']);
            Route::post('/stories/cleanup-expired', [StoryManagementController::class, 'cleanupExpired']);

            // Ad Management API
            Route::get('/ads', [AdManagementController::class, 'getAds']);
            Route::get('/ads/stats', [AdManagementController::class, 'getStats']);
            Route::post('/ads/bulk-approve', [AdManagementController::class, 'bulkApprove']);
            Route::post('/ads/bulk-reject', [AdManagementController::class, 'bulkReject']);
            Route::get('/ads/countries', [AdManagementController::class, 'getCountries']);
            Route::get('/ads/social-circles', [AdManagementController::class, 'getSocialCircles']);

            // System Management API
            Route::prefix('system')->group(function () {
                Route::get('/social-circles', [SocialCircleController::class, 'getSocialCircles']);
                Route::get('/countries', [CountryController::class, 'getCountries']);
            });

            // Subscription Management API
            Route::get('/subscriptions', [SubscriptionManagementController::class, 'getSubscriptions']);
            Route::get('/subscriptions/stats', [SubscriptionManagementController::class, 'getStats']);
            Route::get('/subscription-plans', [SubscriptionManagementController::class, 'getPlans']);
            Route::get('/subscription-plans/stats', [SubscriptionManagementController::class, 'getPlanStats']);

            // Stream Management API
            Route::get('/streams', [StreamManagementController::class, 'getStreams']);
            Route::get('/streams/stats', [StreamManagementController::class, 'getStats']);
            Route::get('/streams/interaction-stats', [StreamManagementController::class, 'getStreamInteractionStats']);
            Route::get('/streams/{id}/viewers', [StreamManagementController::class, 'getViewers']);
            Route::get('/streams/{id}/chats', [StreamManagementController::class, 'getChats']);
            Route::post('/streams/{id}/start', [StreamManagementController::class, 'startStream']);
            Route::post('/streams/{id}/end', [StreamManagementController::class, 'endStream']);
            Route::post('/streams/{id}/chats', [StreamManagementController::class, 'sendAdminMessage']);
            Route::get('/streams/{id}/token', [StreamManagementController::class, 'getStreamToken']);
            Route::delete('/chats/{chatId}', [StreamManagementController::class, 'deleteChat']);

            // Multi-Camera Management API
            Route::get('/streams/{id}/cameras', [StreamManagementController::class, 'getCameras']);
            Route::post('/streams/{id}/cameras', [StreamManagementController::class, 'addCamera']);
            Route::delete('/streams/{id}/cameras/{cameraId}', [StreamManagementController::class, 'removeCamera']);
            Route::post('/streams/{id}/switch-camera', [StreamManagementController::class, 'switchCamera']);
            Route::put('/streams/{id}/cameras/{cameraId}/status', [StreamManagementController::class, 'updateCameraStatus']);
            Route::get('/streams/{id}/mixer-settings', [StreamManagementController::class, 'getMixerSettings']);
            Route::put('/streams/{id}/mixer-settings', [StreamManagementController::class, 'updateMixerSettings']);
            Route::get('/streams/{id}/camera-switches', [StreamManagementController::class, 'getCameraSwitchHistory']);

            // RTMP Streaming API
            Route::get('/streams/{id}/rtmp-details', [RtmpController::class, 'getStreamRtmpDetails']);
            Route::put('/streams/{id}/rtmp-settings', [RtmpController::class, 'updateRtmpSettings']);
            Route::get('/streams/{id}/rtmp-status', [RtmpController::class, 'checkRtmpStatus']);
            Route::post('/streams/{id}/rtmp-stop', [RtmpController::class, 'stopRtmpStream']);
            Route::post('/rtmp-heartbeat', [RtmpController::class, 'rtmpHeartbeat']); // Called by RTMP server
            Route::get('/rtmp-server-status', [RtmpController::class, 'getServerStatus']); // Check RTMP server status

            // Advertisement Management API
            Route::prefix('ads')->group(function () {
                Route::get('/available', [AdController::class, 'getAvailableAds']);
                Route::post('/streams/{id}/trigger', [AdController::class, 'triggerAdBreak']);
                Route::get('/streams/{id}/check-timing', [AdController::class, 'checkAdTiming']);
                Route::get('/streams/{id}/stats', [AdController::class, 'getStreamAdStats']);
                Route::post('/interaction', [AdController::class, 'recordAdInteraction']);
            });

            // Notification Management API
            Route::prefix('notifications')->group(function () {
                // Admin notifications
                Route::get('/', [NotificationController::class, 'getAdminNotifications']);
                Route::post('/{id}/read', [NotificationController::class, 'markNotificationAsRead']);
                Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);

                // Push notifications
                Route::prefix('push')->group(function () {
                    Route::post('/send', [NotificationController::class, 'sendPushNotification']);
                    Route::post('/preview-targets', [NotificationController::class, 'previewTargets']);
                    Route::post('/test-admin', [NotificationController::class, 'testAdminNotification']);
                });

                // Admin FCM token management
                Route::prefix('admin-fcm')->group(function () {
                    Route::post('/subscribe', [NotificationController::class, 'subscribeAdmin']);
                    Route::post('/unsubscribe', [NotificationController::class, 'unsubscribeAdmin']);
                    Route::get('/tokens', [NotificationController::class, 'getAdminTokens']);
                    Route::put('/preferences', [NotificationController::class, 'updateAdminPreferences']);
                });

                // Get notification resources
                Route::get('/stats', [NotificationController::class, 'getNotificationStats']);
                Route::get('/social-circles', [NotificationController::class, 'getSocialCircles']);
                Route::get('/countries', [NotificationController::class, 'getCountries']);

                // Email templates
                Route::prefix('email')->group(function () {
                    Route::get('/templates', [NotificationController::class, 'getEmailTemplates']);
                    Route::post('/templates', [NotificationController::class, 'storeEmailTemplate']);
                    Route::put('/templates/{id}', [NotificationController::class, 'updateEmailTemplate']);
                    Route::put('/templates/{id}/toggle', [NotificationController::class, 'toggleEmailTemplate']);
                    Route::delete('/templates/{id}', [NotificationController::class, 'deleteEmailTemplate']);
                    Route::get('/stats', [NotificationController::class, 'getEmailStats']);
                });

                // SMS settings
                Route::prefix('sms')->group(function () {
                    Route::get('/config', [NotificationController::class, 'getSmsConfig']);
                    Route::post('/config', [NotificationController::class, 'updateSmsConfig']);
                    Route::post('/test', [NotificationController::class, 'sendTestSms']);
                    Route::get('/stats', [NotificationController::class, 'getSmsStats']);
                });

                // Notification logs
                Route::prefix('logs')->group(function () {
                    Route::get('/', [NotificationController::class, 'getLogs']);
                    Route::get('/stats', [NotificationController::class, 'getLogStats']);
                    Route::post('/{id}/retry', [NotificationController::class, 'retryNotification']);
                    Route::delete('/{id}', [NotificationController::class, 'deleteLog']);
                    Route::delete('/cleanup', [NotificationController::class, 'cleanupOldLogs']);
                    Route::get('/export', [NotificationController::class, 'exportLogs']);
                });

                // Additional API endpoints
                Route::get('/stats', [NotificationController::class, 'getNotificationStats']);
            });

            // User search for notifications
            Route::get('/users/search', [UserManagementController::class, 'searchUsers']);

            // Debug route for Agora configuration
            Route::get('/test-agora', function () {
                try {
                    $appId = env('AGORA_APP_ID');
                    $appCertificate = env('AGORA_APP_CERTIFICATE');

                    $configAppId = config('services.agora.app_id');
                    $configCertificate = config('services.agora.app_certificate');

                    $isConfigured = \App\Helpers\AgoraHelper::isConfigured();

                    return response()->json([
                        'success' => true,
                        'env_app_id' => $appId ? 'Set (' . strlen($appId) . ' chars)' : 'Not set',
                        'env_certificate' => $appCertificate ? 'Set (' . strlen($appCertificate) . ' chars)' : 'Not set',
                        'config_app_id' => $configAppId ? 'Set (' . strlen($configAppId) . ' chars)' : 'Not set',
                        'config_certificate' => $configCertificate ? 'Set (' . strlen($configCertificate) . ' chars)' : 'Not set',
                        'agora_helper_configured' => $isConfigured,
                        'test_timestamp' => now()->toISOString()
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ], 500);
                }
            });

            // Debug route for users API
            Route::get('/test-users', function () {
                try {
                    $userCount = \App\Models\User::count();
                    $users = \App\Models\User::take(3)->get(['id', 'name', 'email']);

                    return response()->json([
                        'success' => true,
                        'user_count' => $userCount,
                        'sample_users' => $users,
                        'authenticated_admin' => auth('admin')->check(),
                        'admin_user' => auth('admin')->user() ? auth('admin')->user()->name : 'None',
                        'test_timestamp' => now()->toISOString()
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ], 500);
                }
            });
        });

        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/export', [UserManagementController::class, 'export'])->name('export');
            Route::get('/export-status', [UserManagementController::class, 'getExportStatus'])->name('export-status');
            Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
            Route::patch('/{user}', [UserManagementController::class, 'update'])->name('update');
            Route::patch('/{user}/suspend', [UserManagementController::class, 'suspend'])->name('suspend');
            Route::patch('/{user}/activate', [UserManagementController::class, 'activate'])->name('activate');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        });

        // Post Management
        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PostManagementController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\PostManagementController::class, 'export'])->name('export');
            Route::get('/reports', function() {
                return view('admin.posts.reports');
            })->name('reports');
            Route::get('/{post}', [\App\Http\Controllers\Admin\PostManagementController::class, 'show'])->name('show');
            Route::delete('/{post}', [\App\Http\Controllers\Admin\PostManagementController::class, 'destroy'])->name('destroy');
        });

        // Story Management
        Route::prefix('stories')->name('stories.')->group(function () {
            Route::get('/', [StoryManagementController::class, 'index'])->name('index');
            Route::get('/export', [StoryManagementController::class, 'export'])->name('export');
            Route::get('/{story}', [StoryManagementController::class, 'show'])->name('show');
            Route::delete('/{story}', [StoryManagementController::class, 'destroy'])->name('destroy');
        });

        // Ad Management
        Route::prefix('ads')->name('ads.')->group(function () {
            Route::get('/', [AdManagementController::class, 'index'])->name('index');
            Route::get('/export', [AdManagementController::class, 'export'])->name('export');
            Route::get('/{ad}', [AdManagementController::class, 'show'])->name('show');
            Route::post('/{ad}/approve', [AdManagementController::class, 'approve'])->name('approve');
            Route::post('/{ad}/reject', [AdManagementController::class, 'reject'])->name('reject');
            Route::post('/{ad}/pause', [AdManagementController::class, 'pauseAd'])->name('pause');
            Route::post('/{ad}/resume', [AdManagementController::class, 'resumeAd'])->name('resume');
            Route::post('/{ad}/stop', [AdManagementController::class, 'stopAd'])->name('stop');
            Route::delete('/{ad}', [AdManagementController::class, 'destroy'])->name('destroy');
        });

        // Subscription Management
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
             Route::get('/plans', [SubscriptionManagementController::class, 'plansIndex'])->name('plans.index');
            Route::get('/', [SubscriptionManagementController::class, 'index'])->name('index');
            Route::get('/get', [SubscriptionManagementController::class, 'getSubscriptions'])->name('get');
            Route::get('/stats', [SubscriptionManagementController::class, 'getStats'])->name('stats');
            Route::get('/export', [SubscriptionManagementController::class, 'export'])->name('export');
            Route::get('/{subscription}', [SubscriptionManagementController::class, 'show'])->name('show');
            Route::put('/{subscription}/status', [SubscriptionManagementController::class, 'updateSubscriptionStatus'])->name('update-status');
            Route::put('/{subscription}/extend', [SubscriptionManagementController::class, 'extendSubscription'])->name('extend');

            // Subscription Plans
            Route::prefix('plans')->name('plans.')->group(function () {
                Route::get('/create', [SubscriptionManagementController::class, 'createPlan'])->name('create');
                Route::post('/', [SubscriptionManagementController::class, 'storePlan'])->name('store');
                Route::get('/get', [SubscriptionManagementController::class, 'getPlans'])->name('get');
                Route::get('/stats', [SubscriptionManagementController::class, 'getPlanStats'])->name('stats');
                Route::get('/export', [SubscriptionManagementController::class, 'exportPlans'])->name('export');
                Route::get('/{plan}', [SubscriptionManagementController::class, 'showPlan'])->name('show');
                Route::get('/{plan}/edit', [SubscriptionManagementController::class, 'editPlan'])->name('edit');
                Route::put('/{plan}', [SubscriptionManagementController::class, 'updatePlan'])->name('update');
                Route::put('/{plan}/status', [SubscriptionManagementController::class, 'updatePlanStatus'])->name('update-status');
                Route::delete('/{plan}', [SubscriptionManagementController::class, 'destroyPlan'])->name('destroy');
            });
        });

        // Stream Management
        Route::prefix('streams')->name('streams.')->group(function () {
            Route::get('/', [StreamManagementController::class, 'index'])->name('index');
            Route::get('/create', [StreamManagementController::class, 'create'])->name('create');
            Route::post('/', [StreamManagementController::class, 'store'])->name('store');
            Route::get('/{stream}', [StreamManagementController::class, 'show'])->name('show');
            Route::get('/{stream}/edit', [StreamManagementController::class, 'edit'])->name('edit');
            Route::get('/{stream}/broadcast', [StreamManagementController::class, 'broadcast'])->name('broadcast');
            Route::get('/{stream}/viewers-chat', [StreamManagementController::class, 'viewersChat'])->name('viewers-chat');
            Route::get('/{stream}/cameras', [StreamManagementController::class, 'cameraManagement'])->name('cameras');
            Route::put('/{stream}', [StreamManagementController::class, 'update'])->name('update');
            Route::delete('/{stream}', [StreamManagementController::class, 'destroy'])->name('destroy');
        });

        // System Management
        Route::prefix('social-circles')->name('social-circles.')->group(function () {
            Route::get('/', [SocialCircleController::class, 'index'])->name('index');
            Route::get('/create', [SocialCircleController::class, 'create'])->name('create');
            Route::get('/export', [SocialCircleController::class, 'export'])->name('export');
            Route::post('/', [SocialCircleController::class, 'store'])->name('store');
            Route::get('/{socialCircle}', [SocialCircleController::class, 'show'])->name('show');
            Route::get('/{socialCircle}/edit', [SocialCircleController::class, 'edit'])->name('edit');
            Route::put('/{socialCircle}', [SocialCircleController::class, 'update'])->name('update');
            Route::delete('/{socialCircle}', [SocialCircleController::class, 'destroy'])->name('destroy');
            Route::patch('/{socialCircle}/status', [SocialCircleController::class, 'updateStatus'])->name('update-status');
        });

        Route::prefix('countries')->name('countries.')->group(function () {
            Route::get('/', [CountryController::class, 'index'])->name('index');
            Route::get('/create', [CountryController::class, 'create'])->name('create');
            Route::get('/export', [CountryController::class, 'export'])->name('export');
            Route::post('/', [CountryController::class, 'store'])->name('store');
            Route::get('/{country}', [CountryController::class, 'show'])->name('show');
            Route::get('/{country}/edit', [CountryController::class, 'edit'])->name('edit');
            Route::put('/{country}', [CountryController::class, 'update'])->name('update');
            Route::delete('/{country}', [CountryController::class, 'destroy'])->name('destroy');
            Route::patch('/{country}/status', [CountryController::class, 'updateStatus'])->name('update-status');
        });

        // Notification Management (View Routes)
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/push', [NotificationController::class, 'pushIndex'])->name('push.index');
            Route::get('/email', [NotificationController::class, 'emailIndex'])->name('email.index');
            Route::post('/email/send', [NotificationController::class, 'sendEmail'])->name('email.send');
            Route::get('/sms', [NotificationController::class, 'smsIndex'])->name('sms.index');
            Route::get('/logs', [NotificationController::class, 'logsIndex'])->name('logs.index');
            Route::get('/subscription', [NotificationController::class, 'subscriptionIndex'])->name('subscription.index');
            Route::get('/test-push', function() { return view('admin.test-push-notifications'); })->name('test-push');
        });

        // Analytics Routes
        Route::prefix('analytics')->name('analytics.')->middleware('admin.permissions:manage_analytics')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])->name('index');
            Route::get('/users', [AnalyticsController::class, 'users'])->name('users');
            Route::get('/content', [AnalyticsController::class, 'content'])->name('content');
            Route::get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');
            Route::get('/advertising', [AnalyticsController::class, 'advertising'])->name('advertising');
            Route::get('/streaming', [AnalyticsController::class, 'streaming'])->name('streaming');
            Route::get('/export', [AnalyticsController::class, 'exportData'])->name('export');
        });

        // Admin Management (Only Super Admin and Admin roles)
        Route::prefix('admins')->name('admins.')->middleware('admin.permissions:manage_admins')->group(function () {
            Route::get('/', [AdminManagementController::class, 'index'])->name('index');
            Route::get('/create', [AdminManagementController::class, 'create'])->name('create');
            Route::post('/', [AdminManagementController::class, 'store'])->name('store');
            Route::get('/{admin}', [AdminManagementController::class, 'show'])->name('show');
            Route::get('/{admin}/edit', [AdminManagementController::class, 'edit'])->name('edit');
            Route::put('/{admin}', [AdminManagementController::class, 'update'])->name('update');
            Route::patch('/{admin}/status', [AdminManagementController::class, 'updateStatus'])->name('update-status');
            Route::patch('/{admin}/reset-password', [AdminManagementController::class, 'resetPassword'])->name('reset-password');
            Route::delete('/{admin}', [AdminManagementController::class, 'destroy'])->name('destroy');

            // AJAX API routes
            Route::prefix('api')->name('api.')->group(function () {
                Route::get('/admins', [AdminManagementController::class, 'getAdmins'])->name('admins');
                Route::patch('/bulk-status', [AdminManagementController::class, 'bulkUpdateStatus'])->name('bulk-status');
            });
        });

        // Profile Management
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::put('/update', [ProfileController::class, 'updateProfile'])->name('update');
            Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
            Route::put('/notifications', [ProfileController::class, 'updateNotifications'])->name('notifications');
            Route::delete('/image', [ProfileController::class, 'deleteProfileImage'])->name('delete-image');
            Route::get('/activity', [ProfileController::class, 'activityLog'])->name('activity');
        });

        // System Settings (Super Admin Only)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::post('/update', [SettingsController::class, 'update'])->name('update');
            Route::post('/test-email', [SettingsController::class, 'testEmail'])->name('test-email');
            Route::post('/delete-file', [SettingsController::class, 'deleteFile'])->name('delete-file');
        });

        // Logout
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

    //Route [admin.notifications.markAllRead] not defined.
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
});
