<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        Log::info('=== ADMIN LOGIN ATTEMPT START ===');
        Log::info('Request method: ' . $request->method());
        Log::info('Request URL: ' . $request->fullUrl());
        Log::info('Request data: ', $request->all());
        Log::info('Request IP: ' . $request->ip());
        Log::info('User Agent: ' . $request->userAgent());
        Log::info('=== END REQUEST INFO ===');

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            Log::info('Admin login validation failed', ['errors' => $validator->errors()]);
            return back()->withErrors($validator)->withInput();
        }

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }

        // Check if account is locked
        if ($admin->isLocked()) {
            return back()->withErrors(['email' => 'Account is temporarily locked. Please try again later.'])->withInput();
        }

        // Check if account is suspended
        if ($admin->status !== Admin::STATUS_ACTIVE) {
            return back()->withErrors(['email' => 'Account is suspended. Contact super admin.'])->withInput();
        }

        // Verify password
        if (!Hash::check($request->password, $admin->password)) {
            $admin->lockAccount();
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }

        // Check if OTP verification is needed (24 hours rule)
        if ($admin->needsOtpVerification()) {
            // Generate and send OTP
            $otp = $admin->generateOtp();

            // Send OTP via email
            $this->sendOtpEmail($admin, $otp);

            // Store admin ID in session for OTP verification
            session(['admin_login_id' => $admin->id]);

            return redirect()->route('admin.auth.verify-otp')->with('message', 'OTP sent to your email. Please verify to continue.');
        }

        // Login directly if OTP not needed
        Auth::guard('admin')->login($admin, $request->filled('remember'));
        $admin->clearOtp();

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Show OTP verification form
     */
    public function showOtpForm()
    {
        if (!session('admin_login_id')) {
            return redirect()->route('admin.auth.login');
        }

        $admin = Admin::find(session('admin_login_id'));
        session(['admin_email' => $admin->email]);

        return view('admin.auth.otp');
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $adminId = session('admin_login_id');
        if (!$adminId) {
            return redirect()->route('admin.auth.login')->withErrors(['otp' => 'Session expired. Please login again.']);
        }

        $admin = Admin::find($adminId);
        if (!$admin) {
            return redirect()->route('admin.auth.login')->withErrors(['otp' => 'Invalid session. Please login again.']);
        }

        if (!$admin->verifyOtp($request->otp)) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP code.']);
        }

        // Clear OTP and login
        $admin->clearOtp();
        Auth::guard('admin')->login($admin);

        // Clear session
        session()->forget('admin_login_id');

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $adminId = session('admin_login_id');
        if (!$adminId) {
            return redirect()->route('admin.auth.login');
        }

        $admin = Admin::find($adminId);
        if (!$admin) {
            return redirect()->route('admin.auth.login');
        }

        // Check if last OTP was sent recently (prevent spam)
        if ($admin->last_otp_sent_at && $admin->last_otp_sent_at->diffInSeconds(now()) < 60) {
            return back()->withErrors(['otp' => 'Please wait before requesting another OTP.']);
        }

        $otp = $admin->generateOtp();
        $this->sendOtpEmail($admin, $otp);

        return back()->with('message', 'New OTP sent to your email.');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.auth.login')->with('message', 'Successfully logged out.');
    }

    /**
     * Send OTP email
     */
    private function sendOtpEmail(Admin $admin, string $otp)
    {
        try {
            Mail::send('admin.emails.otp', ['admin' => $admin, 'otp' => $otp], function ($message) use ($admin) {
                $message->to($admin->email)
                        ->subject('ConnectApp Admin - Login Verification Code');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email: ' . $e->getMessage());
        }
    }
}
