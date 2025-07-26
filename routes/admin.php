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
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('auth.login');
        Route::get('/login-test', function() { return view('admin.auth.login-test'); })->name('auth.login-test');
        Route::get('/login-form-test', function() { return view('admin.auth.login-form-test'); })->name('auth.login-form-test');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login.post');

        // Temporary test route to bypass CSRF for testing
        Route::match(['GET', 'POST'], '/login-simple-test', function(Request $request) {
            if ($request->isMethod('GET')) {
                return '
                <html>
                <head><title>Simple Login Test</title></head>
                <body>
                    <h2>Simple Login Test (No CSRF)</h2>
                    <form method="POST" action="/admin/login-simple-test">
                        <div>
                            <label>Email:</label><br>
                            <input type="email" name="email" value="admin@connectapp.com" required>
                        </div><br>
                        <div>
                            <label>Password:</label><br>
                            <input type="password" name="password" value="admin123" required>
                        </div><br>
                        <button type="submit">Test Submit</button>
                    </form>
                    <script>
                        document.querySelector("form").addEventListener("submit", function(e) {
                            console.log("Form submitting...");
                            document.querySelector("button").textContent = "Submitting...";
                        });
                    </script>
                </body>
                </html>';
            } else {
                Log::info('Simple test login received', [
                    'method' => $request->method(),
                    'data' => $request->all(),
                    'content_type' => $request->header('Content-Type')
                ]);
                return 'SUCCESS: Form submitted! Data: ' . json_encode($request->all());
            }
        })->withoutMiddleware(['csrf'])->name('auth.login.simple-test');

        Route::any('/login-debug', function(Request $request) {
            Log::info('Login debug route hit', [
                'method' => $request->method(),
                'all_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);
            dd('Login debug route hit', $request->method(), $request->all());
        })->name('auth.login.debug')->withoutMiddleware('csrf');
        Route::get('/verify-otp', [AuthController::class, 'showOtpForm'])->name('auth.otp');
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.otp.verify');
        Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('auth.otp.resend');
    });

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
