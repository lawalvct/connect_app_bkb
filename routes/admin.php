<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;

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
            Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus']);
            Route::patch('/users/bulk-status', [UserManagementController::class, 'bulkUpdateStatus']);
            Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword']);
            Route::post('/users/{user}/login-as', [UserManagementController::class, 'loginAsUser']);
        });

        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
            Route::patch('/{user}/suspend', [UserManagementController::class, 'suspend'])->name('suspend');
            Route::patch('/{user}/activate', [UserManagementController::class, 'activate'])->name('activate');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        });

        // Logout
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });
});
