<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Helpers\TimezoneHelper;
use App\Models\User;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // The $this->resource is the User model instance
        /** @var User $userModelInstance */
        $userModelInstance = $this->resource;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'email_verified_at' => $this->email_verified_at ?
                TimezoneHelper::convertToUserTimezone($this->email_verified_at, $userModelInstance)?->toISOString() : null,
            'is_verified' => (bool)$this->is_verified,
            'bio' => $this->bio,
            'profile' => $this->profile,
            'profile_url' => $this->whenNotNull($this->getProfileUrl()),
            'country_id' => $this->country_id,
            'country' => $this->when($this->relationLoaded('country') && $this->country, function () {
                return [
                    'id' => $this->country->id,
                    'name' => $this->country->name,
                    'code' => $this->country->code,
                    'flag' => 'https://flagcdn.com/w80/'.strtolower($this->country->code).'.png',
                    'timezone' => $this->country->timezone // This is country's timezone, not user's
                ];
            }),
            'city' => $this->city,
            'state' => $this->state,
            'birth_date' => $this->birth_date,
            'gender' => $this->gender,
            'phone' => $this->phone ?? null, // Add phone field
            'timezone' => $this->timezone, // User's own timezone string
            'interests' => $this->interests, // Assuming this is already an array or JSON
            'social_links' => $this->social_links, // Assuming this is already an array or JSON
            'is_online' => (bool)$this->is_online,
            'last_activity_at' => $this->last_activity_at ?
                TimezoneHelper::convertToUserTimezone($this->last_activity_at, $userModelInstance)?->toISOString() : null,
            'created_at' => $this->created_at ?
                TimezoneHelper::convertToUserTimezone($this->created_at, $userModelInstance)?->toISOString() : null,
            'updated_at' => $this->updated_at ?
                TimezoneHelper::convertToUserTimezone($this->updated_at, $userModelInstance)?->toISOString() : null,
            'profile_completion' => $this->profile_completion,
            'profile_completion_percentage' => $this->when(isset($this->profile_completion_percentage), $this->profile_completion_percentage),
            'registration_step' => $this->registration_step ?? 0,
            'registration_completed_at' => $this->registration_completed_at ?
                TimezoneHelper::convertToUserTimezone($this->registration_completed_at, $userModelInstance)?->toISOString() : null,

            // Statistics
            'total_connections' => $this->when(isset($this->total_connections), $this->total_connections),
            'total_likes' => $this->when(isset($this->total_likes), $this->total_likes),
            'total_posts' => $this->when(isset($this->total_posts), $this->total_posts),
            'likes_given' => $this->when(isset($this->likes_given), $this->likes_given),
            'likes_received' => $this->when(isset($this->likes_received), $this->likes_received),
            'mutual_matches_count' => $this->when(isset($this->mutual_matches_count), $this->mutual_matches_count),
            'pending_requests_count' => $this->when(isset($this->pending_requests_count), $this->pending_requests_count),
            'posts_this_month' => $this->when(isset($this->posts_this_month), $this->posts_this_month),
            'posts_this_week' => $this->when(isset($this->posts_this_week), $this->posts_this_week),

            // Swipe Statistics
            'swipe_stats' => $this->when(isset($this->swipe_stats), $this->swipe_stats),

            // Recent Posts
            'recent_posts' => $this->when(isset($this->recent_posts), $this->recent_posts),

            // Social Circles (detailed)
            'social_circles' => $this->when(isset($this->social_circles_detailed), $this->social_circles_detailed),

            // Social Circles (from relationship)
            'social_circles' => $this->when($this->relationLoaded('socialCircles'), function () {
                // Add null check for socialCircles collection
                if (!$this->socialCircles) {
                    return [];
                }

                return $this->socialCircles->map(function ($circle) {
                    // Add null check for individual circle
                    if (!$circle) {
                        return null;
                    }

                    return [
                        'id' => $circle->id ?? null,
                        'name' => $circle->name ?? null,
                        'logo' => $circle->logo ?? null,
                        'logo_url' => $circle->logo_url ?? null,
                        'color' => $circle->color ?? '#3498db',
                        'description' => $circle->description ?? null,
                        'is_default' => $circle->is_default ?? false,
                        'is_private' => $circle->is_private ?? false,
                    ];
                })->filter(); // Remove null entries
            }),

            // Profile Images
            'profile_images' => $this->when($this->relationLoaded('profileImages'), function () use ($userModelInstance) {
                if (!$this->profileImages) {
                    return [];
                }

                return $this->profileImages->map(function ($image) use ($userModelInstance) {
                    if (!$image) {
                        return null;
                    }

                    return [
                        'id' => $image->id,
                        'file_name' => $image->file_name ?? $image->profile,
                        'file_url' => $image->file_url ?? $image->profile_url,
                        'profile_url' => $this->buildCompleteProfileUrl($image->file_url ?? $image->profile_url, $image->file_name ?? $image->profile),
                        'file_type' => $image->file_type ?? 'image',
                        'is_primary' => $image->is_primary ?? false,
                        'created_at' => $image->created_at ?
                            TimezoneHelper::convertToUserTimezone($image->created_at, $userModelInstance)?->toISOString() : null,
                    ];
                })->filter(); // Remove null entries
            }),

            // Profile Uploads (legacy support)
            'profile_uploads' => $this->when($this->relationLoaded('profileUploads'), function () use ($userModelInstance) {
                if (!$this->profileUploads) {
                    return [];
                }

                return $this->profileUploads->map(function ($upload) use ($userModelInstance) {
                    if (!$upload) {
                        return null;
                    }

                    return [
                        'id' => $upload->id,
                        'file_name' => $upload->file_name,
                        'file_url' => $upload->file_url,
                        'profile_url' => $this->buildCompleteProfileUrl($upload->file_url, $upload->file_name),
                        'file_type' => $upload->file_type ?? 'image',
                        'file_size' => $upload->file_size ?? null,
                        'is_primary' => $upload->is_primary ?? false,
                        'created_at' => $upload->created_at ?
                            TimezoneHelper::convertToUserTimezone($upload->created_at, $userModelInstance)?->toISOString() : null,
                    ];
                })->filter(); // Remove null entries
            }),

            // Privacy Settings
            'privacy_settings' => [
                'public_profile' => (bool)$this->privacy_public_profile,
                'show_online_status' => (bool)$this->privacy_show_online_status,
                'show_activity' => (bool)$this->privacy_show_activity,
            ],

            // Notification Settings
            'notification_settings' => [
                'email_notifications' => (bool)$this->notification_email,
                'push_notifications' => (bool)$this->notification_push,
                'preferences' => $this->notification_preferences ?? [],
            ],

            // Account Status
            'account_status' => [
                'is_active' => (bool)$this->is_active,
                'is_banned' => (bool)$this->is_banned,
                'ban_reason' => $this->when($this->is_banned, $this->ban_reason),
                'banned_until' => $this->when($this->is_banned && $this->banned_until,
                    TimezoneHelper::convertToUserTimezone($this->banned_until, $userModelInstance)?->toISOString()),
            ],

            // Additional Profile Information
            'additional_info' => [
                'occupation' => $this->occupation,
                'education_level' => $this->education_level,
                'relationship_status' => $this->relationship_status,
                'has_children' => $this->has_children,
                'income_range' => $this->income_range,
                'skills' => $this->skills,
            ],

            // Location Information
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'postal_code' => $this->postal_code,
                'location_string' => $this->location_string, // This uses the accessor from User model
            ],

            // Age (calculated from birth_date)
            'age' => $this->age, // This uses the accessor from User model

            // Display name
            'display_name' => $this->display_name, // This uses the accessor from User model
        ];
    }

    /**
     * Get the profile URL for the user
     *
     * @return string|null
     */
    private function getProfileUrl()
    {
        if (!$this->profile) {
            return null;
        }

        // Clean up profile filename
        $cleanProfile = $this->cleanFileName($this->profile);

        // Check if this is a legacy user (ID 1-3354) and use old server URL
        if ($this->id >= 1 && $this->id <= 3354) {
            // For legacy users, always use the old server URL with clean filename
            return 'https://connectapp.talosmart.xyz/uploads/profiles/' . $cleanProfile;
        }

        // For new users (ID > 3354), use current project logic

        // If profile_url is already set and is a full URL, return it
        if ($this->profile_url && filter_var($this->profile_url, FILTER_VALIDATE_URL)) {
            return $this->profile_url;
        }

        // If using cloud storage
        if (config('filesystems.default') === 's3') {
            try {
                // Try to get a public URL
                if (Storage::disk('s3')->exists('profiles/' . $cleanProfile)) {
                    return config('filesystems.disks.s3.url') . '/profiles/' . $cleanProfile;
                }
            } catch (\Exception $e) {
                // Log error and continue to fallback
                Log::warning('Failed to generate S3 URL for profile: ' . $cleanProfile, ['error' => $e->getMessage()]);
            }
        }

        // For local storage (new users)
        return url('uploads/profiles/' . $cleanProfile);
    }

    /**
     * Build complete profile URL by concatenating file_url and file_name
     *
     * @param string|null $fileUrl
     * @param string|null $fileName
     * @return string|null
     */
    private function buildCompleteProfileUrl($fileUrl, $fileName)
    {
        if (!$fileName) {
            return null;
        }

        // If fileName is already a complete URL, return it as is
        if (filter_var($fileName, FILTER_VALIDATE_URL)) {
            return $fileName;
        }

        // Clean up fileName - remove any duplications or malformed parts
        $cleanFileName = $this->cleanFileName($fileName);

        // Check if this is a legacy user (ID 1-3354) and use old server URL
        if ($this->id >= 1 && $this->id <= 3354) {
            // For legacy users, always use the old server URL with clean filename
            return 'https://connectapp.talosmart.xyz/uploads/profiles/' . $cleanFileName;
        }

        // For new users (ID > 3354), use current project logic

        // If fileUrl is empty or null, use default path
        if (!$fileUrl) {
            $fileUrl = 'uploads/profiles/';
        }

        // Remove trailing slash from fileUrl if present
        $fileUrl = rtrim($fileUrl, '/');

        // If fileUrl is already a complete URL, concatenate directly
        if (filter_var($fileUrl, FILTER_VALIDATE_URL)) {
            return $fileUrl . '/' . $cleanFileName;
        }

        // For local storage, build complete URL with domain
        return url($fileUrl . '/' . $cleanFileName);
    }

    /**
     * Clean filename by removing duplications and invalid characters
     *
     * @param string $fileName
     * @return string
     */
    private function cleanFileName($fileName)
    {
        if (!$fileName) {
            return '';
        }

        // Remove any URL parts if they somehow got into the filename
        $fileName = basename($fileName);

        // Handle duplicated filenames (e.g., "file.jpegfile.jpeg" -> "file.jpeg")
        $pathInfo = pathinfo($fileName);
        $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
        $basename = isset($pathInfo['filename']) ? $pathInfo['filename'] : $fileName;

        // Check if the basename contains the extension duplicated
        if ($extension && str_ends_with($basename, $extension)) {
            // Remove the duplicated extension from basename
            $basename = substr($basename, 0, -strlen($extension));
        }

        // Reconstruct the clean filename
        $cleanFileName = $extension ? $basename . '.' . $extension : $basename;

        return $cleanFileName;
    }
}
