<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StreamPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stream_id',
        'amount',
        'currency',
        'reference',
        'payment_gateway',
        'gateway_transaction_id',
        'status',
        'gateway_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
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

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStream($query, $streamId)
    {
        return $query->where('stream_id', $streamId);
    }

    // Generate unique reference
    public static function generateReference(): string
    {
        do {
            $reference = 'STREAM_' . strtoupper(Str::random(12));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    // Mark payment as completed
    public function markAsCompleted(string $transactionId = null, array $gatewayResponse = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'gateway_transaction_id' => $transactionId,
            'gateway_response' => $gatewayResponse,
            'paid_at' => now(),
        ]);
    }

    // Mark payment as failed
    public function markAsFailed(array $gatewayResponse = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'gateway_response' => $gatewayResponse,
        ]);
    }
}
