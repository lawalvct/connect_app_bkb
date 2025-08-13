<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
        'profile_image',
        'phone',
        'status',
        'otp_code',
        'otp_expires_at',
        'last_login_at',
        'last_otp_sent_at',
        'force_password_change',
        'failed_login_attempts',
        'locked_until'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array',
        'otp_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_otp_sent_at' => 'datetime',
        'locked_until' => 'datetime',
        'force_password_change' => 'boolean',
    ];

    // Role constants
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_CONTENT_MANAGER = 'content_manager';

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Check if admin needs OTP verification (within 24 hours)
     */
    public function needsOtpVerification(): bool
    {
        if (!$this->last_login_at) {
            return true;
        }

        return $this->last_login_at->diffInHours(now()) >= 24;
    }

    /**
     * Generate and send OTP
     */
    public function generateOtp(): string
    {
        $otp = sprintf('%06d', mt_rand(100000, 999999));

        $this->update([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
            'last_otp_sent_at' => now()
        ]);

        return $otp;
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(string $otp): bool
    {
        if (!$this->otp_code || !$this->otp_expires_at) {
            return false;
        }

        if ($this->otp_expires_at->isPast()) {
            return false;
        }

        return Hash::check($otp, $this->otp_code);
    }

    /**
     * Clear OTP after verification
     */
    public function clearOtp(): void
    {
        $this->update([
            'otp_code' => null,
            'otp_expires_at' => null,
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null
        ]);
    }

    /**
     * Check if admin is locked
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Lock admin account
     */
    public function lockAccount(int $minutes = 15): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
            'failed_login_attempts' => $this->failed_login_attempts + 1
        ]);
    }

    /**
     * Check if admin has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if admin has permission
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->role === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if admin can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN) ||
               $this->hasRole(self::ROLE_ADMIN) ||
               $this->hasPermission('manage_users');
    }

    /**
     * Check if admin can manage content
     */
    public function canManageContent(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN) ||
               $this->hasRole(self::ROLE_ADMIN) ||
               $this->hasRole(self::ROLE_CONTENT_MANAGER) ||
               $this->hasPermission('manage_content');
    }

    /**
     * Check if admin can view analytics
     */
    public function canViewAnalytics(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN) ||
               $this->hasRole(self::ROLE_ADMIN) ||
               $this->hasPermission('view_analytics');
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_MODERATOR => 'Moderator',
            self::ROLE_CONTENT_MANAGER => 'Content Manager',
            default => ucfirst($this->role)
        };
    }

    /**
     * Get the FCM tokens for the admin.
     */
    public function fcmTokens()
    {
        return $this->hasMany(AdminFcmToken::class);
    }

    /**
     * Get only active FCM tokens for the admin.
     */
    public function activeFcmTokens()
    {
        return $this->hasMany(AdminFcmToken::class)->active();
    }
}
