<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AdPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ad_id',
        'amount',
        'currency',
        'amount_usd',
        'exchange_rate',
        'payment_gateway',
        'payment_method',
        'status',
        'gateway_reference',
        'gateway_transaction_id',
        'gateway_response',
        'paid_at',
        'expires_at',
        'payment_link',
        'failure_reason',
        'ip_address',
        'user_agent',
          'external_callback_url',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Methods
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now()
        ]);

        // Update ad status to pending_review
        $this->ad->update(['status' => 'pending_review']);
    }

    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason
        ]);
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canRetry()
    {
        return in_array($this->status, ['failed', 'cancelled']) && !$this->isExpired();
    }
}
