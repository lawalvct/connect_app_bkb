<?php
namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Http\Resources\V1\UserResource;
use App\Http\Resources\V1\UserProfileUploadResource;
use App\Models\ProfileMultiUpload;
use App\Models\User;
use App\Models\UserProfileUpload;
use App\Models\UserVerification;
use App\Helpers\FileUploadHelper;
use App\Helpers\S3UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
class ProfileController extends BaseController
{
    /**
     * Get all profile images and videos for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // Validate pagination parameters
            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'type' => 'nullable|in:image,video,all',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation error', $validator->errors(), 422);
            }

            // Get pagination parameters
            $perPage = $request->get('per_page', 20);
            $type = $request->get('type', 'all');

            // Build query
            $query = UserProfileUpload::where('user_id', $user->id)
                ->where('deleted_flag', 'N');

            // Filter by type if specified
            if ($type !== 'all') {
                $query->where('file_type', $type);
            }

            // Get user's profile uploads with pagination
            $profileUploads = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->sendResponse('Profile uploads retrieved successfully', [
                'uploads' => UserProfileUploadResource::collection($profileUploads->items()),
                'pagination' => [
                    'current_page' => $profileUploads->currentPage(),
                    'last_page' => $profileUploads->lastPage(),
                    'per_page' => $profileUploads->perPage(),
                    'total' => $profileUploads->total(),
                    'has_more' => $profileUploads->hasMorePages(),
                    'from' => $profileUploads->firstItem(),
                    'to' => $profileUploads->lastItem(),
                ],
                'stats' => [
                    'total_images' => UserProfileUpload::where('user_id', $user->id)
                        ->where('deleted_flag', 'N')
                        ->where('file_type', 'image')
                        ->count(),
                    'total_videos' => UserProfileUpload::where('user_id', $user->id)
                        ->where('deleted_flag', 'N')
                        ->where('file_type', 'video')
                        ->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve profile uploads', $e->getMessage(), 500);
        }
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string',
            'username' => "nullable|string|unique:users,username,{$user->id},id,deleted_flag,N",
            'bio' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        $updateData = [];

        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }

        if ($request->has('username')) {
            $updateData['username'] = $request->username;
        }

        if ($request->has('bio')) {
            $updateData['bio'] = $request->bio;
        }

        if ($request->has('country_id')) {
            $updateData['country_id'] = $request->country_id;
        }

        // Handle profile pictures
        if ($request->has('profile') && is_array($request->profile) && count($request->profile) > 0) {
            // Soft delete existing profile pictures
            UserProfileUpload::where('user_id', $user->id)->update(['deleted_flag' => 'Y']);

            // Add new profile pictures
            foreach ($request->profile as $file) {
                $profileData = [
                    'profile' => $file,
                    'profile_url' => 'uploads/profile/',
                    'user_id' => $user->id,
                ];

                UserProfileUpload::create($profileData);
            }

            // Update the user's main profile picture with the first one
            $updateData['profile'] = $request->profile[0];
            $updateData['profile_url'] = 'uploads/profile/';
        }

        // Update social links if provided
        if ($request->has('social_links') && is_array($request->social_links)) {
            $existingLinks = json_decode($user->social_links, true) ?: [];
            $linkMap = [];

            foreach ($request->social_links as $link) {
                if (isset($link['platform']) && isset($link['url'])) {
                    $platform = strtolower($link['platform']);
                    $linkMap[$platform] = $link['url'];
                }
            }

            // Update existing links or add new ones
            foreach ($linkMap as $platform => $url) {
                $found = false;

                foreach ($existingLinks as &$existingLink) {
                    if (strtolower($existingLink['platform']) === $platform) {
                        $existingLink['url'] = $url;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $existingLinks[] = [
                        'platform' => $platform,
                        'url' => $url,
                    ];
                }
            }

            $updateData['social_links'] = json_encode($existingLinks);
        }

        $user->update($updateData);

        return $this->sendResponse('Profile updated successfully', [
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Upload a profile picture.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfilePicture(Request $request)
    {
        // Get max file upload size from settings table (in KB), default to 5MB (5120 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 5120);

        $validator = Validator::make($request->all(), [
            'profile' => "required|file|image|max:{$maxFileSize}", // Dynamic max from settings
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        $user = $request->user();

        if ($request->hasFile('profile')) {
            $file = $request->file('profile');
            $path = 'uploads/profile/';
            $filename = time() . '_' . $file->getClientOriginalName();

            // Store the file
            $file->storeAs($path, $filename, 'public');

            return $this->sendResponse('Profile picture uploaded successfully', [
                'name' => $filename,
            ]);
        }

        return $this->sendError('No file selected', null, 422);
    }

    /**
     * Upload multiple profile pictures.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMultipleProfilePictures(Request $request)
    {
        // Get max file upload size from settings table (in KB), default to 5MB (5120 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 5120);

        $validator = Validator::make($request->all(), [
            'profile.*' => "required|file|image|max:{$maxFileSize}", // Dynamic max from settings
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        $user = $request->user();

        if ($request->hasFile('profile')) {
            $files = $request->file('profile');
            $uploadedFiles = [];

            // Soft delete existing profile pictures
            UserProfileUpload::where('user_id', $user->id)->update(['deleted_flag' => 'Y']);

            foreach ($files as $index => $file) {
                $path = 'uploads/profile/';
                $filename = time() . '_' . $index . '_' . $file->getClientOriginalName();

                // Store the file
                $file->storeAs($path, $filename, 'public');

                // Create profile record
                $profileData = [
                    'profile' => $filename,
                    'profile_url' => $path,
                    'user_id' => $user->id,
                    'type' => 'image',
                ];

                UserProfileUpload::create($profileData);

                $uploadedFiles[] = $filename;

                // Update the user's main profile picture with the first one
                if ($index === 0) {
                    $user->update([
                        'profile' => $filename,
                        'profile_url' => $path,
                    ]);
                }
            }

            return $this->sendResponse('Profile pictures uploaded successfully', [
                'files' => $uploadedFiles,
            ]);
        }

        return $this->sendError('No files selected', null, 422);
    }

    /**
     * Delete a social media link.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSocialLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        $user = $request->user();
        $platform = strtolower($request->platform);

        $existingLinks = json_decode($user->social_links, true) ?: [];

        // Filter out the platform to be deleted
        $updatedLinks = array_filter($existingLinks, function($link) use ($platform) {
            return strtolower($link['platform']) !== $platform;
        });

        // Reindex array
        $updatedLinks = array_values($updatedLinks);

        $user->update([
            'social_links' => json_encode($updatedLinks),
        ]);

        return $this->sendResponse('Social link deleted successfully', [
            'social_links' => $updatedLinks,
        ]);
    }

    /**
     * Get the authenticated user's social media links.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSocialLinks(Request $request)
    {
        $user = $request->user();
        $socialLinks = json_decode($user->social_links, true) ?: [];

        return $this->sendResponse('Social links retrieved successfully', [
            'social_links' => $socialLinks,
        ]);
    }

    /**
     * Delete the authenticated user's account after password verification.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccount(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);
//dd($request->all());
        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        $user = $request->user();

        // Verify the password
        if (!Hash::check($request->password, $user->password)) {
            return $this->sendError('Password is incorrect', null, 403);
        }

        try {
            // Soft delete the user
            $user->update([
                'deleted_flag' => 'Y',
                'deleted_at' => now(),
            ]);

            // Revoke all tokens
            $user->tokens()->delete();

            // Log the account deletion
            Log::info('User account deleted', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->sendResponse('Account deleted successfully');
        } catch (\Exception $e) {
            Log::error('Account deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Failed to delete account: ' . $e->getMessage(), null, 500);
        }
    }


     /**
     * Get all profile images for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfileImages(Request $request)
    {
        try {
            $user = $request->user();

            // Get images from both tables
            $profileMultiUploads = UserProfileUpload::where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($upload) {
                    return [
                        'id' => $upload->id,
                        'type' => 'profile_multi',
                        'filename' => $upload->file_name,
                        'url' => $upload->file_url . $upload->file_name,
                        'full_url' => url($upload->file_url . $upload->file_name),
                        'is_main' => false, // Will be updated below
                        'created_at' => $upload->created_at,
                        'updated_at' => $upload->updated_at,
                    ];
                });

            $userProfileUploads = UserProfileUpload::where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($upload) {
                    return [
                        'id' => $upload->id,
                        'type' => 'user_profile',
                        'filename' => $upload->file_name,
                        'url' => $upload->file_url,
                        'full_url' => url($upload->file_url),
                        'file_type' => $upload->file_type,
                        'is_main' => false, // Will be updated below
                        'created_at' => $upload->created_at,
                        'updated_at' => $upload->updated_at,
                    ];
                });

            // Combine both collections
            $allImages = $profileMultiUploads->concat($userProfileUploads);

            // Mark the current main profile image
            $allImages = $allImages->map(function ($image) use ($user) {
                if ($image['type'] === 'profile_multi' && $image['filename'] === $user->profile) {
                    $image['is_main'] = true;
                } elseif ($image['type'] === 'user_profile' && $image['filename'] === $user->profile) {
                    $image['is_main'] = true;
                }
                return $image;
            });

            // Sort by created_at desc
            $allImages = $allImages->sortByDesc('created_at')->values();

            return $this->sendResponse('Profile images retrieved successfully', [
                'images' => $allImages,
                'total_count' => $allImages->count(),
                'main_profile_image' => $user->profile ? [
                    'filename' => $user->profile,
                    'url' => $user->profile_url . $user->profile,
                    'full_url' => url($user->profile_url . $user->profile),
                ] : null,
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve profile images', $e->getMessage(), 500);
        }
    }

    /**
     * Set a profile image as the main profile picture.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setMainProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image_id' => 'required|integer',
            'image_type' => 'required|string|in:profile_multi,user_profile',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $imageId = $request->image_id;
            $imageType = $request->image_type;

            $imageData = null;

            // Find the image based on type
            if ($imageType === 'profile_multi') {
                $image = UserProfileUpload::where('id', $imageId)
                    ->where('user_id', $user->id)
                    ->where('deleted_flag', 'N')
                    ->first();

                if (!$image) {
                    return $this->sendError('Profile image not found', null, 404);
                }

                $imageData = [
                    'profile' => $image->profile,
                    'profile_url' => $image->profile_url,
                ];

            } elseif ($imageType === 'user_profile') {
                $image = UserProfileUpload::where('id', $imageId)
                    ->where('user_id', $user->id)
                    ->where('deleted_flag', 'N')
                    ->first();

                if (!$image) {
                    return $this->sendError('Profile image not found', null, 404);
                }

                $imageData = [
                    'profile' => $image->file_name,
                    'profile_url' => $image->file_url,
                ];
            }

            // Update user's main profile image
            $user->update($imageData);

            return $this->sendResponse('Main profile image updated successfully', [
                'user' => new UserResource($user->fresh()),
                'main_profile_image' => [
                    'filename' => $imageData['profile'],
                    'url' => $imageData['profile_url'] . $imageData['profile'],
                    'full_url' => url($imageData['profile_url'] . $imageData['profile']),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to update main profile image', $e->getMessage(), 500);
        }
    }

    /**
     * Delete a profile image.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image_id' => 'required|integer',
            'image_type' => 'required|string|in:profile_multi,user_profile',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $imageId = $request->image_id;
            $imageType = $request->image_type;

            $deleted = false;
            $wasMainImage = false;

            // Find and delete the image based on type
            if ($imageType === 'profile_multi') {
                $image = UserProfileUpload::where('id', $imageId)
                    ->where('user_id', $user->id)
                    ->where('deleted_flag', 'N')
                    ->first();

                if ($image) {
                    // Check if this is the main profile image
                    $wasMainImage = ($user->profile === $image->profile);

                    // Soft delete
                    $image->update(['deleted_flag' => 'Y']);
                    $deleted = true;

                    // Delete physical file
                    $filePath = $image->profile_url . $image->profile;
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                }

            } elseif ($imageType === 'user_profile') {
                $image = UserProfileUpload::where('id', $imageId)
                    ->where('user_id', $user->id)
                    ->where('deleted_flag', 'N')
                    ->first();

                if ($image) {
                    // Check if this is the main profile image
                    $wasMainImage = ($user->profile === $image->file_name);

                    // Soft delete
                    $image->update(['deleted_flag' => 'Y']);
                    $deleted = true;

                    // Delete physical file
                    if (Storage::disk('public')->exists($image->file_url)) {
                        Storage::disk('public')->delete($image->file_url);
                    }
                }
            }

            if (!$deleted) {
                return $this->sendError('Profile image not found', null, 404);
            }

            // If the deleted image was the main profile image, clear it from user
            if ($wasMainImage) {
                $user->update([
                    'profile' => null,
                    'profile_url' => null,
                ]);
            }

            return $this->sendResponse('Profile image deleted successfully', [
                'was_main_image' => $wasMainImage,
                'user' => new UserResource($user->fresh()),
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to delete profile image', $e->getMessage(), 500);
        }
    }

    /**
     * Get profile image details by ID.
     *
     * @param Request $request
     * @param int $imageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfileImageById(Request $request, $imageId)
    {
        $validator = Validator::make(['image_id' => $imageId], [
            'image_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $imageData = null;

            // Check in ProfileMultiUpload first
            $profileMultiUpload = UserProfileUpload::where('id', $imageId)
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->first();

            if ($profileMultiUpload) {
                $imageData = [
                    'id' => $profileMultiUpload->id,
                    'type' => 'profile_multi',
                    'filename' => $profileMultiUpload->file_name,
                    'url' => $profileMultiUpload->file_url . $profileMultiUpload->file_name,
                    'full_url' => url($profileMultiUpload->file_url . $profileMultiUpload->file_name),
                    'is_main' => $user->profile === $profileMultiUpload->profile,
                    'created_at' => $profileMultiUpload->created_at,
                    'updated_at' => $profileMultiUpload->updated_at,
                ];
            } else {
                // Check in UserProfileUpload
                $userProfileUpload = UserProfileUpload::where('id', $imageId)
                    ->where('user_id', $user->id)
                    ->where('deleted_flag', 'N')
                    ->first();

                if ($userProfileUpload) {
                    $imageData = [
                        'id' => $userProfileUpload->id,
                        'type' => 'user_profile',
                        'filename' => $userProfileUpload->file_name,
                        'url' => $userProfileUpload->file_url,
                        'full_url' => url($userProfileUpload->file_url),
                        'file_type' => $userProfileUpload->file_type,
                        'is_main' => $user->profile === $userProfileUpload->file_name,
                        'created_at' => $userProfileUpload->created_at,
                        'updated_at' => $userProfileUpload->updated_at,
                    ];
                }
            }

            if (!$imageData) {
                return $this->sendError('Profile image not found', null, 404);
            }

            return $this->sendResponse('Profile image retrieved successfully', [
                'image' => $imageData,
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve profile image', $e->getMessage(), 500);
        }
    }

     /**
     * Upload new profile images (single or multiple).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadNewProfileImages(Request $request)
    {
        // Get max file upload size from settings table (in KB), default to 10MB (10240 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 10240);

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => "required|file|image|mimes:jpeg,png,jpg,gif,webp|max:{$maxFileSize}", // Dynamic max from settings
            'set_as_main' => 'nullable|boolean',
            'upload_type' => 'nullable|string|in:s3,local', // Allow choosing upload type
        ]);


        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $uploadedImages = [];
            $uploadType = $request->get('upload_type', 's3'); // Default to S3
            $setAsMain = $request->get('set_as_main', false);

            // Debug: Check how many files are received
            $files = $request->file('images');
            if (!$files || !is_array($files)) {
                return $this->sendError('No images received or invalid format', null, 422);
            }

            DB::beginTransaction();

            foreach ($files as $index => $file) {
                if (!$file || !$file->isValid()) {
                    continue; // Skip invalid files
                }

                if ($uploadType === 's3') {
                    // Upload to S3
                    $uploadResult = S3UploadHelper::uploadFile($file, 'profiles');

                    $imageData = [
                        'user_id' => $user->id,
                        'file_name' => $uploadResult['filename'],
                        'file_url' => $uploadResult['url'],
                        'file_type' => 'image',
                        'deleted_flag' => 'N',
                    ];
                } else {
                    // Upload locally
                    $filename = time() . '_' . $index . '_' . $file->getClientOriginalName();
                    $path = 'uploads/profile/';

                    // Store the file locally
                    $file->storeAs($path, $filename, 'public');

                    $imageData = [
                        'user_id' => $user->id,
                        'file_name' => $filename,
                        'file_url' => $path . $filename,
                        'file_type' => 'image',
                        'deleted_flag' => 'N',
                    ];
                }

                // Create profile record
                $profileUpload = UserProfileUpload::create($imageData);

                $uploadedImages[] = [
                    'id' => $profileUpload->id,
                    'type' => 'user_profile',
                    'filename' => $profileUpload->file_name,
                    'url' => $profileUpload->file_url,
                    'full_url' => $uploadType === 's3' ? $profileUpload->file_url : url($profileUpload->file_url),
                    'file_type' => $profileUpload->file_type,
                    'is_main' => false,
                    'created_at' => $profileUpload->created_at,
                    'updated_at' => $profileUpload->updated_at,
                ];

                // Set first image as main if requested
                if ($setAsMain && $index === 0) {
                    if ($uploadType === 's3') {
                        // For S3, store the full URL in profile field
                        $user->update([
                            'profile' => $profileUpload->file_url, // Store full S3 URL
                            'profile_url' => '', // Keep empty for S3
                        ]);
                    } else {
                        // For local, store filename and path separately
                        $user->update([
                            'profile' => $profileUpload->file_name,
                            'profile_url' => 'uploads/profile/',
                        ]);
                    }
                    $uploadedImages[0]['is_main'] = true;
                }
            }

            DB::commit();

            return $this->sendResponse('Profile images uploaded successfully', [
                'uploaded_images' => $uploadedImages,
                'total_uploaded' => count($uploadedImages),
                'files_received' => count($files), // Debug info
                'user' => new UserResource($user->fresh()),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to upload profile images', $e->getMessage(), 500);
        }
    }
    /**
     * Replace an existing profile image.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function replaceProfileImage(Request $request)
    {
        // Get max file upload size from settings table (in KB), default to 10MB (10240 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 10240);

        $validator = Validator::make($request->all(), [
            'image_id' => 'required|integer',
            'image_type' => 'required|string|in:profile_multi,user_profile',
'new_image' => "required|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi,wmv,flv,webm,3gp,mkv,m4v,pdf,doc,docx|max:{$maxFileSize}", // Dynamic max from settings
            'upload_type' => 'nullable|string|in:s3,local',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $imageId = $request->image_id;
            $imageType = $request->image_type;
            $newImageFile = $request->file('new_image');
            $uploadType = $request->get('upload_type', 's3');

            DB::beginTransaction();

            $existingImage = null;
            $wasMainImage = false;

            // Find existing image
            if ($imageType === 'profile_multi') {
                $existingImage = UserProfileUpload::where('id', $imageId)
                    ->where('user_id', $user->id)
                    ->where('deleted_flag', 'N')
                    ->first();

                if (!$existingImage) {
                    return $this->sendError('Profile image not found', null, 404);
                }

                $wasMainImage = ($user->profile === $existingImage->profile);

            } elseif ($imageType === 'user_profile') {
                $existingImage = UserProfileUpload::where('id', $imageId)
                    ->where('user_id', $user->id)
                    ->where('deleted_flag', 'N')
                    ->first();

                if (!$existingImage) {
                    return $this->sendError('Profile image not found', null, 404);
                }

                $wasMainImage = ($user->profile === $existingImage->file_name);
            }

            // Delete old file
            if ($imageType === 'profile_multi') {
                $oldFilePath = $existingImage->profile_url . $existingImage->profile;
                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
            } else {
                if ($uploadType === 's3') {
                    // Extract S3 path from URL for deletion
                    $s3Path = str_replace(Storage::disk('s3')->url(''), '', $existingImage->file_url);
                    Storage::disk('s3')->delete($s3Path);
                } else {
                    if (Storage::disk('public')->exists($existingImage->file_url)) {
                        Storage::disk('public')->delete($existingImage->file_url);
                    }
                }
            }

            // Upload new file
            if ($uploadType === 's3') {
                $uploadResult = S3UploadHelper::uploadFile($newImageFile, 'profiles');
                $newFileName = $uploadResult['filename'];
                $newFileUrl = $uploadResult['url'];
            } else {
                $newFileName = time() . '_' . $newImageFile->getClientOriginalName();
                $path = 'uploads/profile/';
                $newImageFile->storeAs($path, $newFileName, 'public');
                $newFileUrl = $path . $newFileName;
            }

            // Update existing record
            if ($imageType === 'profile_multi') {
                $existingImage->update([
                    'profile' => $newFileName,
                    'profile_url' => $uploadType === 's3' ? '' : 'uploads/profile/',
                ]);
            } else {
                $existingImage->update([
                    'file_name' => $newFileName,
                    'file_url' => $newFileUrl,
                ]);
            }

            // Update user's main profile if this was the main image
            if ($wasMainImage) {
                $user->update([
                    'profile' => $newFileName,
                    'profile_url' => $uploadType === 's3' ? '' : 'uploads/profile/',
                ]);
            }

            DB::commit();

            $updatedImageData = [
                'id' => $existingImage->id,
                'type' => $imageType,
                'filename' => $newFileName,
                'url' => $newFileUrl,
                'full_url' => $uploadType === 's3' ? $newFileUrl : url($newFileUrl),
                'file_type' => $imageType === 'user_profile' ? $existingImage->file_type : 'image',
                'is_main' => $wasMainImage,
                'created_at' => $existingImage->created_at,
                'updated_at' => $existingImage->updated_at,
            ];

            return $this->sendResponse('Profile image replaced successfully', [
                'updated_image' => $updatedImageData,
                'user' => new UserResource($user->fresh()),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to replace profile image', $e->getMessage(), 500);
        }
    }

    /**
     * Upload single profile image (simplified version).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadSingleProfileImage(Request $request)
    {
        // Get max file upload size from settings table (in KB), default to 10MB (10240 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 10240);

        $validator = Validator::make($request->all(), [
            'image' => "required|file|image|mimes:jpeg,png,jpg,gif,webp|max:{$maxFileSize}", // Dynamic max from settings
            'set_as_main' => 'nullable|boolean',
            'upload_type' => 'nullable|string|in:s3,local',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $imageFile = $request->file('image');
            $uploadType = $request->get('upload_type', 's3');
            $setAsMain = $request->get('set_as_main', false);

            DB::beginTransaction();

            if ($uploadType === 's3') {
                // Upload to S3
                $uploadResult = S3UploadHelper::uploadFile($imageFile, 'profiles');

                $imageData = [
                    'user_id' => $user->id,
                    'file_name' => $uploadResult['filename'],
                    'file_url' => $uploadResult['url'],
                    'file_type' => 'image',
                    'deleted_flag' => 'N',
                ];
            } else {
                // Upload locally
                $filename = time() . '_' . $imageFile->getClientOriginalName();
                $path = 'uploads/profiles/';

                $imageFile->storeAs($path, $filename, 'public');

                $imageData = [
                    'user_id' => $user->id,
                    'file_name' => $filename,
                    'file_url' => $path ,
                    'file_type' => 'image',
                    'deleted_flag' => 'N',
                ];
            }

            // Create profile record
            $profileUpload = UserProfileUpload::create($imageData);

            // Set as main if requested
            if ($setAsMain) {
                if ($uploadType === 's3') {
                    // Get the S3 base URL (everything before the filename)
                    $s3BaseUrl = substr($profileUpload->file_url, 0, strrpos($profileUpload->file_url, '/') + 1);

                    $user->update([
                        'profile' => $profileUpload->file_name,
                        'profile_url' => $s3BaseUrl,
                    ]);
                } else {
                    $user->update([
                        'profile' => $profileUpload->file_name,
                        'profile_url' => 'uploads/profile/',
                    ]);
                }
            }

            DB::commit();

            $uploadedImage = [
                'id' => $profileUpload->id,
                'type' => 'user_profile',
                'filename' => $profileUpload->file_name,
                'url' => $profileUpload->file_url,
                'full_url' => $uploadType === 's3' ? $profileUpload->file_url : url($profileUpload->file_url),
                'file_type' => $profileUpload->file_type,
                'is_main' => $setAsMain,
                'created_at' => $profileUpload->created_at,
                'updated_at' => $profileUpload->updated_at,
            ];

            return $this->sendResponse('Profile image uploaded successfully', [
                'uploaded_image' => $uploadedImage,
                'user' => new UserResource($user->fresh()),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to upload profile image', $e->getMessage(), 500);
        }
    }

    /**
     * Bulk upload profile images using FileUploadHelper for advanced processing.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUploadProfileImages(Request $request)
    {
        // Get max file upload size from settings table (in KB), default to 10MB (10240 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 10240);

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => "required|file|image|mimes:jpeg,png,jpg,gif,webp|max:{$maxFileSize}",
            'set_first_as_main' => 'nullable|boolean',
            'create_variants' => 'nullable|boolean', // Create thumbnail, medium, large variants
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $uploadedImages = [];
            $setFirstAsMain = $request->get('set_first_as_main', false);
            $createVariants = $request->get('create_variants', false);

            DB::beginTransaction();

            foreach ($request->file('images') as $index => $file) {
                // Use FileUploadHelper for advanced image processing
                $uploadResult = FileUploadHelper::uploadMessageFile($file, 'image', $user->id);

                // Create main image record
                $imageData = [
                    'user_id' => $user->id,
                    'file_name' => $uploadResult['filename'],
                    'file_url' => $uploadResult['file_url'],
                    'file_type' => 'image',
                    'deleted_flag' => 'N',
                ];

                $profileUpload = UserProfileUpload::create($imageData);

                $uploadedImageData = [
                    'id' => $profileUpload->id,
                    'type' => 'user_profile',
                    'filename' => $profileUpload->file_name,
                    'url' => $profileUpload->file_url,
                    'full_url' => $profileUpload->file_url,
                    'file_type' => $profileUpload->file_type,
                    'is_main' => false,
                    'metadata' => [
                        'original_name' => $uploadResult['original_name'],
                        'file_size' => $uploadResult['file_size'],
                        'mime_type' => $uploadResult['mime_type'],
                        'width' => $uploadResult['width'] ?? null,
                        'height' => $uploadResult['height'] ?? null,
                    ],
                    'created_at' => $profileUpload->created_at,
                    'updated_at' => $profileUpload->updated_at,
                ];

                // Add thumbnail URL if available
                if (isset($uploadResult['thumbnail_url'])) {
                    $uploadedImageData['thumbnail_url'] = $uploadResult['thumbnail_url'];
                }

                // Create image variants if requested
                if ($createVariants) {
                    $variants = FileUploadHelper::createImageVariants($file);
                    $uploadedImageData['variants'] = $variants;
                }

                $uploadedImages[] = $uploadedImageData;

                // Set first image as main if requested
                if ($setFirstAsMain && $index === 0) {
                    $user->update([
                        'profile' => $profileUpload->file_name,
                        'profile_url' => '', // S3 URLs are complete
                    ]);
                    $uploadedImages[0]['is_main'] = true;
                }
            }

            DB::commit();

            return $this->sendResponse('Profile images uploaded successfully', [
                'uploaded_images' => $uploadedImages,
                'total_uploaded' => count($uploadedImages),
                'user' => new UserResource($user->fresh()),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to upload profile images', $e->getMessage(), 500);
        }
    }

    /**
     * Update profile image metadata.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfileImageMetadata(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image_id' => 'required|integer',
            'image_type' => 'required|string|in:profile_multi,user_profile',
            'caption' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $imageId = $request->image_id;
            $imageType = $request->image_type;

            $image = null;

            if ($imageType === 'profile_multi') {
                $image = UserProfileUpload::where('id', $imageId)
                    ->where('user_id', $user->id)
                    ->where('deleted_flag', 'N')
                    ->first();
            } else {
                $image = UserProfileUpload::where('id', $imageId)
                    ->where('user_id', $user->id)
                    ->where('deleted_flag', 'N')
                    ->first();
            }

            if (!$image) {
                return $this->sendError('Profile image not found', null, 404);
            }

            // Update metadata (you might need to add these columns to your tables)
            $updateData = [];

            if ($request->has('caption')) {
                $updateData['caption'] = $request->caption;
            }

            if ($request->has('alt_text')) {
                $updateData['alt_text'] = $request->alt_text;
            }

            if ($request->has('tags')) {
                $updateData['tags'] = json_encode($request->tags);
            }

            if (!empty($updateData)) {
                $image->update($updateData);
            }

            return $this->sendResponse('Profile image metadata updated successfully', [
                'image' => [
                    'id' => $image->id,
                    'type' => $imageType,
                    'filename' => $imageType === 'user_profile' ? $image->file_name : $image->profile,
                    'caption' => $image->caption ?? null,
                    'alt_text' => $image->alt_text ?? null,
                    'tags' => $image->tags ? json_decode($image->tags, true) : [],
                    'updated_at' => $image->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to update image metadata', $e->getMessage(), 500);
        }
    }


    public function updateSocialLinks(Request $request)
{
    $validator = Validator::make($request->all(), [
        'social_links' => 'required|array',
        'social_links.*.platform' => 'required|string',
        'social_links.*.url' => 'required|url'
    ]);

    if ($validator->fails()) {
        return $this->sendError('Validation error', $validator->errors(), 422);
    }

    try {
        $user = $request->user();

        // Update social links
        $user->update([
            'social_links' => json_encode($request->social_links)
        ]);

        return $this->sendResponse('Social links updated successfully', [
            'user' => new UserResource($user->fresh()),
            'social_links' => json_decode($user->social_links, true)
        ]);
    } catch (\Exception $e) {
        return $this->sendError('Failed to update social links', $e->getMessage(), 500);
    }
}

//verifyMe function here
public function verifyMe(Request $request)
{
    // Get max file upload size from settings table (in KB), default to ~10MB (10048 KB)
    $maxFileSize = Setting::getValue('max_file_upload_size', 10048);

    //user will provide id card type and id card image for admin to verify
    $validator = Validator::make($request->all(), [
        'id_card_type' => 'required|string|in:national_id,passport,drivers_license,voters_card,international_passport',
        'id_card_image' => "required|image|mimes:jpeg,png,jpg,gif|max:{$maxFileSize}",
    ]);

    if ($validator->fails()) {
        return $this->sendError('Validation error', $validator->errors(), 422);
    }

    try {
        $user = $request->user();
        $idCardType = $request->id_card_type;
        $idCardImage = $request->file('id_card_image');

        // Process the ID card verification
        $verification = $this->processIdCardVerification($user, $idCardType, $idCardImage);

        return $this->sendResponse('ID card submitted successfully for verification', [
            'verification' => [
                'id' => $verification->id,
                'id_card_type' => $verification->id_card_type,
                'status' => $verification->admin_status,
                'submitted_at' => $verification->submitted_at,
                'image_url' => $verification->id_card_image_url
            ],
            'message' => 'Your ID card has been submitted for admin review. You will be notified once the verification is complete.'
        ]);
    } catch (\Exception $e) {
        return $this->sendError('Failed to submit ID card for verification', $e->getMessage(), 500);
    }
}
    protected function processIdCardVerification($user, $idCardType, $idCardImage)
    {
        try {
            // Check if user already has a pending or approved verification
            $existingVerification = UserVerification::where('user_id', $user->id)
                ->whereIn('admin_status', ['pending', 'approved'])
                ->first();

            if ($existingVerification) {
                if ($existingVerification->isApproved()) {
                    throw new \Exception('User is already verified');
                }
                if ($existingVerification->isPending()) {
                    throw new \Exception('User already has a pending verification request');
                }
            }

            // Create the verifyme directory if it doesn't exist
            $uploadPath = public_path('uploads/verifyme');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $filename = $user->id . '_' . time() . '_' . uniqid() . '.' . $idCardImage->getClientOriginalExtension();

            // Move the uploaded file to the verifyme folder
            $idCardImage->move($uploadPath, $filename);

            // Create verification record
            $verification = UserVerification::create([
                'user_id' => $user->id,
                'id_card_type' => $idCardType,
                'id_card_image' => $filename,
                'admin_status' => 'pending',
                'submitted_at' => now(),
            ]);

            // Log the verification request
            Log::info('ID card verification submitted', [
                'user_id' => $user->id,
                'verification_id' => $verification->id,
                'id_card_type' => $idCardType,
                'filename' => $filename
            ]);

            return $verification;

        } catch (\Exception $e) {
            Log::error('ID card verification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }


    public function updateProfilePicture(Request $request)
    {
        // Get max file upload size from settings table (in KB), default to 50MB (50120 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 50120);

        $validator = Validator::make($request->all(), [
            'profile_picture' => "required|image|mimes:jpeg,png,jpg,gif,webp|max:{$maxFileSize}", // Dynamic size limit
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $file = $request->file('profile_picture');

            // Create uploads/profiles directory if it doesn't exist
            $uploadPath = public_path('uploads/profiles');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Delete old profile image if it exists
            if ($user->profile) {
                $oldImagePath = public_path('uploads/profiles/' . $user->profile);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Move file to uploads/profiles directory
            $file->move($uploadPath, $filename);

            // Update user profile fields
            $user->profile = $filename;
            $user->profile_url = 'uploads/profiles';
            $user->save();

            // Generate full URL for response
            $fullUrl = url('uploads/profiles/' . $filename);

            return $this->sendResponse('Profile picture updated successfully', [
                'user' => $user->fresh(),
                'profile_image_url' => $fullUrl
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update profile picture', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Failed to update profile picture', $e->getMessage(), 500);
        }
    }

}
