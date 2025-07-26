<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\StoryManagementController;
use App\Http\Controllers\Admin\AdManagementController;
use App\Http\Controllers\Admin\SubscriptionManagementController;

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
            Route::get('/dashboard-data', [DashboardController::class, 'getDashboardData']);
            Route::get('/dashboard-charts', [DashboardController::class, 'getChartData']);
            Route::get('/users', [UserManagementController::class, 'getUsers']);
            Route::get('/social-circles', [UserManagementController::class, 'getSocialCircles']);
            Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus']);
            Route::patch('/users/bulk-status', [UserManagementController::class, 'bulkUpdateStatus']);
            Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword']);

            // Post Management API
            Route::get('/posts', [\App\Http\Controllers\Admin\PostManagementController::class, 'getPosts']);
            Route::patch('/posts/{post}/status', [\App\Http\Controllers\Admin\PostManagementController::class, 'updateStatus']);
            Route::patch('/posts/bulk-status', [\App\Http\Controllers\Admin\PostManagementController::class, 'bulkUpdateStatus']);

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

            // Subscription Management API
            Route::get('/subscriptions', [SubscriptionManagementController::class, 'getSubscriptions']);
            Route::get('/subscriptions/stats', [SubscriptionManagementController::class, 'getStats']);
            Route::get('/subscription-plans', [SubscriptionManagementController::class, 'getPlans']);
            Route::get('/subscription-plans/stats', [SubscriptionManagementController::class, 'getPlanStats']);
        });

        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/export', [UserManagementController::class, 'export'])->name('export');
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

                Route::get('/get', [SubscriptionManagementController::class, 'getPlans'])->name('get');
                Route::get('/stats', [SubscriptionManagementController::class, 'getPlanStats'])->name('stats');
                Route::get('/export', [SubscriptionManagementController::class, 'exportPlans'])->name('export');
                Route::get('/{plan}', [SubscriptionManagementController::class, 'showPlan'])->name('show');
                Route::put('/{plan}/status', [SubscriptionManagementController::class, 'updatePlanStatus'])->name('update-status');

            });
        });

        // Logout
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });
});
