<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfileUpload extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'file_name',
        'file_url',
        'file_type',
        'caption',
        'alt_text',
        'tags',
        'metadata',
        'deleted_flag',
        'deleted_at'
    ];

    /**
     * Get the user that owns the profile upload.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

        /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'deleted_flag' => 'string',
        'tags' => 'array',
        'metadata' => 'array',
    ];



    /**
     * Get the full URL for the profile image.
     */
    public function getFullUrlAttribute()
    {
        // If file_url is already a complete URL (S3), return as is
        if (filter_var($this->file_url, FILTER_VALIDATE_URL)) {
            return $this->file_url;
        }

        // Otherwise, construct the URL
        return url($this->file_url);
    }
     /**
     * Scope to get only active (non-deleted) uploads.
     */
    public function scopeActive($query)
    {
        return $query->where('deleted_flag', 'N');
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute()
    {
        if (isset($this->metadata['file_size'])) {
            return $this->formatBytes($this->metadata['file_size']);
        }
        return null;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
