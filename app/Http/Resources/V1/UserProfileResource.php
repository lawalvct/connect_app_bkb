<?php

namespace App\Http\Resources\V1;

use App\Models\UserProfileUpload;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'bio' => $this->bio,
            'profile_image' => $this->getProfileUrl(),
            'profile_images' => $this->when($this->relationLoaded('profileImages'), function () {
                return $this->profileImages->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'file_name' => $image->file_name ?? $image->profile,
                        'file_url' => $image->file_url ?? $image->profile_url,
                        'profile_url' => $this->buildCompleteProfileUrl($image->file_url ?? $image->profile_url, $image->file_name ?? $image->profile),
                        'file_type' => $image->file_type ?? 'image',
                        'is_primary' => $image->is_primary ?? false,
                    ];
                });
            }),
            'country' => $this->whenLoaded('country', [
                'id' => $this->country->id,
                'name' => $this->country->name,
            ]),
            'social_circles' => SocialCircleResource::collection($this->whenLoaded('socialCircles')),
            'stats' => [
                'total_connections' => $this->total_connections ?? 0,
                'total_likes' => $this->total_likes ?? 0,
                'total_posts' => $this->total_posts ?? 0,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Get the profile URL for the user (same logic as UserResource)
     */
    private function getProfileUrl()
    {
        if (!$this->profile) {
            return null;
        }

        // Clean up profile filename
        $cleanProfile = $this->cleanFileName($this->profile);

        // If profile_url is already set and is a full URL, return it
        if ($this->profile_url && filter_var($this->profile_url, FILTER_VALIDATE_URL)) {
            return $this->profile_url;
        }

        // For local storage
        return url('uploads/profiles/' . $cleanProfile);
    }

    /**
     * Build complete profile URL by concatenating file_url and file_name
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

        // Clean up fileName
        $cleanFileName = $this->cleanFileName($fileName);

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
