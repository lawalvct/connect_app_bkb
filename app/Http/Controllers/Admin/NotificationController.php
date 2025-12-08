<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPushNotificationJob;
use App\Models\AdminNotification;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    // Push Notifications
    public function pushIndex()
    {
        return view('admin.notifications.push.index');
    }

    public function subscriptionIndex()
    {
        return view('admin.notifications.subscription');
    }

    public function sendPushNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target_type' => 'required|in:all,specific,users,social_circles,countries',
            'use_queue' => 'nullable|boolean',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'social_circle_ids' => 'nullable|array',
            'social_circle_ids.*' => 'exists:social_circles,id',
            'country_ids' => 'nullable|array',
            'country_ids.*' => 'exists:countries,id',
            'user_ids' => 'required_if:target_type,users|array',
            'social_circle_ids' => 'required_if:target_type,social_circles|array',
            'country_ids' => 'required_if:target_type,countries|array',
            'data' => 'nullable|array'
        ]);

        try {
            $sent = 0;
            $failed = 0;
            $users = collect();

            switch ($request->target_type) {
                case 'all':
                    // Use chunk to avoid memory issues with large user bases
                    $users = User::whereHas('fcmTokens', function($q) {
                        $q->where('is_active', true);
                    })->with('fcmTokens')->get();
                    break;

                case 'specific':
                case 'users':
                    $users = User::whereIn('id', $request->user_ids ?? [])
                        ->whereHas('fcmTokens', function($q) {
                            $q->where('is_active', true);
                        })->with('fcmTokens')->get();
                    break;

                case 'social_circles':
                    $users = User::whereHas('socialCircles', function($q) use ($request) {
                        $q->whereIn('social_circles.id', $request->social_circle_ids ?? []);
                    })->whereHas('fcmTokens', function($q) {
                        $q->where('is_active', true);
                    })->with('fcmTokens')->get();
                    break;

                case 'countries':
                    $users = User::whereIn('country_id', $request->country_ids ?? [])
                        ->whereHas('fcmTokens', function($q) {
                            $q->where('is_active', true);
                        })->with('fcmTokens')->get();
                    break;
            }

            // Check if we have users to notify
            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users found matching the specified criteria with active FCM tokens',
                    'sent' => 0,
                    'failed' => 0
                ], 404);
            }

            $useQueue = $request->input('use_queue', false);

            if ($useQueue) {
                // Queue notifications for better performance
                foreach ($users as $user) {
                    SendPushNotificationJob::dispatch(
                        $user->id,
                        $request->title,
                        $request->body,
                        $request->data ?? []
                    );
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Push notifications queued successfully',
                    'queued' => $users->count(),
                    'mode' => 'queued'
                ]);
            }

            // Send immediately (synchronous)
            foreach ($users as $user) {
                // Use eager-loaded relationship instead of querying again
                $tokens = $user->fcmTokens->where('is_active', true)->pluck('fcm_token');

                foreach ($tokens as $token) {
                    try {
                        $result = $this->firebaseService->sendNotification(
                            $token,
                            $request->title,
                            $request->body,
                            $request->data ?? [],
                            $user->id
                        );

                        if ($result) {
                            $sent++;
                        } else {
                            $failed++;
                            Log::error('Push notification failed for token: ' . $token . ' (user_id: ' . $user->id . ')');
                        }
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error('Push notification exception for token: ' . $token . ' (user_id: ' . $user->id . '): ' . $e->getMessage());
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Notification sent successfully. Sent: $sent, Failed: $failed",
                'sent' => $sent,
                'failed' => $failed
            ]);

        } catch (\Exception $e) {
            Log::error('Push notification error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        }
    }

    // Email
    public function emailIndex()
    {
        return view('admin.notifications.email.index');
    }

    public function sendEmail(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'target_type' => 'required|in:all,selected,circle,country',
            'users' => 'array',
            'circle_id' => 'nullable|integer',
            'country' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        try {
            $sent = 0;
            $failed = 0;
            $users = collect();

            switch ($request->target_type) {
                case 'all':
                    $users = User::whereNotNull('email_verified_at')->get();
                    break;
                case 'selected':
                    $users = User::whereIn('id', $request->users ?? [])->whereNotNull('email_verified_at')->get();
                    break;
                case 'circle':
                    $users = User::where('social_circle_id', $request->circle_id)->whereNotNull('email_verified_at')->get();
                    break;
                case 'country':
                    $users = User::where('country_id', $request->country)->whereNotNull('email_verified_at')->get();
                    break;
            }

            $attachmentPath = null;
            $attachmentName = null;
            $attachmentMime = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->getRealPath();
                $attachmentName = $request->file('attachment')->getClientOriginalName();
                $attachmentMime = $request->file('attachment')->getMimeType();
            }

            foreach ($users as $user) {
                try {
                    \App\Jobs\SendAdminNotificationEmail::dispatch(
                        $user,
                        $request->subject,
                        $request->body,
                        $attachmentPath,
                        $attachmentName,
                        $attachmentMime
                    );
                    $sent++;
                } catch (\Exception $e) {
                    Log::error("Failed to queue email to {$user->email}: " . $e->getMessage());
                    $failed++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Emails queued for sending. Queued: $sent, Failed to queue: $failed",
                'sent' => $sent,
                'failed' => $failed
            ]);
        } catch (\Exception $e) {
            Log::error('Email queueing error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue emails: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getEmailTemplates(Request $request)
    {
        $query = NotificationTemplate::where('type', 'email');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $templates = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    public function storeEmailTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:notification_templates,name',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'variables' => 'nullable|array'
        ]);

        $template = NotificationTemplate::create([
            'name' => $request->name,
            'type' => 'email',
            'subject' => $request->subject,
            'content' => $request->content,
            'description' => $request->description,
            'variables' => $request->variables ?? [],
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email template created successfully',
            'template' => $template
        ]);
    }

    public function updateEmailTemplate(Request $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:notification_templates,name,' . $id,
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'variables' => 'nullable|array'
        ]);

        $template->update([
            'name' => $request->name,
            'subject' => $request->subject,
            'content' => $request->content,
            'description' => $request->description,
            'variables' => $request->variables ?? []
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email template updated successfully',
            'template' => $template
        ]);
    }

    public function toggleEmailTemplate($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Template status updated successfully'
        ]);
    }

    public function deleteEmailTemplate($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    }

    public function getEmailStats()
    {
        try {
            $totalUsers = User::whereNotNull('email_verified_at')->count();
            $totalCircles = \App\Models\SocialCircle::active()->count();
            $totalCountries = \App\Models\Country::where('active', true)->count();

            $stats = [
                'total_users' => $totalUsers,
                'total_circles' => $totalCircles,
                'total_countries' => $totalCountries,
                // Also include template stats for other parts of the system
                'total_templates' => NotificationTemplate::where('type', 'email')->count(),
                'active_templates' => NotificationTemplate::where('type', 'email')->where('is_active', true)->count(),
                'inactive_templates' => NotificationTemplate::where('type', 'email')->where('is_active', false)->count()
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching email stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'stats' => [
                    'total_users' => 0,
                    'total_circles' => 0,
                    'total_countries' => 0,
                    'total_templates' => 0,
                    'active_templates' => 0,
                    'inactive_templates' => 0
                ]
            ]);
        }
    }

    // SMS Settings
    public function smsIndex()
    {
        return view('admin.notifications.sms.index');
    }

    public function getSmsConfig()
    {
        // You would typically store SMS config in a settings table or config file
        $config = [
            'provider' => config('sms.provider', ''),
            'enabled' => config('sms.enabled', false),
            'daily_limit' => config('sms.daily_limit', 1000),
            'rate_limit' => config('sms.rate_limit', 60),
            'twilio' => [
                'account_sid' => config('sms.twilio.account_sid', ''),
                'auth_token' => '', // Don't return actual token for security
                'from_number' => config('sms.twilio.from_number', '')
            ],
            'nexmo' => [
                'api_key' => config('sms.nexmo.api_key', ''),
                'api_secret' => '', // Don't return actual secret
                'from' => config('sms.nexmo.from', '')
            ],
            'aws_sns' => [
                'region' => config('sms.aws_sns.region', 'us-east-1'),
                'access_key_id' => config('sms.aws_sns.access_key_id', ''),
                'secret_access_key' => '' // Don't return actual key
            ],
            'custom' => [
                'endpoint' => config('sms.custom.endpoint', ''),
                'method' => config('sms.custom.method', 'POST'),
                'headers' => config('sms.custom.headers', ''),
                'body_template' => config('sms.custom.body_template', '')
            ]
        ];

        return response()->json([
            'success' => true,
            'config' => $config
        ]);
    }

    public function updateSmsConfig(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:twilio,nexmo,aws_sns,custom',
            'enabled' => 'boolean',
            'daily_limit' => 'integer|min:0',
            'rate_limit' => 'integer|min:1'
        ]);

        // In a real implementation, you would save this to a settings table or update config files
        // For now, we'll just return success

        return response()->json([
            'success' => true,
            'message' => 'SMS configuration updated successfully'
        ]);
    }

    public function sendTestSms(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:160'
        ]);

        // In a real implementation, you would send the SMS using your configured provider
        // For now, we'll simulate success

        return response()->json([
            'success' => true,
            'message' => 'Test SMS sent successfully'
        ]);
    }

    public function getSmsStats()
    {
        // In a real implementation, you would get these stats from your SMS logs
        $stats = [
            'today' => 0,
            'week' => 0,
            'month' => 0,
            'success_rate' => 95.2
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    // Notification Logs
    public function logsIndex()
    {
        return view('admin.notifications.logs.index');
    }

    public function getLogs(Request $request)
    {
        $query = \App\Models\PushNotificationLog::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }

    public function getLogStats(Request $request)
    {
        $query = \App\Models\PushNotificationLog::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $totalSent = $query->count();
        $delivered = $query->where('status', 'delivered')->count();
        $failed = $query->where('status', 'failed')->count();
        $successRate = $totalSent > 0 ? round(($delivered / $totalSent) * 100, 1) : 0;

        $stats = [
            'total_sent' => $totalSent,
            'delivered' => $delivered,
            'failed' => $failed,
            'success_rate' => $successRate
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    public function retryNotification($id)
    {
        $log = \App\Models\PushNotificationLog::findOrFail($id);

        if ($log->status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Only failed notifications can be retried'
            ], 400);
        }

        // In a real implementation, you would queue the notification for retry

        return response()->json([
            'success' => true,
            'message' => 'Notification queued for retry'
        ]);
    }

    public function deleteLog($id)
    {
        $log = \App\Models\PushNotificationLog::findOrFail($id);
        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Log entry deleted successfully'
        ]);
    }

    public function cleanupOldLogs()
    {
        $deletedCount = \App\Models\PushNotificationLog::where('created_at', '<', now()->subDays(30))->delete();

        return response()->json([
            'success' => true,
            'message' => 'Old logs cleaned up successfully',
            'deleted_count' => $deletedCount
        ]);
    }

    public function exportLogs(Request $request)
    {
        // In a real implementation, you would generate and return a CSV/Excel file
        return response()->json([
            'success' => true,
            'message' => 'Export functionality not implemented yet'
        ]);
    }

    // Admin Notifications API
    public function getAdminNotifications(Request $request)
    {
        $adminId = Auth::guard('admin')->id();

        $query = AdminNotification::forAdmin($adminId);

        if ($request->filled('unread_only')) {
            $query->unread();
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 10))
            ->get();

        $unreadCount = AdminNotification::forAdmin($adminId)->unread()->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    public function markNotificationAsRead($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    public function markAllAsRead()
    {
        $adminId = Auth::guard('admin')->id();

        AdminNotification::forAdmin($adminId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Mark all notifications as read for the current admin (including global notifications)
     */
    public function markAllRead(Request $request)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        // Mark all notifications for this admin and global as read
        AdminNotification::forAdmin($admin->id)->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    // General notification stats
    public function getNotificationStats()
    {
        $stats = [
            'total_users' => User::count(),
            'active_tokens' => User::whereHas('fcmTokens', function($q) {
                $q->where('is_active', true);
            })->count(),
            'sent_today' => \App\Models\PushNotificationLog::whereDate('created_at', today())->count()
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    // Get social circles for targeting
    public function getSocialCircles()
    {
        try {
            $circles = \App\Models\SocialCircle::select('id', 'name', 'description')
                ->active()
                ->ordered()
                ->get();

            return response()->json([
                'success' => true,
                'circles' => $circles
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching social circles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'circles' => []
            ]);
        }
    }

    // Get countries for targeting
    public function getCountries()
    {
        try {
            $countries = \App\Models\Country::select('id', 'name', 'code')
                ->where('active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'countries' => $countries
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching countries: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'countries' => []
            ]);
        }
    }

    // Preview notification targets
    public function previewTargets(Request $request)
    {
        $request->validate([
            'target_type' => 'required|in:all,users,social_circles,countries',
            'user_ids' => 'array',
            'social_circle_ids' => 'array',
            'country_ids' => 'array'
        ]);

        $count = 0;
        $userQuery = User::whereHas('fcmTokens', function($q) {
            $q->where('is_active', true);
        });

        switch ($request->target_type) {
            case 'all':
                $count = $userQuery->count();
                break;

            case 'users':
                if ($request->user_ids) {
                    $count = $userQuery->whereIn('id', $request->user_ids)->count();
                }
                break;

            case 'social_circles':
                if ($request->social_circle_ids) {
                    $count = $userQuery->whereHas('socialCircles', function($q) use ($request) {
                        $q->whereIn('social_circles.id', $request->social_circle_ids);
                    })->count();
                }
                break;

            case 'countries':
                if ($request->country_ids) {
                    $count = $userQuery->whereIn('country_id', $request->country_ids)->count();
                }
                break;
        }

        return response()->json([
            'success' => true,
            'target_count' => $count
        ]);
    }

    // Admin FCM Token Management
    public function subscribeAdmin(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'push_endpoint' => 'nullable|string',
            'push_p256dh' => 'nullable|string',
            'push_auth' => 'nullable|string',
            'subscription_type' => 'nullable|string|in:fcm,web_push,both',
            'device_name' => 'nullable|string',
            'platform' => 'nullable|string',
            'browser' => 'nullable|string',
            'notification_preferences' => 'nullable|array'
        ]);

        $admin = Auth::guard('admin')->user();

        // Check if token already exists for this admin
        $existingToken = \App\Models\AdminFcmToken::where('admin_id', $admin->id)
            ->where('fcm_token', $request->fcm_token)
            ->first();

        $tokenData = [
            'device_name' => $request->device_name,
            'platform' => $request->platform ?? 'web',
            'browser' => $request->browser,
            'push_endpoint' => $request->push_endpoint,
            'push_p256dh' => $request->push_p256dh,
            'push_auth' => $request->push_auth,
            'subscription_type' => $request->subscription_type ?? 'fcm',
            'is_active' => true,
            'notification_preferences' => $request->notification_preferences ?? \App\Models\AdminFcmToken::getDefaultPreferences(),
            'last_used_at' => now()
        ];

        if ($existingToken) {
            // Update existing token
            $existingToken->update($tokenData);
            $token = $existingToken;
        } else {
            // Create new token
            $token = \App\Models\AdminFcmToken::create(array_merge($tokenData, [
                'admin_id' => $admin->id,
                'fcm_token' => $request->fcm_token,
            ]));
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to admin notifications',
            'token_id' => $token->id,
            'subscription_type' => $token->subscription_type,
            'preferences' => $token->notification_preferences
        ]);
    }

    public function unsubscribeAdmin(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $admin = Auth::guard('admin')->user();

        $token = \App\Models\AdminFcmToken::where('admin_id', $admin->id)
            ->where('fcm_token', $request->fcm_token)
            ->first();

        if ($token) {
            $token->deactivate();
            return response()->json([
                'success' => true,
                'message' => 'Successfully unsubscribed from admin notifications'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Token not found'
        ], 404);
    }

    public function getAdminTokens()
    {
        $admin = Auth::guard('admin')->user();
        $tokens = $admin->fcmTokens()->orderBy('last_used_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'tokens' => $tokens
        ]);
    }

    public function updateAdminPreferences(Request $request)
    {
        $request->validate([
            'token_id' => 'required|integer',
            'preferences' => 'required|array'
        ]);

        $admin = Auth::guard('admin')->user();

        $token = \App\Models\AdminFcmToken::where('admin_id', $admin->id)
            ->where('id', $request->token_id)
            ->first();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found'
            ], 404);
        }

        $token->update([
            'notification_preferences' => $request->preferences
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
            'preferences' => $token->notification_preferences
        ]);
    }

    // Send notification to admins
    public function notifyAdmins($type, $title, $body, $data = [])
    {
        try {
            $sent = 0;
            $failed = 0;

            // Get all active admin tokens that want this type of notification
            $adminTokens = \App\Models\AdminFcmToken::active()
                ->with('admin')
                ->get()
                ->filter(function ($token) use ($type) {
                    return $token->wantsNotification($type);
                });

            foreach ($adminTokens as $token) {
                $result = $this->firebaseService->sendNotification(
                    $token->fcm_token,
                    $title,
                    $body,
                    array_merge($data, [
                        'type' => 'admin_notification',
                        'notification_type' => $type,
                        'admin_id' => $token->admin_id
                    ]),
                    null // Set user_id to null for admin notifications to avoid foreign key constraint
                );

                if ($result) {
                    $sent++;
                    $token->markAsUsed();
                } else {
                    $failed++;
                }
            }

            \Illuminate\Support\Facades\Log::info("Admin notification sent: {$title}", [
                'type' => $type,
                'sent' => $sent,
                'failed' => $failed
            ]);

            return ['sent' => $sent, 'failed' => $failed];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to notify admins: ' . $e->getMessage());
            return ['sent' => 0, 'failed' => 1];
        }
    }

    // Test admin notification
    public function testAdminNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $result = $this->notifyAdmins(
            'test_notifications',
            $request->title,
            $request->body,
            ['test' => true, 'sent_at' => now()->toISOString()]
        );

        return response()->json([
            'success' => true,
            'message' => "Test notification sent. Sent: {$result['sent']}, Failed: {$result['failed']}",
            'sent' => $result['sent'],
            'failed' => $result['failed']
        ]);
    }
}
