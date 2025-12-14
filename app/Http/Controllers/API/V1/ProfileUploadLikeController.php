<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Models\UserProfileUpload;
use App\Jobs\SendProfileUploadLikeNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileUploadLikeController extends BaseController
{
    /**
     * Toggle like on a profile upload (like if not liked, unlike if already liked)
     *
     * @param Request $request
     * @param int $uploadId
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleLike(Request $request, $uploadId)
    {
        try {
            $user = $request->user();

            $upload = UserProfileUpload::where('id', $uploadId)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$upload) {
                return $this->sendError('Profile upload not found', [], 404);
            }

            DB::beginTransaction();

            // Check if user already liked this upload
            $alreadyLiked = DB::table('user_profile_upload_likes')
                ->where('user_id', $user->id)
                ->where('upload_id', $uploadId)
                ->exists();

            if ($alreadyLiked) {
                // Unlike: Remove the like
                DB::table('user_profile_upload_likes')
                    ->where('user_id', $user->id)
                    ->where('upload_id', $uploadId)
                    ->delete();

                // Decrement like count
                $upload->decrement('like_count');

                DB::commit();

                Log::channel('daily')->info('Profile upload unliked', [
                    'user_id' => $user->id,
                    'upload_id' => $uploadId,
                    'new_count' => $upload->fresh()->like_count
                ]);

                return $this->sendResponse('Upload unliked successfully', [
                    'liked' => false,
                    'like_count' => $upload->fresh()->like_count
                ]);
            } else {
                // Like: Add the like
                DB::table('user_profile_upload_likes')->insert([
                    'user_id' => $user->id,
                    'upload_id' => $uploadId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Increment like count
                $upload->increment('like_count');

                DB::commit();

                Log::channel('daily')->info('Profile upload liked', [
                    'user_id' => $user->id,
                    'upload_id' => $uploadId,
                    'new_count' => $upload->fresh()->like_count
                ]);

                // Dispatch notification job (don't send if user liked own upload)
                if ($user->id !== $upload->user_id) {
                    try {
                        SendProfileUploadLikeNotificationJob::dispatch(
                            $user->id,
                            $upload->user_id,
                            $uploadId
                        )->onQueue('notifications');

                        Log::channel('daily')->info('Profile upload like notification job dispatched', [
                            'liker_id' => $user->id,
                            'upload_owner_id' => $upload->user_id,
                            'upload_id' => $uploadId
                        ]);
                    } catch (\Exception $e) {
                        Log::channel('daily')->error('Failed to dispatch profile upload like notification', [
                            'error' => $e->getMessage(),
                            'liker_id' => $user->id,
                            'upload_id' => $uploadId
                        ]);
                    }
                }

                return $this->sendResponse('Upload liked successfully', [
                    'liked' => true,
                    'like_count' => $upload->fresh()->like_count
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error toggling profile upload like', [
                'user_id' => $request->user()->id ?? 'unknown',
                'upload_id' => $uploadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Failed to process like', $e->getMessage(), 500);
        }
    }

    /**
     * Get users who liked a specific profile upload
     *
     * @param Request $request
     * @param int $uploadId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLikes(Request $request, $uploadId)
    {
        try {
            $upload = UserProfileUpload::where('id', $uploadId)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$upload) {
                return $this->sendError('Profile upload not found', [], 404);
            }

            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // Get users who liked this upload with pagination
            $likes = $upload->likes()
                ->select('users.id', 'users.name', 'users.username', 'users.profile_url')
                ->paginate($perPage, ['*'], 'page', $page);

            return $this->sendResponse('Likes retrieved successfully', [
                'upload_id' => $uploadId,
                'total_likes' => $upload->like_count,
                'likes' => $likes->items(),
                'pagination' => [
                    'current_page' => $likes->currentPage(),
                    'per_page' => $likes->perPage(),
                    'total' => $likes->total(),
                    'last_page' => $likes->lastPage(),
                    'has_more' => $likes->hasMorePages()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving profile upload likes', [
                'upload_id' => $uploadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Failed to retrieve likes', $e->getMessage(), 500);
        }
    }

    /**
     * Check if current user has liked a specific upload
     *
     * @param Request $request
     * @param int $uploadId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkLikeStatus(Request $request, $uploadId)
    {
        try {
            $user = $request->user();

            $upload = UserProfileUpload::where('id', $uploadId)
                ->where('deleted_flag', 'N')
                ->first();

            if (!$upload) {
                return $this->sendError('Profile upload not found', [], 404);
            }

            $isLiked = $upload->isLikedBy($user->id);

            return $this->sendResponse('Like status retrieved successfully', [
                'upload_id' => $uploadId,
                'is_liked' => $isLiked,
                'like_count' => $upload->like_count
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking profile upload like status', [
                'user_id' => $request->user()->id ?? 'unknown',
                'upload_id' => $uploadId,
                'error' => $e->getMessage()
            ]);

            return $this->sendError('Failed to check like status', $e->getMessage(), 500);
        }
    }

    /**
     * Get all uploads liked by the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyLikedUploads(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // Get all uploads liked by this user
            $likedUploads = UserProfileUpload::join('user_profile_upload_likes', 'user_profile_uploads.id', '=', 'user_profile_upload_likes.upload_id')
                ->where('user_profile_upload_likes.user_id', $user->id)
                ->where('user_profile_uploads.deleted_flag', 'N')
                ->with(['user:id,name,username,profile_url'])
                ->select('user_profile_uploads.*', 'user_profile_upload_likes.created_at as liked_at')
                ->orderBy('user_profile_upload_likes.created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return $this->sendResponse('Liked uploads retrieved successfully', [
                'uploads' => $likedUploads->items(),
                'pagination' => [
                    'current_page' => $likedUploads->currentPage(),
                    'per_page' => $likedUploads->perPage(),
                    'total' => $likedUploads->total(),
                    'last_page' => $likedUploads->lastPage(),
                    'has_more' => $likedUploads->hasMorePages()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user liked uploads', [
                'user_id' => $request->user()->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Failed to retrieve liked uploads', $e->getMessage(), 500);
        }
    }
}
