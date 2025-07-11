<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ad_name',
        'type',
        'description',
        'media_files',
        'call_to_action',
        'destination_url',
        'ad_placement',
        'start_date',
        'end_date',
        'target_audience',
        'budget',
        'daily_budget',
        'target_impressions',
        'current_impressions',
        'clicks',
        'conversions',
        'cost_per_click',
        'total_spent',
        'status',
        'admin_status',
        'admin_comments',
        'reviewed_by',
        'reviewed_at',
        'activated_at',
        'paused_at',
        'stopped_at',
        'deleted_flag',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'media_files' => 'array',
        'target_audience' => 'array',
        'ad_placement' => 'array',
        'budget' => 'decimal:2',
        'daily_budget' => 'decimal:2',
        'cost_per_click' => 'decimal:4',
        'total_spent' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
        'activated_at' => 'datetime',
        'paused_at' => 'datetime',
        'stopped_at' => 'datetime'
    ];

    protected $appends = [
        'progress_percentage',
        'ctr', // Click-through rate
        'days_remaining',
        'is_active',
        'can_be_edited'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // New relationship for ad placements (social circles)
    public function socialCircles()
    {
        return $this->belongsToMany(
            SocialCircle::class,
            'ad_social_circles', // We'll create this pivot table if needed
            'ad_id',
            'social_circle_id'
        );
    }

    // Get social circles by IDs stored in ad_placement JSON
    public function getPlacementSocialCirclesAttribute()
    {
        if (empty($this->ad_placement)) {
            return collect();
        }

        return SocialCircle::whereIn('id', $this->ad_placement)->get();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('admin_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('admin_status', 'approved');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('deleted_flag', 'N');
    }

    // Scope for ads that should appear in specific social circles
    public function scopeForSocialCircle($query, $socialCircleId)
    {
        return $query->whereJsonContains('ad_placement', $socialCircleId);
    }

    public function scopeForSocialCircles($query, $socialCircleIds)
    {
        return $query->where(function ($q) use ($socialCircleIds) {
            foreach ($socialCircleIds as $socialCircleId) {
                $q->orWhereJsonContains('ad_placement', $socialCircleId);
            }
        });
    }

    // Accessors
    public function getProgressPercentageAttribute()
    {
        if ($this->target_impressions == 0) {
            return 0;
        }
        return min(100, round(($this->current_impressions / $this->target_impressions) * 100, 2));
    }

    public function getCtrAttribute()
    {
        if ($this->current_impressions == 0) {
            return 0;
        }
        return round(($this->clicks / $this->current_impressions) * 100, 2);
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->end_date->isPast()) {
            return 0;
        }
        return now()->diffInDays($this->end_date);
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getCanBeEditedAttribute()
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    // Methods
    public function canBePaused()
    {
        return $this->status === 'active';
    }

    public function canBeStopped()
    {
        return in_array($this->status, ['active', 'paused']);
    }

    public function canBeDeleted()
    {
        return in_array($this->status, ['draft', 'rejected', 'stopped']);
    }

    public function pause()
    {
        if ($this->canBePaused()) {
            $this->update([
                'status' => 'paused',
                'paused_at' => now()
            ]);
            return true;
        }
        return false;
    }

    public function resume()
    {
        if ($this->status === 'paused') {
            $this->update([
                'status' => 'active',
                'paused_at' => null
            ]);
            return true;
        }
        return false;
    }

    public function stop()
    {
        if ($this->canBeStopped()) {
            $this->update([
                'status' => 'stopped',
                'stopped_at' => now()
            ]);
            return true;
        }
        return false;
    }

    public function approve($adminId, $comments = null)
    {
        $this->update([
            'admin_status' => 'approved',
            'status' => 'active',
            'admin_comments' => $comments,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'activated_at' => now()
        ]);
    }

    public function reject($adminId, $comments)
    {
        $this->update([
            'admin_status' => 'rejected',
            'status' => 'rejected',
            'admin_comments' => $comments,
            'reviewed_by' => $adminId,
            'reviewed_at' => now()
        ]);
    }

    // Check if ad should be displayed in a specific social circle
    public function shouldDisplayInSocialCircle($socialCircleId)
    {
        return $this->status === 'active'
            && $this->admin_status === 'approved'
            && in_array($socialCircleId, $this->ad_placement ?? [])
            && $this->start_date <= now()
            && $this->end_date >= now();
    }
}
