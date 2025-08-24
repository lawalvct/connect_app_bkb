<?php
namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Requests\V1\RegisterRequest;
use App\Http\Resources\V1\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\AuthenticationException;
use App\Helpers\S3UploadHelper;
use App\Helpers\SymlinkUploadHelper;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use App\Mail\WelcomeEmail;
use App\Mail\VerificationEmail;
use App\Models\User;
use App\Models\AdminNotification;
use App\Models\Admin;
use App\Models\UserProfileUpload;
use Illuminate\Support\Facades\Auth;
use App\Services\EmailValidationService;
use App\Services\RecaptchaService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use illuminate\Support\Str;



use App\Http\Requests\V1\RegisterStep1Request;
use App\Http\Requests\V1\RegisterStep2Request;
use App\Http\Requests\V1\RegisterStep3Request;
use App\Http\Requests\V1\RegisterStep4Request;
use App\Http\Requests\V1\RegisterStep5Request;
use App\Http\Requests\V1\RegisterStep6Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\StorageUploadHelper;
use Illuminate\Support\Facades\Log;

class AuthController extends BaseController
{
    protected $authService;
    protected $recaptchaService;
    protected $emailValidationService;


    public function __construct(
        AuthService $authService,
        RecaptchaService $recaptchaService,
        EmailValidationService $emailValidationService
    ) {
        $this->authService = $authService;
        $this->recaptchaService = $recaptchaService;
        $this->emailValidationService = $emailValidationService;
    }

  /**
 * Register a new user
 *
 * @param RegisterRequest $request
 * @return \Illuminate\Http\JsonResponse
 */
public function register(RegisterRequest $request)
{
    try {

       // dd($request);
        // Verify reCAPTCHA
        // if (!$this->recaptchaService->verify($request->recaptcha_token)) {
        //     return $this->sendError('Bot verification failed. Please try again.', null, 400);
        // }

        // Check for suspicious email
        // if (!$this->emailValidationService->isValidEmail($request->email)) {
        //     return $this->sendError('Invalid or suspicious email address.', null, 400);
        // }

        // Prepare registration data
        $registrationData = $request->validated();

       if ($request->hasFile('profile_media')) {
    try {
        $profileImage = $request->file('profile_media');

        // Validate file
        if (!$profileImage->isValid()) {
            return $this->sendError('Invalid profile image: ' . $profileImage->getErrorMessage(), null, 400);
        }

        // Log file details for debugging
        Log::info('Processing profile image upload', [
            'original_name' => $profileImage->getClientOriginalName(),
            'size' => $profileImage->getSize(),
            'mime_type' => $profileImage->getMimeType()
        ]);

        // Upload file using our helper to public/uploads/profiles
        $fileData = StorageUploadHelper::uploadFile(
            $profileImage,
            'profiles'
        );

        if ($fileData['success']) {
            // Store the filename in 'profile' column and the directory path in 'profile_url' column
            $registrationData['profile'] = $fileData['filename'];
            $registrationData['profile_url'] = 'uploads/profiles/';

            Log::info('Profile image upload successful', [
                'filename' => $fileData['filename'],
                'path' => $fileData['path'],
                'full_url' => $fileData['full_url']
            ]);
        } else {
            Log::warning('Profile image upload failed but no exception thrown');
        }

    } catch (\Exception $e) {
        Log::error('Profile image upload failed during registration', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        // Continue registration without profile image
        Log::info('Continuing registration without profile image');
    }
}
        // Process social circles if provided
        if ($request->has('social_circles')) {
            $registrationData['social_circles'] = $request->social_circles;
        }

        $user = $this->authService->register($registrationData);

        // Add 4 default profile uploads
        $this->addDefaultProfileUploads($user);

        // Load relationships after all data is inserted
        $user->load(['country', 'profileUploads']);
        // Don't load socialCircles here to avoid the ambiguous query during registration

        // Generate OTP for email verification
        $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // Store OTP in database
        $user->email_otp = $otp;
        $user->email_otp_expires_at = now()->addHours(1); // OTP expires in 1 hour
        $user->save();


        // Queue emails instead of sending them immediately
        try {
            // The emails will be sent in the background
            Mail::to($user->email)->queue(new WelcomeEmail($user));
            Mail::to($user->email)->queue(new VerificationEmail($user, $otp));

            // Create admin notification for all admins
            $admins = Admin::all();
            foreach ($admins as $admin) {
                AdminNotification::createForAdmin($admin->id, [
                    'title' => 'New User Registration',
                    'message' => 'A new user has registered: ' . $user->name . ' (' . $user->email . ')',
                    'type' => 'user_registration',
                    'data' => [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                    ],
                    'action_url' => null,
                    'icon' => 'user-plus',
                ]);
            }

        } catch (\Exception $mailException) {
            // Log the email error but don't fail the registration
            \Log::error('Failed to queue registration emails or create admin notification: ' . $mailException->getMessage());
        }

        $token = $this->authService->createToken($user);

        return $this->sendResponse('User registered successfully. Please check your email for verification instructions.', [
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    } catch (\Exception $e) {
        return $this->sendError('Registration failed: ' . $e->getMessage(), null, 500);
    }
}
/**
 * Add default profile uploads to a user
 *
 * @param User $user
 * @return void
 */
private function addDefaultProfileUploads(User $user)
{
    // Define the default avatars - 2 images and 2 videos
    $defaultUploads = [
        [
            'file_name' => 'public',
            'file_url' => 'https://avatar.iran.liara.run/',
            'file_type' => 'image'
        ],
        [
            'file_name' => 'public',
            'file_url' => 'https://avatar.iran.liara.run/',
            'file_type' => 'image'
        ],
        [
            'file_name' => 'public',
            'file_url' => 'https://avatar.iran.liara.run/',
            'file_type' => 'image'
        ],
        [
            'file_name' => 'public',
            'file_url' => 'https://avatar.iran.liara.run/',
            'file_type' => 'image'
        ]
    ];

    // Insert the records
    foreach ($defaultUploads as $upload) {
        $upload['user_id'] = $user->id;
        UserProfileUpload::create($upload);
    }

    // Log the addition of default profile uploads
    \Log::info('Added default profile uploads for user ID: ' . $user->id);
}


    /**
     * Login user
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            // Validate credentials
            $credentials = $request->only('email', 'password');
            $remember = $request->input('remember_me', false);
            if (!Auth::attempt($credentials, $remember)) {
                throw new AuthenticationException('Invalid credentials', 401);
            }
            $user = Auth::user();

            // Save device_token to user_fcm_tokens if provided
            if ($request->filled('device_token')) {
                $fcmToken = $request['device_token'];
                $deviceId = $request['device_id'] ?? null;
                $platform = $request['platform'] ?? null;
                $appVersion = $request['app_version'] ?? null;
                \App\Models\UserFcmToken::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'fcm_token' => $fcmToken,
                    ],
                    [
                        'device_id' => $deviceId,
                        'platform' => $platform,
                        'app_version' => $appVersion,
                        'is_active' => true,
                        'last_used_at' => now(),
                    ]
                );
            }

            // Generate token
            $token = $this->authService->createToken($user);

            return $this->sendResponse('Login successful', [
                'user' => new UserResource($user),
                'token' => $token,
            ]);
        } catch (AuthenticationException $e) {
            return $this->sendError($e->getMessage(), null, $e->getCode());
        } catch (\Exception $e) {
            return $this->sendError('Login failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update device token for push notifications (for use after login)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDeviceToken(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'device_id' => 'nullable|string',
            'platform' => 'nullable|string',
            'app_version' => 'nullable|string',
        ]);
        $user = $request->user();
        $fcmToken = $request->input('device_token');
        $deviceId = $request->input('device_id');
        $platform = $request->input('platform');
        $appVersion = $request->input('app_version');
        \App\Models\UserFcmToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'fcm_token' => $fcmToken,
            ],
            [
                'device_id' => $deviceId,
                'platform' => $platform,
                'app_version' => $appVersion,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
        return response()->json(['success' => true, 'message' => 'Device token updated successfully']);
    }
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $otp = $this->authService->generatePasswordResetOTP($request->email);

            if (!$otp) {
                return $this->sendError('User not found', null, 404);
            }

            // Get the user
            $user = User::where('email', $request->email)->first();

            // Send the OTP via email
            Mail::to($request->email)->queue(new \App\Mail\ResetPasswordOTPMail($user, $otp));

            return $this->sendResponse(
                'Password reset OTP has been sent to your email',
                ['email' => $request->email]
            );
        } catch (\Exception $e) {
            return $this->sendError('Failed to send reset OTP: ' . $e->getMessage(), null, 500);
        }
    }
    /**
     * Verify password reset OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyResetOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:4'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $isValid = $this->authService->verifyPasswordResetOTP(
            $request->email,
            $request->otp
        );

        if (!$isValid) {
            return $this->sendError('Invalid OTP', null, 400);
        }

        return $this->sendResponse('OTP verified successfully', null);
    }

    /**
     * Reset password with OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:4',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $success = $this->authService->resetPasswordWithOTP(
            $request->email,
            $request->otp,
            $request->password
        );

        if (!$success) {
            return $this->sendError('Invalid OTP or reset token expired', null, 400);
        }

        return $this->sendResponse('Password has been reset successfully', null);
    }

    /**
     * Verify email with OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:4'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->sendError('User not found', null, 404);
        }

        if ($user->email_otp != $request->otp) {
            return $this->sendError('Invalid OTP', null, 400);
        }

        if ($user->email_otp_expires_at < now()) {
            return $this->sendError('OTP has expired', null, 400);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->email_otp = null;
        $user->email_otp_expires_at = null;
        $user->save();

        return $this->sendResponse('Email verified successfully', [
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Resend email verification OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user->email_verified_at) {
                return $this->sendError('Email already verified', null, 400);
            }

            $otp = $this->authService->generateEmailVerificationOTP($user);


            // Queue the email for sending
            Mail::to($user->email)->queue(new VerificationEmail($user, $otp));
            return $this->sendResponse(
                'Verification OTP has been sent to your email',
                ['email' => $user->email]
            );
        } catch (\Exception $e) {
            return $this->sendError('Failed to send verification OTP: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Redirect to OAuth provider
     *
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function redirectToProvider($provider)
    {
        try {
            $url = Socialite::driver($provider)->redirect();
            return response()->json([
                'status' => 1,
                'message' => 'Redirect URL generated successfully',
                'data' => ['url' => $url]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to generate redirect URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle provider callback
     *
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            $user = $this->authService->handleSocialLogin(
                $provider,
                null,
                [
                    'id' => $socialUser->getId(),
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar' => $socialUser->getAvatar(),
                    'username' => $socialUser->getNickname()
                ]
            );

            $token = $this->authService->createToken($user, true);

            // If API request, return JSON
            if (request()->expectsJson()) {
                return $this->sendResponse('Social login successful', [
                    'user' => new UserResource($user),
                    'token' => $token,
                ]);
            }

            // For web flow, redirect with token
            return redirect()->to(config('app.frontend_url') . '/auth/social-callback?token=' . $token);

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return $this->sendError('Social login failed: ' . $e->getMessage(), null, 500);
            }

            return redirect()->to(config('app.frontend_url') . '/auth/social-callback?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Handle social login from mobile app
     *
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleSocialLoginFromApp(Request $request, $provider)
    {
        $validator = Validator::make($request->all(), [
            'access_token' => 'required|string',
            'device_token' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $user = $this->authService->handleSocialLogin($provider, $request->access_token);

            // Update device token if provided
            if ($request->device_token) {
                $user->device_token = $request->device_token;
                $user->save();
            }

            $token = $this->authService->createToken($user, true);

            return $this->sendResponse('Social login successful', [
                'user' => new UserResource($user),
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Social login failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Handle social login with user data directly from app
     * Used when social SDK is handled on client side
     *
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleSocialLoginWithUserData(Request $request, $provider)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'avatar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            // Find or create user based on provided social data
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                    'provider_id' => $provider === 'google' ? $request->id : null,
                    'provider' => $provider,
                    // Set other social IDs based on provider
                ]);

                $user->assignRole('user');
            } else {
                // Update social ID if not set
                if ($provider === 'google' && empty($user->google_id)) {
                    $user->google_id = $request->id;
                    $user->save();
                }
                // Handle other providers similarly
            }

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->sendResponse('Social login successful', [
                'user' => new UserResource($user),
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Social login failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            // Set user offline
            $user->is_online = false;
            $user->save();

            // Revoke the token
            $request->user()->currentAccessToken()->delete();

            return $this->sendResponse('Logged out successfully', null);
        } catch (\Exception $e) {
            return $this->sendError('Logout failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get authenticated user profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser(Request $request)
    {
        try {
            $user = $request->user();

            // Update last activity
            $user->last_activity_at = now();
            $user->save();

            // Load relevant relationships
            $user->load(['country', 'socialCircles']);

            return $this->sendResponse('User profile retrieved successfully', [
                'user' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve user profile: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update user password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();

            // Verify current password
            if (!\Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Current password is incorrect', null, 400);
            }

            // Update password
            $user->password = \Hash::make($request->password);
            $user->save();

            return $this->sendResponse('Password updated successfully', null);
        } catch (\Exception $e) {
            return $this->sendError('Failed to update password: ' . $e->getMessage(), null, 500);
        }
    }

    /**
 * Resend verification email
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function resendVerificationEmail(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
    ]);

    if ($validator->fails()) {
        return $this->sendError('Validation Error', $validator->errors(), 422);
    }

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return $this->sendError('User not found', null, 404);
    }

    if ($user->email_verified_at) {
        return $this->sendError('Email already verified', null, 400);
    }

    // Generate new OTP
    $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $user->email_otp = $otp;
    $user->email_otp_expires_at = now()->addHours(1);
    $user->save();

    // Send verification email with new OTP
    Mail::to($user->email)->send(new VerificationEmail($user, $otp));

    return $this->sendResponse('Verification email sent successfully', [
        'email' => $user->email
    ]);
}

    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle()
    {
        try {
            $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
            return response()->json([
                'status' => 1,
                'message' => 'Redirect URL generated successfully',
                'data' => ['url' => $url]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to generate redirect URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find existing user or create new one
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'provider_id' => $googleUser->id,
                    'provider' => 'google',
                    'avatar' => $googleUser->avatar,
                    'username' => $googleUser->nickname,
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(), // Google accounts are already verified
                ]);

                // Assign appropriate role
             //   $user->assignRole('user');
            } else {
                // Update Google ID if not set
                if (empty($user->provider_id)) {
                    $user->provider_id = $googleUser->id;
                    $user->provider = 'google';
                    $user->save();
                }
            }

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Successfully authenticated with Google',
                'status' => 1,
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $token
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed: ' . $e->getMessage(),
                'status' => 0,
                'data' => []
            ], 500);
        }
    }



    /**
     * Step 1: Register with username, email, password
     */
    public function registerStep1(RegisterStep1Request $request)
    {
        try {
            $data = $request->validated();

            // Create user with basic info
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'device_token' => $data['device_token'] ?? null,
                'registration_step' => 1, // Track current step
            ]);

            // Generate OTP for email verification
            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $user->email_otp = $otp;
            $user->email_otp_expires_at = now()->addHours(1);
            $user->save();

            // Send verification email
            try {
                Mail::to($user->email)->queue(new VerificationEmail($user, $otp));
            } catch (\Exception $mailException) {
                \Log::error('Failed to queue verification email: ' . $mailException->getMessage());
            }

            return $this->sendResponse('Step 1 completed. Please check your email for verification code.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'next_step' => 2
            ], 201);

        } catch (\Exception $e) {
            return $this->sendError('Step 1 failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Step 2: Verify OTP
     */
    public function registerStep2(RegisterStep2Request $request)
    {
        try {
            $data = $request->validated();

            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                return $this->sendError('User not found', null, 404);
            }

            if ($user->email_otp != $data['otp']) {
                return $this->sendError('Invalid OTP', null, 400);
            }

            if ($user->email_otp_expires_at < now()) {
                return $this->sendError('OTP has expired', null, 400);
            }

            // Mark email as verified and update step
            $user->email_verified_at = now();
            $user->email_otp = null;
            $user->email_otp_expires_at = null;
            $user->registration_step = 2;
            $user->save();

            return $this->sendResponse('Email verified successfully', [
                'user_id' => $user->id,
                'next_step' => 3
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Step 2 failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Step 3: Add birth date and phone
     */
    public function registerStep3(RegisterStep3Request $request)
    {
        try {
            $data = $request->validated();

            $user = User::where('email', $data['email'])->first();

            if (!$user || $user->registration_step < 2) {
                return $this->sendError('Please complete previous steps first', null, 400);
            }

            $user->update([
                'birth_date' => $data['birth_date'],
                'phone' => $data['phone'],
                'registration_step' => 3
            ]);

            return $this->sendResponse('Step 3 completed successfully', [
                'user_id' => $user->id,
                'next_step' => 4
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Step 3 failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Step 4: Select gender
     */
    public function registerStep4(RegisterStep4Request $request)
    {
        try {
            $data = $request->validated();

            $user = User::where('email', $data['email'])->first();

            if (!$user || $user->registration_step < 3) {
                return $this->sendError('Please complete previous steps first', null, 400);
            }

            $user->update([
                'gender' => $data['gender'],
                  'country_id' => $data['country_id'],
                'registration_step' => 4
            ]);

            return $this->sendResponse('Step 4 completed successfully', [
                'user_id' => $user->id,
                'next_step' => 5
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Step 4 failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Step 5: Upload profile picture and bio
     */
    public function registerStep5(RegisterStep5Request $request)
    {
        try {
            $data = $request->validated();

            $user = User::where('email', $data['email'])->first();

            if (!$user || $user->registration_step < 4) {
                return $this->sendError('Please complete previous steps first', null, 400);
            }

            $updateData = [
                'bio' => $data['bio'] ?? null,
                'registration_step' => 5
            ];

            // Handle profile image upload (using same logic as main register function)
            if ($request->hasFile('profile_media')) {
                try {
                    $profileImage = $request->file('profile_media');

                    // Validate file
                    if (!$profileImage->isValid()) {
                        return $this->sendError('Invalid profile image: ' . $profileImage->getErrorMessage(), null, 400);
                    }

                    // Log file details for debugging
                    Log::info('Processing profile image upload in step 5', [
                        'original_name' => $profileImage->getClientOriginalName(),
                        'size' => $profileImage->getSize(),
                        'mime_type' => $profileImage->getMimeType(),
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);

                    // Upload file using our helper to public/uploads/profiles
                    $fileData = StorageUploadHelper::uploadFile(
                        $profileImage,
                        'profiles'
                    );

                    if ($fileData['success']) {
                        // Store the filename in 'profile' column and the directory path in 'profile_url' column
                        $updateData['profile'] = $fileData['filename'];
                        $updateData['profile_url'] = 'uploads/profiles/';

                        Log::info('Profile image upload successful in step 5', [
                            'user_id' => $user->id,
                            'filename' => $fileData['filename'],
                            'path' => $fileData['path'],
                            'full_url' => $fileData['full_url']
                        ]);
                    } else {
                        Log::warning('Profile image upload failed but no exception thrown in step 5', [
                            'user_id' => $user->id,
                            'file_data' => $fileData
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('Profile image upload failed during step 5', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Continue registration without profile image
                    Log::info('Continuing step 5 without profile image');
                }
            }

            // Update user data
            $updated = $user->update($updateData);

            if (!$updated) {
                Log::error('Failed to update user in step 5', [
                    'user_id' => $user->id,
                    'update_data' => $updateData
                ]);
                return $this->sendError('Failed to update user profile', null, 500);
            }

            // Refresh user data from database
            $user->refresh();

            Log::info('Step 5 completed successfully', [
                'user_id' => $user->id,
                'profile' => $user->profile,
                'profile_url' => $user->profile_url,
                'bio' => $user->bio,
                'registration_step' => $user->registration_step
            ]);

            return $this->sendResponse('Step 5 completed successfully', [
                'user_id' => $user->id,
                'profile_url' => $user->profile_url,
                'filename' => $user->profile,
                'bio' => $user->bio,
                'next_step' => 6
            ]);

        } catch (\Exception $e) {
            Log::error('Step 5 registration failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Step 5 failed: ' . $e->getMessage(), null, 500);
        }
    }


    /**
 * Step 6: Select social circles (Final step)
 */
public function registerStep6(RegisterStep6Request $request)
{
    try {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return $this->sendError('User not found with this email address', null, 404);
        }

        if ($user->registration_step < 5) {
            return $this->sendError('Please complete previous steps first. Current step: ' . $user->registration_step, null, 400);
        }

        // Validate social circles exist
        $validSocialCircles = \App\Models\SocialCircle::whereIn('id', $data['social_circles'])
            ->where('is_active', true)
            ->where('deleted_flag', 'N')
            ->pluck('id')
            ->toArray();

        if (count($validSocialCircles) !== count($data['social_circles'])) {
            return $this->sendError('Some social circles are invalid or inactive', null, 400);
        }

        // Attach social circles to user
        $user->socialCircles()->sync($data['social_circles']);

        // Mark registration as complete
        $user->update([
            'registration_step' => 6,
            'registration_completed_at' => now()
        ]);

        // Add default profile uploads
        $this->addDefaultProfileUploads($user);

        // Load relationships properly with error handling
        try {
            $user->load(['country', 'profileUploads', 'socialCircles']);
        } catch (\Exception $loadException) {
            \Log::error('Failed to load user relationships: ' . $loadException->getMessage());
            // Continue without relationships if loading fails
        }

        // Create token
        $token = $this->authService->createToken($user);

        // Send welcome email
        try {
            Mail::to($user->email)->queue(new WelcomeEmail($user));
        } catch (\Exception $mailException) {
            \Log::error('Failed to queue welcome email: ' . $mailException->getMessage());
        }

        return $this->sendResponse('Registration completed successfully!', [
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Step 6 registration failed', [
            'email' => $request->input('email'),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return $this->sendError('Step 6 failed: ' . $e->getMessage(), null, 500);
    }
}


/**
 * Debug: Check user registration status
 */
public function debugUserStatus(Request $request)
{
    $email = $request->input('email');

    if (!$email) {
        return response()->json(['error' => 'Email required']);
    }

    $user = User::where('email', $email)->first();

    if (!$user) {
        return response()->json(['error' => 'User not found']);
    }

    return response()->json([
        'user_id' => $user->id,
        'email' => $user->email,
        'registration_step' => $user->registration_step,
        'email_verified_at' => $user->email_verified_at,
        'created_at' => $user->created_at
    ]);
}



/**
 * TEMPORARY: Delete user by email (FOR DEVELOPMENT ONLY)
 * Remove this endpoint in production
 */
public function tempDeleteUserByEmail(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
    ]);

    if ($validator->fails()) {
        return $this->sendError('Validation error', $validator->errors(), 422);
    }

    try {
        // Find the user first
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->sendError('User not found', null, 404);
        }

        $userId = $user->id;
        $originalEmail = $user->email;

        // Begin transaction
        DB::beginTransaction();

        // Instead of deleting, mark as deleted and randomize email
        // This avoids the prepared statement issue completely
        $randomSuffix = '_deleted_' . time() . '_' . substr(md5(rand()), 0, 8);

        $updated = $user->update([
            'deleted_flag' => 'Y',
            'deleted_at' => now(),
            'email' => $originalEmail . $randomSuffix,
            'is_active' => false
        ]);

        if (!$updated) {
            DB::rollBack();
            return $this->sendError('Failed to delete user', null, 500);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        DB::commit();

        return $this->sendResponse('User deleted successfully', [
            'user_id' => $userId,
            'email' => $originalEmail,
            'new_email' => $user->email,
            'deleted_flag' => $user->deleted_flag,
            'deleted_at' => $user->deleted_at,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        // Log the error for debugging
        \Log::error('User deletion failed', [
            'email' => $request->email,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return $this->sendError('Failed to delete user: ' . $e->getMessage(), null, 500);
    }
}
/**
 * TEMPORARY: Get user's reset password OTP (FOR DEVELOPMENT ONLY)
 * Remove this endpoint in production
 */
public function tempGetResetOTP(Request $request)
{
    // Add environment check for safety
    if (app()->environment('production')) {
        return $this->sendError('This endpoint is not available in production', null, 403);
    }

    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email'
    ]);

    if ($validator->fails()) {
        return $this->sendError('Validation Error', $validator->errors(), 422);
    }

    try {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->sendError('User not found', null, 404);
        }

        return $this->sendResponse('User reset OTP retrieved successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'reset_otp' => $user->reset_otp,
            'email_otp' => $user->email_otp,
            'email_otp_expires_at' => $user->email_otp_expires_at,
            'registration_step' => $user->registration_step,
            'email_verified_at' => $user->email_verified_at
        ]);

    } catch (\Exception $e) {
        return $this->sendError('Failed to get reset OTP: ' . $e->getMessage(), null, 500);
    }
}
  }






