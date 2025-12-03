<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\CallController;
use App\Http\Controllers\API\V1\UserController;
use App\Http\Controllers\API\V1\PostController;
use App\Http\Controllers\API\V1\CommentController;
use App\Http\Controllers\API\V1\SocialCircleController;
use App\Http\Controllers\API\V1\ConnectionController;
use App\Http\Controllers\API\V1\StoryController;
use App\Http\Controllers\API\V1\SearchController;
use App\Http\Controllers\API\V1\NotificationController;
use App\Http\Controllers\API\V1\SubscriptionController;
use App\Http\Controllers\API\V1\MessageController;
use App\Http\Controllers\API\V1\ConversationController;
use App\Http\Controllers\API\V1\ProfileController;
use App\Http\Controllers\API\V1\AdController;
use App\Http\Controllers\API\V1\AdminAdController;
use App\Http\Controllers\API\V1\StreamController;
use App\Http\Controllers\API\V1\StreamPaymentController;
use App\Http\Controllers\API\V1\StreamChatMvpController;
use App\Http\Controllers\API\V1\SettingsController;

// Handle OPTIONS requests for CORS preflight (browser Postman compatibility)
Route::options('{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD')
        ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Cache-Control, Pragma')
        ->header('Access-Control-Expose-Headers', 'Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN')
        ->header('Access-Control-Max-Age', '86400')
        ->header('Access-Control-Allow-Credentials', 'true');
})->where('any', '.*');

// Debug route to test API v1 routes are working
Route::get('/debug-routes', function () {
    return response()->json([
        'success' => true,
        'message' => 'API v1 routes are working perfectly!',
        'timestamp' => now(),
        'environment' => app()->environment(),
        'routes_registered' => count(Route::getRoutes()),
        'cors_enabled' => true
    ]);
});

// MVP Testing Routes (Unprotected for testing with rate limiting)
Route::prefix('streams/{id}')->middleware('throttle:30,1')->group(function () {
    // Get stream chats (unprotected)
    Route::get('/chats', function ($id) {
        try {
            $stream = \App\Models\Stream::findOrFail($id);
            $chats = \App\Models\StreamChat::where('stream_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(request('limit', 20))
                ->get();

            return response()->json([
                'success' => true,
                'data' => $chats->map(function ($chat) {
                    return [
                        'id' => $chat->id,
                        'message' => $chat->message,
                        'username' => $chat->username ?? 'Anonymous',
                        'user_profile_url' => $chat->user_profile_url,
                        'is_admin' => $chat->is_admin ?? false,
                        'created_at' => $chat->created_at->toISOString(),
                        'user' => [
                            'id' => $chat->user_id,
                            'name' => $chat->username
                        ]
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Get stream viewers (unprotected with caching)
    Route::get('/viewers', function ($id) {
        try {
            $stream = \App\Models\Stream::findOrFail($id);

            // For MVP, simulate viewers with caching
            $cacheKey = "stream_viewers_{$id}";
            $viewers = cache()->remember($cacheKey, 30, function() {
                $viewerCount = rand(50, 200);
                $viewers = [];

                for ($i = 0; $i < min($viewerCount, 50); $i++) {
                    $viewers[] = [
                        'id' => $i + 1,
                        'user_id' => rand(100, 999),
                        'username' => 'Viewer' . ($i + 1),
                        'joined_at' => now()->subMinutes(rand(1, 60))->toISOString(),
                        'left_at' => null
                    ];
                }

                return [
                    'viewers' => $viewers,
                    'total_count' => $viewerCount,
                    'active_count' => $viewerCount
                ];
            });

            return response()->json([
                'success' => true,
                'viewers' => $viewers['viewers'],
                'total_count' => $viewers['total_count'],
                'active_count' => $viewers['active_count']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    });

    // Get viewer token (unprotected)
    Route::get('/viewer-token', function ($id) {
        try {
            $stream = \App\Models\Stream::findOrFail($id);

            $uid = rand(100000, 999999);

            // For MVP, return a mock token
            $token = 'mock_token_' . time() . '_' . $uid;

            return response()->json([
                'success' => true,
                'token' => $token,
                'uid' => $uid,
                'channel_name' => $stream->channel_name,
                'app_id' => env('AGORA_APP_ID')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    })->middleware('throttle:10,1');

    // Check watch access for stream (unprotected but user-aware)
    Route::get('/check-watch', function ($id) {
        try {
            $stream = \App\Models\Stream::findOrFail($id);
            $user = null;
            $hasAccess = false;
            $reason = '';

            // Check if user is authenticated
            if (auth('sanctum')->check()) {
                $user = auth('sanctum')->user();

                // Check if stream is free
                if (!$stream->is_paid || $stream->price <= 0) {
                    $hasAccess = true;
                    $reason = 'Free stream access granted';
                } else {
                    // Check if user has paid for the stream
                    $hasAccess = $stream->hasUserPaid($user);
                    $reason = $hasAccess ? 'Paid access verified' : 'Payment required to watch this premium stream';
                }
            } else {
                // Unauthenticated user - can only watch free streams
                if (!$stream->is_paid || $stream->price <= 0) {
                    $hasAccess = true;
                    $reason = 'Free stream access granted to guest user';
                } else {
                    $hasAccess = false;
                    $reason = 'Authentication and payment required for premium stream';
                }
            }

            // Additional checks
            if ($stream->status !== 'live' && $stream->status !== 'scheduled') {
                $hasAccess = false;
                $reason = 'Stream is not currently available';
            }

            return response()->json([
                'success' => true,
                'has_access' => $hasAccess,
                'reason' => $reason,
                'stream_info' => [
                    'id' => $stream->id,
                    'title' => $stream->title,
                    'status' => $stream->status,
                    'is_paid' => $stream->is_paid,
                    'price' => $stream->price,
                    'currency' => $stream->currency,
                    'free_minutes' => $stream->free_minutes ?? 0,
                ],
                'user_info' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'authenticated' => true
                ] : [
                    'authenticated' => false
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check watch access: ' . $e->getMessage()
            ], 500);
        }
    });
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register'])
    ->middleware(['throttle:5,1']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('verify-reset-otp', [AuthController::class, 'verifyResetOTP']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::get('countries', [UserController::class, 'getCountries']);
Route::get('states/{country}', [UserController::class, 'getStatesByCountry']);
Route::get('social-circles', [SocialCircleController::class, 'index']);
Route::post('verify-email', [AuthController::class, 'verifyEmail']);
Route::post('resend-verification-otp', [AuthController::class, 'resendVerificationOTP'])
    ->middleware(['throttle:5,1']);
Route::post('verify-email-otp', [AuthController::class, 'verifyEmailOTP'])
    ->middleware(['throttle:5,1']);


    // Multi-step registration routes
Route::prefix('register')->group(function () {
    Route::post('step-1', [AuthController::class, 'registerStep1']); // Username, email, password
    Route::post('step-2', [AuthController::class, 'registerStep2']); // OTP verification
    Route::post('step-3', [AuthController::class, 'registerStep3']); // Date of birth, phone
    Route::post('step-4', [AuthController::class, 'registerStep4']); // Gender
    Route::post('step-5', [AuthController::class, 'registerStep5']); // Profile picture, bio
    Route::post('step-6', [AuthController::class, 'registerStep6']); // Social circles (final)
});



// Temporary endpoints for frontend development (Remove in production)
Route::prefix('temp')->group(function () {
    Route::delete('user/delete-by-email', [AuthController::class, 'tempDeleteUserByEmail']);
    Route::post('user/get-otp', [AuthController::class, 'tempGetUserOTP']);
    Route::post('user/get-reset-otp', [AuthController::class, 'tempGetResetOTP']);
    Route::post('user/user-status', [AuthController::class, 'debugUserStatus']);
});


// Social login routes
Route::get('auth/{provider}', [AuthController::class, 'redirectToProvider']);
Route::get('auth/{provider}/callback', [AuthController::class, 'handleProviderCallback']);
Route::post('auth/{provider}/token', [AuthController::class, 'handleSocialLoginFromApp']);
Route::post('auth/{provider}/user-data', [AuthController::class, 'handleSocialLoginWithUserData']);
Route::get('timezones', [UserController::class, 'getTimezones']);

// Settings routes (Public)
Route::prefix('settings')->group(function () {
    // Public settings - no authentication required
    Route::get('public', [SettingsController::class, 'getPublicSettings']);
    Route::get('maintenance', [SettingsController::class, 'getMaintenanceStatus']);
    Route::get('app-versions', [SettingsController::class, 'getAppVersions']);
    Route::get('{key}', [SettingsController::class, 'getSetting']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('change-password', [AuthController::class, 'changePassword']);

    // User Profile
    Route::get('user', [UserController::class, 'show']);
    Route::get('user/{id}', [UserController::class, 'getUserById']);
    Route::post('profile', [ProfileController::class, 'update']);
    Route::get('profile', [ProfileController::class, 'index']);
    Route::post('profile/social-links', [ProfileController::class, 'updateSocialLinks']);
    Route::post('profile/upload', [ProfileController::class, 'uploadProfilePicture']);
    Route::post('profile/upload-multiple', [ProfileController::class, 'uploadMultipleProfilePictures']);
    Route::delete('account', [ProfileController::class, 'deleteAccount']);
    Route::post('user/timezone', [UserController::class, 'updateTimezone']);

        // Profile Images Management
        Route::get('profile/images', [ProfileController::class, 'getProfileImages']);
        Route::post('profile/picture', [ProfileController::class, 'updateProfilePicture']);
        Route::get('profile/images/{imageId}', [ProfileController::class, 'getProfileImageById']);
        Route::post('profile/images/set-main', [ProfileController::class, 'setMainProfileImage']);
        Route::delete('profile/images', [ProfileController::class, 'deleteProfileImage']);

        Route::post('profile/images/upload', [ProfileController::class, 'uploadSingleProfileImage']);
        Route::post('profile/images/upload-multiple', [ProfileController::class, 'uploadNewProfileImages']);
        Route::post('profile/images/bulk-upload', [ProfileController::class, 'bulkUploadProfileImages']);
        Route::post('profile/images/replace', [ProfileController::class, 'replaceProfileImage']);

         Route::post('profile/verify-me', [ProfileController::class, 'verifyMe']);


        Route::patch('profile/images/metadata', [ProfileController::class, 'updateProfileImageMetadata']);

    // Social Links
    Route::get('social-links', [ProfileController::class, 'getSocialLinks']);
    Route::post('social-links', [ProfileController::class, 'update']);
    Route::delete('social-links', [ProfileController::class, 'deleteSocialLink']);

    // Social Circles
    Route::get('user/social-circles', [SocialCircleController::class, 'userSocialCircles']);
    Route::post('user/social-circles', [SocialCircleController::class, 'updateUserSocialCircles']);
    Route::get('user/{id}/social-circles', [SocialCircleController::class, 'getUserSocialCircles']);

    // Settings routes (Authenticated)
    Route::prefix('settings')->group(function () {
        Route::get('all', [SettingsController::class, 'getAllSettings']);
        Route::get('email', [SettingsController::class, 'getEmailSettings']);
        Route::get('notifications', [SettingsController::class, 'getNotificationSettings']);
        Route::get('payments', [SettingsController::class, 'getPaymentSettings']);
        Route::get('apis', [SettingsController::class, 'getApiSettings']);
        Route::get('features', [SettingsController::class, 'getFeatureSettings']);
        Route::get('limits', [SettingsController::class, 'getLimitSettings']);
    });

    // Posts
    Route::prefix('posts')->group(function () {
        // Block/Unblock user posts (must be before {post} routes)
        Route::post('/block-user', [PostController::class, 'blockUserPosts']);
        Route::post('/unblock-user', [PostController::class, 'unblockUserPosts']);
        Route::get('/blocked-users', [PostController::class, 'getBlockedUsers']);

        Route::get('/feed', [PostController::class, 'getFeed']);
        Route::get('/scheduled', [PostController::class, 'getScheduledPosts']);
        Route::get('/user/{userId?}', [PostController::class, 'getUserPosts']);
        Route::post('/', [PostController::class, 'store']);
        Route::get('/{post}', [PostController::class, 'show']);
        Route::put('/{post}', [PostController::class, 'update']);
        Route::delete('/{post}', [PostController::class, 'destroy']);

        // Post interactions
        Route::post('/{post}/react', [PostController::class, 'toggleReaction']);
        Route::post('/{post}/comments', [PostController::class, 'addComment']);
        Route::get('/{post}/comments', [PostController::class, 'getComments']);
        Route::post('/{post}/report', [PostController::class, 'reportPost']);
        Route::post('/{post}/share', [PostController::class, 'sharePost']);

        // Post management
        Route::post('/{post}/publish', [PostController::class, 'publishScheduledPost']);
        Route::get('/{post}/analytics', [PostController::class, 'getPostAnalytics']);
    });

    // Discovery & User Management
    Route::prefix('users')->group(function () {
        // User details and stats (no rate limit needed)
        Route::get('/{id}/details', [ConnectionController::class, 'getUserDetailsById']);
        Route::get('/stats', [ConnectionController::class, 'getUserStats']);
        Route::get('/swipe-stats', [ConnectionController::class, 'getSwipeStats']);
        Route::post('/discover-by-post', [ConnectionController::class, 'getUsersByPost']);

        // Discovery with rate limiting
        Route::middleware(['swipe.limit'])->group(function () {
            Route::post('/discover', [ConnectionController::class, 'getUsersBySocialCircle']);
        });

        // User likes (with rate limiting since these count as swipes)
        Route::middleware(['swipe.limit'])->group(function () {
            Route::post('/{id}/like', [ConnectionController::class, 'likeUser']);
        });

        // These don't need rate limiting
        Route::get('/likes/received', [ConnectionController::class, 'getUsersWhoLikedMe']);
        Route::get('/matches', [ConnectionController::class, 'getMutualMatches']);
    });

    // Connections
    Route::prefix('connections')->group(function () {
        // Connection requests with rate limiting (since these are swipes)
        Route::middleware(['swipe.limit'])->group(function () {
            Route::post('/request', [ConnectionController::class, 'sendRequest']);
        });

        // These don't need rate limiting
        Route::get('/requests', [ConnectionController::class, 'getConnectionRequests']);
        Route::get('/incoming', [ConnectionController::class, 'getIncomingRequests']);
        Route::post('/request/{id}/respond', [ConnectionController::class, 'respondToRequest']);
        Route::get('/', [ConnectionController::class, 'getConnectedUsers']);
        Route::get('/sent', [ConnectionController::class, 'getSentRequests']);
        Route::post('/{id}/disconnect', [ConnectionController::class, 'disconnect']);
    });

    // Stories
    Route::prefix('stories')->group(function () {
        Route::post('/', [StoryController::class, 'store']);
        Route::get('/feed', [StoryController::class, 'feed']);
        Route::get('/my-stories', [StoryController::class, 'myStories']);
        Route::get('/archive', [StoryController::class, 'archive']);

        Route::prefix('{story}')->group(function () {
            Route::get('/', [StoryController::class, 'show']);
            Route::delete('/', [StoryController::class, 'destroy']);
            Route::post('/view', [StoryController::class, 'markAsViewed']);
            Route::get('/viewers', [StoryController::class, 'getViewers']);
            Route::post('/reply', [StoryController::class, 'reply']);
            Route::get('/replies', [StoryController::class, 'getReplies']);
        });
    });

    // User stories
    Route::get('users/{user}/stories', [StoryController::class, 'getUserStories']);

   // Search
Route::prefix('search')->group(function () {
    Route::get('users', [SearchController::class, 'searchUsers']);
    Route::get('posts', [SearchController::class, 'searchPosts']);
    Route::get('conversations', [SearchController::class, 'searchConversations']);
    Route::get('messages', [SearchController::class, 'searchMessages']);
    Route::get('social-circles', [SearchController::class, 'searchSocialCircles']);
    Route::get('subscription-plans', [SearchController::class, 'searchSubscriptionPlans']);
    Route::get('all', [SearchController::class, 'searchAll']); // Search across multiple content types
});

// Discovery
Route::prefix('discover')->group(function () {
    Route::get('users', [SearchController::class, 'discoverUsers']);
    Route::get('trending-posts', [SearchController::class, 'discoverTrendingPosts']);
    Route::get('suggested-connections', [SearchController::class, 'discoverSuggestedConnections']);
});

    // Notifications (Legacy system)
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // User Notifications (New system)
    Route::get('user-notifications', [NotificationController::class, 'getUserNotifications']);
    Route::get('user-notifications/count', [NotificationController::class, 'getUserNotificationCount']);
    Route::post('user-notifications/{id}/read', [NotificationController::class, 'markUserNotificationAsRead']);
    Route::post('user-notifications/read-all', [NotificationController::class, 'markAllUserNotificationsAsRead']);

    // Subscription routes
    Route::prefix('subscriptions')->group(function () {
        // Public routes
        Route::get('/', [SubscriptionController::class, 'index']);


            Route::get('/user', [SubscriptionController::class, 'userSubscriptions']);
            Route::get('/features', [SubscriptionController::class, 'getFeatures']);

            // Payment routes
            Route::post('/stripe/initialize', [SubscriptionController::class, 'initializeStripePayment']);

              Route::post('/stripe/initialize', [SubscriptionController::class, 'initializeStripeWithPaymentLink']);


            Route::post('/nomba/initialize', [SubscriptionController::class, 'initializeNombaPayment']);
            Route::post('/nomba/initialize-ngn', [SubscriptionController::class, 'initializeNombaPaymentNGN']); // Direct NGN payment
            Route::post('/nomba/initialize-usd', [SubscriptionController::class, 'initializeNombaPaymentUSD']); // Direct USD payment
            Route::post('/verify', [SubscriptionController::class, 'verifyPayment']);

            // Subscription management
            Route::post('/{id}/cancel', [SubscriptionController::class, 'cancel']);
            Route::post('/restore', [SubscriptionController::class, 'restore']);
            Route::post('/boost/activate', [SubscriptionController::class, 'activateBoost']);



    });

    // Messaging Routes
    Route::prefix('conversations')->group(function () {
        // Conversation management
        Route::get('/', [ConversationController::class, 'index']);
        Route::post('/', [ConversationController::class, 'store']);
        Route::get('/{id}', [ConversationController::class, 'show']);
        Route::post('/{id}/leave', [ConversationController::class, 'leave']);

        // Messages within conversations
        Route::get('/{id}/messages', [MessageController::class, 'index']);
        Route::post('/{id}/messages', [MessageController::class, 'store']);
        Route::post('/{id}/messages/read', [MessageController::class, 'markAsRead']);
        Route::delete('/{id}/messages/{messageId}', [MessageController::class, 'destroy']);
    });

    // Direct messaging shortcuts
    Route::prefix('messages')->group(function () {
        Route::get('/', [ConversationController::class, 'index']); // Alias for conversations
        Route::post('/send', [MessageController::class, 'sendDirectMessage']); // For quick messaging
    });

    // Call routes
    Route::prefix('calls')->group(function () {
        // Call management
        Route::post('initiate', [CallController::class, 'initiate']);
        Route::post('{call}/answer', [CallController::class, 'answer']);
        Route::post('{call}/reject', [CallController::class, 'reject']);
        Route::post('{call}/end', [CallController::class, 'end']);

        // Call history
        Route::get('history', [CallController::class, 'getUserCallHistory']);
        Route::get('conversation/{conversation}/history', [CallController::class, 'getConversationCallHistory']);

        // Call participants
        Route::get('{call}/participants', [CallController::class, 'getCallParticipants']);
        Route::post('{call}/participants/{user}/kick', [CallController::class, 'kickParticipant']);
    });




    // Live Streaming Routes
    Route::prefix('streams')->group(function () {
        // Public stream routes (no auth needed for discovery)
        Route::get('/latest', [StreamController::class, 'latest']);
        Route::get('/upcoming', [StreamController::class, 'upcoming']);
        Route::get('/mystreams', [StreamController::class, 'show']);
        Route::get('/{id}/status', [StreamController::class, 'status']);
        Route::get('/{id}/viewers', [StreamController::class, 'viewers']);
        Route::get('/{id}/chat', [StreamController::class, 'getChat']);
        Route::get('/{id}/chats', [\App\Http\Controllers\API\V1\StreamChatController::class, 'getMessages']);
        Route::get('/{id}/chat-stats', [\App\Http\Controllers\API\V1\StreamChatController::class, 'getChatStats']);

        // MVP Chat routes (no authentication required for testing)
        Route::post('/{id}/mvp-chat', [\App\Http\Controllers\API\V1\StreamChatMvpController::class, 'store']);
        Route::get('/{id}/mvp-chats', [\App\Http\Controllers\API\V1\StreamChatMvpController::class, 'index']); // No auth middleware, public route

        // Authenticated stream routes
        Route::middleware('auth:sanctum')->group(function () {
            // Stream management (Admin only - middleware will be checked in controller)
            Route::post('/', [StreamController::class, 'store']);
            Route::put('/{id}', [StreamController::class, 'update']);
            Route::delete('/{id}', [StreamController::class, 'destroy']);
            Route::post('/{id}/start', [StreamController::class, 'start']);
            Route::post('/{id}/end', [StreamController::class, 'end']);
            Route::get('/my-streams', [StreamController::class, 'myStreams']);

            // Stream participation (All users)
            Route::post('/{id}/join', [StreamController::class, 'join']);
            Route::post('/{id}/leave', [StreamController::class, 'leave']);
            Route::get('/{id}/check-watch', [StreamController::class, 'checkWatchAccess']);
            Route::post('/{id}/chat', [\App\Http\Controllers\API\V1\StreamChatController::class, 'sendMessage']);
            Route::delete('/{streamId}/chats/{messageId}', [\App\Http\Controllers\API\V1\StreamChatController::class, 'deleteMessage']);

            // Stream interactions (likes, dislikes, shares)
            Route::post('/{id}/like', [StreamController::class, 'likeStream']);
            Route::post('/{id}/dislike', [StreamController::class, 'dislikeStream']);
            Route::post('/{id}/share', [StreamController::class, 'shareStream']);
            Route::get('/{id}/interactions', [StreamController::class, 'getInteractionStats']);
            Route::get('/{id}/shares', [StreamController::class, 'getStreamShares']);
            Route::delete('/{id}/interactions', [StreamController::class, 'removeInteraction']);

            // Stream payments
            Route::prefix('{id}/payment')->group(function () {
                Route::post('/stripe/initialize', [StreamPaymentController::class, 'initializeStripePayment']);
                Route::post('/nomba/initialize', [StreamPaymentController::class, 'initializeNombaPayment']);
            });

            // Payment management
            Route::prefix('payments')->group(function () {
                Route::post('/verify', [StreamPaymentController::class, 'verifyPayment']);
                Route::get('/{paymentId}/status', [StreamPaymentController::class, 'getPaymentStatus']);
                Route::get('/my-payments', [StreamPaymentController::class, 'getUserPayments']);
            });
        });
    });

    // Stream payment webhooks (no auth needed)
    Route::prefix('streams/webhooks')->group(function () {
        Route::post('/stripe', [StreamPaymentController::class, 'handleStripeWebhook']);
        Route::post('/nomba', [StreamPaymentController::class, 'handleNombaWebhook']);
    });

    // User Advertising Routes
    Route::prefix('ads')->group(function () {
        // Dashboard and listing
        Route::get('/dashboard', [AdController::class, 'dashboard']);
        Route::get('/', [AdController::class, 'index']);
        Route::get('/export', [AdController::class, 'export']);

        // CRUD operations
        Route::post('/', [AdController::class, 'store']);
        Route::get('/{id}', [AdController::class, 'show']);
        Route::put('/{id}', [AdController::class, 'update']);
        Route::delete('/{id}', [AdController::class, 'destroy']);

        // Ad management actions
        Route::post('/{id}/pause', [AdController::class, 'pause']);
        Route::post('/{id}/resume', [AdController::class, 'resume']);
        Route::post('/{id}/stop', [AdController::class, 'stop']);

        // Analytics and preview
        Route::get('/{id}/preview', [AdController::class, 'preview']);
        Route::get('/{id}/analytics', [AdController::class, 'analytics']);


        Route::post('/{id}/payment', [AdController::class, 'initiatePayment']);
        Route::get('/{id}/payment/{paymentId}/status', [AdController::class, 'getPaymentStatus']);
        Route::post('/{id}/payment/{paymentId}/verify', [AdController::class, 'verifyPayment']);
        Route::get('/{id}/payments', [AdController::class, 'getPaymentHistory']);

                // Webhook routes (no auth middleware)
                Route::post('/payment/webhook/nomba', [AdController::class, 'handleNombaWebhook'])
                ->withoutMiddleware(['auth:sanctum'])
                ->name('ads.payment.callback.nomba');
                Route::post('/payment/webhook/stripe', [AdController::class, 'handleStripeWebhook'])
                ->withoutMiddleware(['auth:sanctum']);

            // Analytics routes
            Route::prefix('analytics')->group(function () {
                Route::get('impressions-overtime', [AdController::class, 'getImpressionsOvertime']);
                Route::get('available-years', [AdController::class, 'getAvailableYears']);
                Route::get('comparison', [AdController::class, 'getYearComparison']);
            });
    });

     // Ad Tracking Routes (for recording impressions and clicks)
     Route::prefix('ads/tracking')->group(function () {
        Route::post('/{id}/impression', [AdController::class, 'trackImpression']);
        Route::post('/{id}/click', [AdController::class, 'trackClick']);
         Route::post('/{id}/conversion', [AdController::class, 'trackConversion']);
    });


      // Get ads for social circle feeds
      Route::get('social-circles/{id}/ads', [AdController::class, 'getAdsForSocialCircle']);
      Route::post('ads/for-circles', [AdController::class, 'getAdsForSocialCircles']);

      Route::get('posts/feed-with-ads', [PostController::class, 'getFeedWithAds']);


    // Admin Advertising Routes (Add role-based middleware as needed)
    Route::prefix('admin/ads')->group(function () {
        Route::get('/', [AdminAdController::class, 'index']);
        Route::get('/dashboard', [AdminAdController::class, 'dashboard']);
        Route::post('/{id}/approve', [AdminAdController::class, 'approve']);
        Route::post('/{id}/reject', [AdminAdController::class, 'reject']);
    });

       // uncomment later for admin role base
    //    Route::prefix('admin/ads')->middleware(['role:admin'])->group(function () {
    //     Route::get('/', [AdminAdController::class, 'index']);
    //     Route::get('/dashboard', [AdminAdController::class, 'dashboard']);
    //     Route::post('/{id}/approve', [AdminAdController::class, 'approve']);
    //     Route::post('/{id}/reject', [AdminAdController::class, 'reject']);
    // });

});





    // Test service - Remove this in production
    Route::get('test-agora', function () {
        return \App\Helpers\AgoraHelper::testTokenGeneration();
    });

    Route::get('test-agora-user/{userId}', function ($userId) {
        return \App\Helpers\AgoraHelper::generateTokenForUser(
            $userId,
            'test_channel_' . time(),
            3600,
            'publisher'
        );
    });

    // Add this temporarily for testing
    Route::get('/test-userhelper', function() {
        try {
            $result = \App\Helpers\UserHelper::testMethod();
            return response()->json(['message' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    });



  // Webhook routes (no auth needed)
  // Accept both GET and POST for Nomba callbacks since they may send either
  Route::match(['get', 'post'], '/nomba/callback', [SubscriptionController::class, 'handleNombaCallbackWeb'])->name('api.v1.subscriptions.nomba.callback');
  Route::match(['get', 'post'], '/nomba/callback/web', [SubscriptionController::class, 'handleNombaCallbackWeb'])->name('api.v1.subscriptions.nomba.callback.web');
  Route::post('/stripe/subscription/webhook', [SubscriptionController::class, 'stripeWebhook']);

  // Stripe redirect routes (no auth needed - handles success/cancel from Stripe checkout)
  Route::get('/subscriptions/stripe/success', [SubscriptionController::class, 'handleStripeSuccess'])->name('api.v1.subscriptions.stripe.success');
  Route::get('/subscriptions/stripe/cancel', [SubscriptionController::class, 'handleStripeCancel'])->name('api.v1.subscriptions.stripe.cancel');


  //testion if everything is fine with git
