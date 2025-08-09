<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class UserVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'id_card_type',
        'id_card_image',
        'admin_status',
        'admin_reason',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the verification
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the verification
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the full URL for the ID card image
     */
    public function getIdCardImageUrlAttribute()
    {
        if (empty($this->id_card_image)) {
            return null;
        }

        // Check if it's already a full URL
        if (filter_var($this->id_card_image, FILTER_VALIDATE_URL)) {
            return $this->id_card_image;
        }

        // Return full URL for local storage
        return url('uploads/verifyme/' . $this->id_card_image);
    }

    /**
     * Scope for pending verifications
     */
    public function scopePending($query)
    {
        return $query->where('admin_status', 'pending');
    }

    /**
     * Scope for approved verifications
     */
    public function scopeApproved($query)
    {
        return $query->where('admin_status', 'approved');
    }

    /**
     * Scope for rejected verifications
     */
    public function scopeRejected($query)
    {
        return $query->where('admin_status', 'rejected');
    }

    /**
     * Check if verification is pending
     */
    public function isPending()
    {
        return $this->admin_status === 'pending';
    }

    /**
     * Check if verification is approved
     */
    public function isApproved()
    {
        return $this->admin_status === 'approved';
    }

    /**
     * Check if verification is rejected
     */
    public function isRejected()
    {
        return $this->admin_status === 'rejected';
    }

    /**
     * Mark as approved
     */
    public function approve($reviewerId, $reason = null)
    {
        $this->update([
            'admin_status' => 'approved',
            'admin_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId,
        ]);
    }

    /**
     * Mark as rejected
     */
    public function reject($reviewerId, $reason)
    {
        $this->update([
            'admin_status' => 'rejected',
            'admin_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId,
        ]);
    }
}
