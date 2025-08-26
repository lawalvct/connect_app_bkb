<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class SocialCircle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'logo',
        'logo_url',
        'description',
        'order_by',
        'color',
        'is_default',
        'is_active',
        'is_private',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_private' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'logo_full_url'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('social_circles.is_active', true)
                   ->where('social_circles.deleted_flag', 'N');
        });
    }

    /**
     * Get the full logo URL.
     *
     * @return string|null
     */
    public function getLogoFullUrlAttribute()
    {
        if (!$this->logo) {
            return null;
        }

        // If logo_url is set (like 'uploads/logo/'), combine it with logo filename
        if ($this->logo_url) {
            return asset($this->logo_url . '/' . $this->logo);
        }

        // Default to storage path
        return asset("storage/social-circles/{$this->logo}");
    }

    /**
     * Get the users that belong to the social circle.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_social_circles', 'social_id', 'user_id')
            ->withTimestamps()
            ->withPivot(['deleted_at', 'deleted_flag'])
            ->wherePivot('deleted_flag', 'N')
            ->where('users.deleted_flag', 'N');
    }

    /**
     * Get the user who created this social circle.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get active social circles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('social_circles.is_active', true)
                    ->where('social_circles.deleted_flag', 'N');
    }

    /**
     * Get default social circles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('social_circles.is_default', true);
    }

    /**
     * Scope to order social circles by their defined order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_by');
    }

    /**
     * Get the posts that belong to the social circle.
     */
    public function posts()
    {
        return $this->hasMany(\App\Models\Post::class, 'social_id');
    }

    // Relationship for ads
    public function ads()
    {
        return $this->belongsToMany(Ad::class, 'ad_social_circles', 'social_circle_id', 'ad_id');
    }

    // Get ads that target this social circle via JSON field
    public function getTargetingAdsAttribute()
    {
        return Ad::whereJsonContains('ad_placement', $this->id)->get();
    }

    // Get active ads for this social circle
    public function getActiveAdsAttribute()
    {
        return Ad::forSocialCircle($this->id)
            ->where('status', 'active')
            ->where('admin_status', 'approved')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get();
    }

    // Get ads count for this social circle
    public function getAdsCountAttribute()
    {
        return Ad::forSocialCircle($this->id)->count();
    }

    // Get active ads count for this social circle
    public function getActiveAdsCountAttribute()
    {
        return Ad::forSocialCircle($this->id)
            ->where('status', 'active')
            ->where('admin_status', 'approved')
            ->count();
    }
}
