<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\TimezoneHelper;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\V1\UpdateProfileImageRequest;
use App\Http\Requests\V1\UpdateSocialLinksRequest;
use App\Http\Requests\V1\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Http\Resources\V1\CountryResource;
use App\Models\Country;
use App\Helpers\UserHelper;
use App\Helpers\PostHelper;
use App\Helpers\UserLikeHelper;
use App\Helpers\UserRequestsHelper;
use App\Helpers\SocialCircleHelper;
use App\Helpers\UserSubscriptionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\StateResource;
use App\Models\State as CountState;


class UserController extends BaseController
{
    /**
     * Display the authenticated user with comprehensive profile information
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user();

            // Load necessary relationships
            $user->load([
                'country',
                'profileUploads',
                'socialCircles',
                'profileImages'
            ]);

            // Get user statistics
            $totalConnections = UserRequestsHelper::getConnectionCount($user->id);
            $totalLikes = UserLikeHelper::getReceivedLikesCount($user->id);
            $totalPosts = PostHelper::getTotalPostByUserId($user->id);

            // Get recent posts (last 10)
            $recentPosts = PostHelper::getPostsByUserId($user->id, 10, 0);

            // Get user's social circles with details
            $socialCircles = SocialCircleHelper::getUserSocialCircles($user->id);

            // Get user's swipe stats
            $swipeStats = UserHelper::getSwipeStats($user->id);

            // Get likes given and received counts
            $likesGiven = UserLikeHelper::getGivenLikesCount($user->id);
            $likesReceived = UserLikeHelper::getReceivedLikesCount($user->id);

            // Get mutual matches count
            $mutualMatches = UserLikeHelper::getMutualLikes($user->id);
            $mutualMatchesCount = $mutualMatches->count();

            // Get pending connection requests count
            $pendingRequestsCount = UserRequestsHelper::getPendingRequestsCount($user->id);

            // Get posts statistics
            $postsThisMonth = PostHelper::getPostsThisMonth($user->id);
            $postsThisWeek = PostHelper::getPostsThisWeek($user->id);

            // Get user's active subscriptions
            $activeSubscriptions = UserSubscriptionHelper::getActiveSubscriptionsWithDetails($user->id);

            // Add computed fields to user object
            $user->total_connections = $totalConnections;
            $user->total_likes = $totalLikes;
            $user->total_posts = $totalPosts;
            $user->recent_posts = $recentPosts;
            $user->social_circles_detailed = $socialCircles;
            $user->swipe_stats = $swipeStats;
            $user->likes_given = $likesGiven;
            $user->likes_received = $likesReceived;
            $user->mutual_matches_count = $mutualMatchesCount;
            $user->pending_requests_count = $pendingRequestsCount;
            $user->posts_this_month = $postsThisMonth;
            $user->posts_this_week = $postsThisWeek;
            $user->active_subscriptions = $activeSubscriptions;

            // Profile completion percentage
            $user->profile_completion_percentage = $user->profile_completion;

            return $this->sendResponse('User profile retrieved successfully', [
                'user' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error retrieving user profile', [
                'user_id' => $request->user()->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Failed to retrieve user profile', $e->getMessage(), 500);
        }
    }

    /**
     * Update the authenticated user
     *
     * @param UpdateUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->sendResponse('User updated successfully', [
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Update user profile image
     *
     * @param UpdateProfileImageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfileImage(UpdateProfileImageRequest $request)
    {
        $user = $request->user();

        // Delete old profile image if exists
        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        // Store new profile image
        $path = $request->file('image')->store('profile-images', 'public');
        $user->update(['profile_image' => $path]);

        return $this->sendResponse('Profile image updated successfully', [
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Update user social links
     *
     * @param UpdateSocialLinksRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSocialLinks(UpdateSocialLinksRequest $request)
    {
        $user = $request->user();
        $user->update(['social_links' => $request->social_links]);

        return $this->sendResponse('Social links updated successfully', [
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Delete user account
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        // Revoke all tokens
        $user->tokens()->delete();

        // Soft delete the user
        $user->delete();

        return $this->sendResponse('User account deleted successfully');
    }

    /**
     * Get all countries
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCountries()
    {
        try {
            $countries = Cache::remember('countries', 60*24, function () {
                return Country::where('active', true)
                    ->orderBy('name', 'asc')
                    ->get();
            });

            return $this->sendResponse('Countries retrieved successfully', [
                'countries' => CountryResource::collection($countries)
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve countries', $e->getMessage(), 500);
        }
    }

    /**
     * Get list of available timezones
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTimezones()
    {
        try {
            $timezones = TimezoneHelper::getTimezoneList();

            return $this->sendResponse('Timezones retrieved successfully', [
                'timezones' => $timezones
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve timezones', $e->getMessage(), 500);
        }
    }

    /**
     * Update user timezone
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTimezone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timezone' => 'required|string|timezone'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $user->timezone = $request->timezone;
            $user->save();

            return $this->sendResponse('Timezone updated successfully', [
                'user' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to update timezone', $e->getMessage(), 500);
        }
    }

    public function getStatesByCountry($country)
    {
        try {
            $states = Cache::remember("states.{$country}", 60*24, function () use ($country) {
                return CountState::where('country_id', $country)
                    ->orWhere('country_code', strtoupper($country))
                    ->orderBy('name', 'asc')
                    ->get();
            });

            if ($states->isEmpty()) {
                return $this->sendError('No states found for this country', [], 404);
            }

            return $this->sendResponse('States retrieved successfully', [
                'states' => StateResource::collection($states)
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve states', $e->getMessage(), 500);
        }
    }
}
