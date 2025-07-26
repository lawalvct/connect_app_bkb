<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
        'updated_by',
    'target_countries',
    'target_social_circles',
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
        'stopped_at' => 'datetime',
        'target_countries' => 'array',
        'target_social_circles' => 'array',
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
        if (empty($this->target_social_circles)) {
            return collect();
        }

        return SocialCircle::whereIn('id', $this->target_social_circles)->get();
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
        return $query->whereJsonContains('target_social_circles', $socialCircleId);
    }

    public function scopeForSocialCircles($query, $socialCircleIds)
    {
        return $query->where(function ($q) use ($socialCircleIds) {
            foreach ($socialCircleIds as $socialCircleId) {
                $q->orWhereJsonContains('target_social_circles', $socialCircleId);
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
        if (!$this->end_date || $this->end_date->isPast()) {
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
            && in_array($socialCircleId, $this->target_social_circles ?? [])
            && $this->start_date && $this->start_date <= now()
            && $this->end_date && $this->end_date >= now();
    }

    /**
     * Get ads for a user during discovery/swiping
     */
    public static function getAdsForDiscovery($userId, $limit = 1)
    {
        $user = User::find($userId);
        if (!$user) return collect();

        // Get user's social circles
        $userSocialCircles = $user->socialCircles()->pluck('social_circles.id')->toArray();

        return self::where('status', 'active')
            ->where('admin_status', 'approved')
            ->where('deleted_flag', 'N')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where(function($query) use ($userSocialCircles, $user) {
                // Match social circles
                $query->where(function($q) use ($userSocialCircles) {
                    foreach ($userSocialCircles as $circleId) {
                        $q->orWhereJsonContains('target_social_circles', $circleId);
                    }
                });

                // Match demographics
                $query->where(function($q) use ($user) {
                    // Age targeting
                    if ($user->age) {
                        $q->where(function($ageQuery) use ($user) {
                            $ageQuery->whereRaw('JSON_EXTRACT(target_audience, "$.age_min") <= ?', [$user->age])
                                    ->whereRaw('JSON_EXTRACT(target_audience, "$.age_max") >= ?', [$user->age]);
                        });
                    }

                    // Gender targeting
                    if ($user->gender) {
                        $q->where(function($genderQuery) use ($user) {
                            $genderQuery->whereRaw('JSON_EXTRACT(target_audience, "$.gender") = ?', [$user->gender])
                                       ->orWhereRaw('JSON_EXTRACT(target_audience, "$.gender") = ?', ['all']);
                        });
                    }

                    // Country targeting
                    if ($user->country_id) {
                        $q->where(function($countryQuery) use ($user) {
                            $countryQuery->whereJsonContains('target_countries', $user->country_id)
                                        ->orWhereJsonContains('target_audience->locations', $user->country->code ?? '')
                                        ->orWhereJsonContains('target_audience->locations', $user->country->name ?? '');
                        });
                    }
                });
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }



    // Update or add the status enum values
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_STOPPED = 'stopped';

    // Add scope for draft ads
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    // Add method to check if ad can be paid for
    public function canBePaidFor()
    {
        return in_array($this->status, [self::STATUS_DRAFT, 'unpaid', 'payment_failed']);
    }

    // Relationship with payments
    public function payments()
    {
        return $this->hasMany(AdPayment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(AdPayment::class)->latest();
    }

    /**
     * Get impressions data grouped by month for a specific year
     */
    public function getMonthlyImpressions($year)
    {
        // This would work if you have detailed analytics table
        return $this->hasMany(AdAnalytic::class)
            ->whereYear('date', $year)
            ->selectRaw('MONTH(date) as month, SUM(impressions) as total_impressions')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Check if ad was active in a specific month/year
     */
    public function wasActiveInMonth($year, $month)
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        return $this->start_date && $this->end_date &&
               $this->start_date <= $endOfMonth && $this->end_date >= $startOfMonth;
    }

    /**
     * Get performance metrics for a specific period
     */
    public function getPerformanceMetrics($startDate, $endDate)
    {
        // This is a simplified version - you'd implement based on your analytics table
        return [
            'impressions' => $this->current_impressions,
            'clicks' => $this->clicks,
            'conversions' => $this->conversions,
            'ctr' => $this->ctr,
            'conversion_rate' => $this->conversion_rate ?? 0,
            'cost_per_click' => $this->clicks > 0 ? $this->total_spent / $this->clicks : 0,
            'cost_per_conversion' => $this->conversions > 0 ? $this->total_spent / $this->conversions : 0
        ];
    }
}
