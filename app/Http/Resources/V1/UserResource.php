<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Helpers\TimezoneHelper;
use App\Models\User; // Make sure User model is imported

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
            'registration_step' => $this->registration_step ?? 0,
            'registration_completed_at' => $this->registration_completed_at ?
                TimezoneHelper::convertToUserTimezone($this->registration_completed_at, $userModelInstance)?->toISOString() : null,

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
                        'description' => $circle->description ?? null
                    ];
                })->filter(); // Remove null entries
            }),

            'profile_uploads' => $this->when($this->relationLoaded('profileUploads'), function () use ($userModelInstance) {
                // Add null check for profileUploads collection
                if (!$this->profileUploads) {
                    return [];
                }

                return $this->profileUploads->map(function ($upload) use ($userModelInstance) {
                    // Add null check for individual upload
                    if (!$upload) {
                        return null;
                    }

                    return [
                        'id' => $upload->id ?? null,
                        'file_name' => $upload->file_name ?? null,
                        'file_url' => ($upload->file_url ?? '') . ($upload->file_name ?? ''),
                        'file_type' => $upload->file_type ?? null,
                        'created_at' => $upload->created_at ?
                            TimezoneHelper::convertToUserTimezone($upload->created_at, $userModelInstance)?->toISOString() : null,
                    ];
                })->filter(); // Remove null entries
            }),
        ];
    }

   /**
 * Get properly formatted profile URL
 *
 * @return string|null
 */
protected function getProfileUrl()
{
    if (!$this->profile) {
        return null;
    }

    // If profile_url already contains a full URL (e.g. from social login)
    if ($this->profile_url && filter_var($this->profile_url, FILTER_VALIDATE_URL)) {
        return $this->profile_url;
    }

    // If profile_url is provided and profile is just the filename
    if ($this->profile_url && $this->profile) {
        // Check if profile_url already contains 'uploads/'
        if (strpos($this->profile_url, 'uploads/') !== false) {
            // Direct path to public directory
            return url($this->profile_url . $this->profile);
        } else {
            // Combine path and filename
            $path = trim($this->profile_url, '/') . '/' . $this->profile;

            // Check if this is a storage path or direct public path
            if (strpos($this->profile_url, 'storage/') === 0) {
                return Storage::disk(env('FILESYSTEM_DISK', 'public'))->url($path);
            } else {
                return url($path);
            }
        }
    }

    // If only profile (filename) is available, assume it's in a default 'uploads/profiles' directory
    if ($this->profile) {
        return url('uploads/profiles/' . $this->profile);
    }

    return null;
}

}
