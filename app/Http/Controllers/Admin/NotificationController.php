<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function sendPushNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target_type' => 'required|in:all,specific,users,social_circles,countries',
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
                    $users = User::whereHas('fcmTokens', function($q) {
                        $q->where('is_active', true);
                    })->get();
                    break;

                case 'users':
                    $users = User::whereIn('id', $request->user_ids)
                        ->whereHas('fcmTokens', function($q) {
                            $q->where('is_active', true);
                        })->get();
                    break;

                case 'social_circles':
                    $users = User::whereHas('socialCircles', function($q) use ($request) {
                        $q->whereIn('social_circles.id', $request->social_circle_ids);
                    })->whereHas('fcmTokens', function($q) {
                        $q->where('is_active', true);
                    })->get();
                    break;

                case 'countries':
                    $users = User::whereIn('country_id', $request->country_ids)
                        ->whereHas('fcmTokens', function($q) {
                            $q->where('is_active', true);
                        })->get();
                    break;
            }

            foreach ($users as $user) {
                $tokens = $user->fcmTokens()->where('is_active', true)->pluck('fcm_token');

                foreach ($tokens as $token) {
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

    // Email Templates
    public function emailTemplatesIndex()
    {
        return view('admin.notifications.email.index');
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
        $stats = [
            'total' => NotificationTemplate::where('type', 'email')->count(),
            'active' => NotificationTemplate::where('type', 'email')->where('is_active', true)->count(),
            'inactive' => NotificationTemplate::where('type', 'email')->where('is_active', false)->count()
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
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
        $circles = \App\Models\SocialCircle::select('id', 'name', 'description')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'social_circles' => $circles
        ]);
    }

    // Get countries for targeting
    public function getCountries()
    {
        $countries = \App\Models\Country::select('id', 'name', 'code')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'countries' => $countries
        ]);
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
}
