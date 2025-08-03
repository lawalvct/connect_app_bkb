<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SendConnectionRequest;
use App\Http\Requests\V1\RespondToConnectionRequest;
use App\Http\Requests\V1\GetUsersByCircleRequest;
use App\Http\Resources\V1\UserProfileResource;
use App\Http\Resources\V1\ConnectionRequestResource;
use App\Http\Resources\V1\SwipeStatsResource;
use App\Helpers\UserHelper;
use App\Helpers\UserRequestsHelper;
use App\Helpers\UserLikeHelper;
use App\Helpers\UserSubscriptionHelper;
use App\Helpers\Utility;
use App\Helpers\BlockUserHelper;
use App\Http\Resources\V1\CountryResource;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\UserSwipe;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Connections",
 *     description="User connection and matching operations"
 * )
 */
class ConnectionController extends Controller
{
    private int $successStatus = 200;

    /**
     * @OA\Get(
     *     path="/api/v1/user",
     *     summary="Get current user details",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="User details retrieved successfully")
     * )
     */
    public function getUserDetails(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $userDetails = UserHelper::getAllDetailByUserId($user->id);

            if (!$userDetails->relationLoaded('profileImages')) {
                $userDetails->load('profileImages');
            }

            return response()->json([
                'status' => 1,
                'message' => 'User details retrieved successfully',
                'data' => new UserProfileResource($userDetails)
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get user details failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve user details'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/{id}",
     *     summary="Get user details by ID",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="User details retrieved successfully")
     * )
     */
    public function getUserDetailsById(Request $request, $id)
{
    try {
        $user = UserHelper::getAllDetailByUserId($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'data' => []
            ], 404);
        }

        // Load relationships if not already loaded
        if (!$user->relationLoaded('profileImages')) {
            $user->load('profileImages');
        }

        // Load social circles relationship if not already loaded
        if (!$user->relationLoaded('socialCircles')) {
            $user->load('socialCircles');
        }

        // Use UserResource to properly handle profile URLs with legacy user logic
        $userData = new \App\Http\Resources\V1\UserResource($user);

        return response()->json([
            'data' => [$userData]
        ], $this->successStatus);
    } catch (\Exception $e) {
        \Log::error('Error getting user details by ID', [
            'user_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'message' => 'An error occurred while fetching user details',
            'data' => []
        ], $this->successStatus);
    }
}


    /**
     * @OA\Get(
     *     path="/api/v1/user/swipe-stats",
     *     summary="Get user swipe statistics",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Swipe stats retrieved successfully")
     * )
     */
    public function getSwipeStats(Request $request)
    {
        try {
            $auth = auth()->user();
            $swipeStats = UserHelper::getSwipeStats($auth->id);

            return response()->json([
                'message' => 'Successfully!',
                'status' => 1,
                'data' => $swipeStats
            ], $this->successStatus);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred: ' . $e->getMessage(),
                'status' => 0,
                'data' => []
            ], $this->successStatus);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/discover",
     *     summary="Get users for discovery/swiping by social circle",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="social_id", type="integer"),
     *             @OA\Property(property="country_id", type="integer"),
     *             @OA\Property(property="last_id", type="integer"),
     *             @OA\Property(property="limit", type="integer", default=10)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Discovery users retrieved successfully")
     * )
     */
    public function getUsersBySocialCircle(Request $request)
{
    $validator = Validator::make($request->all(), [
        'social_id' => 'required|array',
        'social_id.*' => 'integer',
        'country_id' => 'nullable|integer',
        'last_id' => 'nullable|integer',
        'limit' => 'nullable|integer|min:1|max:50'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => $validator->errors()->first(),
            'status' => 0,
            'data' => []
        ], $this->successStatus);
    }

    try {
        $user = $request->user(); // Get current authenticated user
        $socialIds = $request->input('social_id', []);
        $countryId = $request->input('country_id');
        $lastId = $request->input('last_id');
        $limit = $request->input('limit', 10);

        \Log::info('getUsersBySocialCircle called:', [
            'user_id' => $user->id,
            'social_ids' => $socialIds,
            'country_id' => $countryId,
            'last_id' => $lastId,
            'limit' => $limit
        ]);

        // Debug: Check if user exists in the social circle
        $userInCircle = DB::table('user_social_circles')
            ->where('user_id', $user->id)
            ->whereIn('social_id', $socialIds)
            ->where('deleted_flag', 'N')
            ->exists();

        \Log::info('Current user in social circle:', ['exists' => $userInCircle]);

        // Debug: Check total users in the specified social circles (without other filters)
        $totalUsersInSocialCircles = DB::table('users')
            ->join('user_social_circles', 'users.id', '=', 'user_social_circles.user_id')
            ->whereIn('user_social_circles.social_id', $socialIds)
            ->where('users.deleted_flag', 'N')
            ->where('user_social_circles.deleted_flag', 'N')
            ->whereNull('users.deleted_at')
            ->count();

        \Log::info('Total users in social circles (all users):', ['count' => $totalUsersInSocialCircles]);

        // Debug: Check how many users exist with ID >= 500
        $usersAbove500 = DB::table('users')
            ->join('user_social_circles', 'users.id', '=', 'user_social_circles.user_id')
            ->whereIn('user_social_circles.social_id', $socialIds)
            ->where('users.deleted_flag', 'N')
            ->where('user_social_circles.deleted_flag', 'N')
            ->where('users.id', '>=', 500)
            ->whereNull('users.deleted_at')
            ->count();

        \Log::info('Users with ID >= 500 in social circles:', ['count' => $usersAbove500]);

        // Debug: Count total users in social circles (excluding testing users below ID 500)
        $totalUsersInCircles = DB::table('users')
            ->join('user_social_circles', 'users.id', '=', 'user_social_circles.user_id')
            ->whereIn('user_social_circles.social_id', $socialIds)
            ->where('users.deleted_flag', 'N')
            ->where('user_social_circles.deleted_flag', 'N')
            ->where('users.id', '!=', $user->id)
            ->where('users.id', '>=', 500) // Exclude testing users below ID 500
            ->whereNull('users.deleted_at')
            ->count();

        \Log::info('Total users in social circles (excluding current user and testing users):', ['count' => $totalUsersInCircles]);

        // Debug: Check what social circles exist and their user counts
        $socialCircleStats = DB::table('social_circles')
            ->leftJoin('user_social_circles', 'social_circles.id', '=', 'user_social_circles.social_id')
            ->whereIn('social_circles.id', $socialIds)
            ->select('social_circles.id', 'social_circles.name',
                     DB::raw('COUNT(user_social_circles.user_id) as user_count'))
            ->groupBy('social_circles.id', 'social_circles.name')
            ->get();

        \Log::info('Social circle statistics:', ['circles' => $socialCircleStats->toArray()]);

        // Debug: Check what social circles the current user belongs to
        $userSocialCircles = DB::table('user_social_circles')
            ->join('social_circles', 'user_social_circles.social_id', '=', 'social_circles.id')
            ->where('user_social_circles.user_id', $user->id)
            ->where('user_social_circles.deleted_flag', 'N')
            ->select('social_circles.id', 'social_circles.name')
            ->get();

        \Log::info('Current user belongs to social circles:', ['circles' => $userSocialCircles->toArray()]);

        // Debug: Count users with country filter (excluding testing users below ID 500)
        if ($countryId) {
            $usersWithCountry = DB::table('users')
                ->join('user_social_circles', 'users.id', '=', 'user_social_circles.user_id')
                ->whereIn('user_social_circles.social_id', $socialIds)
                ->where('users.deleted_flag', 'N')
                ->where('user_social_circles.deleted_flag', 'N')
                ->where('users.id', '!=', $user->id)
                ->where('users.id', '>=', 500) // Exclude testing users below ID 500
                ->where('users.country_id', $countryId)
                ->whereNull('users.deleted_at')
                ->count();

            \Log::info('Users with country filter (excluding testing users):', ['count' => $usersWithCountry]);
        }

        // Get latest users with some randomness instead of purely random
        $getData = UserHelper::getLatestSocialCircleUsers($socialIds, $user->id, $lastId, $countryId, $limit);

        \Log::info('Results from UserHelper:', ['count' => $getData->count()]);

        // If no users found and user has social circles, try getting users from user's own social circles
        if ($getData->isEmpty() && $userSocialCircles->isNotEmpty()) {
            \Log::info('No users found in requested circles, trying user\'s own social circles');
            $userOwnSocialIds = $userSocialCircles->pluck('id')->toArray();
            $getData = UserHelper::getLatestSocialCircleUsers($userOwnSocialIds, $user->id, $lastId, $countryId, $limit);
            \Log::info('Results from user\'s own social circles:', ['count' => $getData->count()]);
        }

        // If still no users found, try getting any users with ID >= 500 regardless of social circles
        if ($getData->isEmpty()) {
            \Log::info('No users found in any social circles, trying any users >= 500');
            $getData = UserHelper::getAnyLatestUsers($user->id, $lastId, $countryId, $limit);
            \Log::info('Results from any users >= 500:', ['count' => $getData->count()]);
        }

        // After getting users, check if it's time to show an ad
        $swipeCount = UserSwipe::getTodayRecord($user->id)->total_swipes ?? 0;

        // Show ad after every 10 swipes
        if ($swipeCount > 0 && $swipeCount % 10 === 0) {
            $ads = Ad::getAdsForDiscovery($user->id, 1);
            if ($ads->isNotEmpty()) {
                // Insert ad into the response
                $adData = [
                    'type' => 'advertisement',
                    'ad_data' => $ads->first(),
                    'is_ad' => true
                ];

                // Add ad to response
                $getData = $getData->push($adData);
            }
        }

        if (count($getData) != 0) {
            // Add connection count for each user
            $getData = $getData->map(function($userItem) use ($user) {
                // Get connection count for this user
                $connectionCount = UserRequestsHelper::getConnectionCount($userItem->id);
                $userItem->total_connections = $connectionCount;

                // Check if the current user is connected to this user
                $isConnected = UserRequestsHelper::areUsersConnected($user->id, $userItem->id);
                $userItem->is_connected_to_current_user = $isConnected;

                 // Add country details using CountryResource
                if ($userItem->country) {
                    $userItem->country_details = new CountryResource($userItem->country);
                }
                return $userItem;
            });

            // Use UserResource collection to properly handle profile URLs with legacy user logic
            $getData = \App\Http\Resources\V1\UserResource::collection($getData);

            return response()->json([
                'message' => 'Successfully!',
                'status' => 1,
                'data' => $getData,
                'debug' => [
                    'total_in_circles' => $totalUsersInCircles,
                    'user_in_circle' => $userInCircle,
                    'filters_applied' => [
                        'country_id' => $countryId,
                        'social_ids' => $socialIds,
                        'current_user_excluded' => $user->id,
                        'testing_users_excluded' => 'Users with ID < 500 excluded'
                    ],
                    'detailed_stats' => [
                        'all_users_in_circles' => $totalUsersInSocialCircles ?? 0,
                        'users_above_500_in_circles' => $usersAbove500 ?? 0,
                        'current_user_social_circles' => $userSocialCircles->pluck('id')->toArray() ?? [],
                        'requested_social_ids' => $socialIds
                    ]
                ]
            ], $this->successStatus);
        } else {
            return response()->json([
                'message' => "No users available.",
                'status' => 0,
                'data' => [],
                'debug' => [
                    'total_in_circles' => $totalUsersInCircles,
                    'user_in_circle' => $userInCircle,
                    'possible_reasons' => [
                        'All users already swiped',
                        'All users blocked',
                        'No users in specified social circles',
                        'No users matching country filter',
                        'Testing users (ID < 500) excluded'
                    ],
                    'detailed_stats' => [
                        'all_users_in_circles' => $totalUsersInSocialCircles ?? 0,
                        'users_above_500_in_circles' => $usersAbove500 ?? 0,
                        'current_user_social_circles' => $userSocialCircles->pluck('id')->toArray() ?? [],
                        'requested_social_ids' => $socialIds
                    ]
                ]
            ], $this->successStatus);
        }
    } catch (\Exception $e) {
        \Log::error('getUsersBySocialCircle error:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'message' => 'An error occurred: ' . $e->getMessage(),
            'status' => 0,
            'data' => []
        ], $this->successStatus);
    }
}

    /**
     * @OA\Post(
     *     path="/api/v1/connections/request",
     *     summary="Send connection request (swipe right)",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="social_id", type="integer"),
     *             @OA\Property(property="request_type", type="string", enum={"right_swipe", "left_swipe", "super_like"}),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Connection request sent successfully")
     * )
     */
    public function sendRequest(SendConnectionRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            \Log::info('Connection request received', [
                'sender_id' => $user->id,
                'data' => $data
            ]);

            // Check if trying to send request to self
            if ($user->id == $data['user_id']) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Cannot send request to yourself'
                ], 400);
            }

            // Check if target user exists
            $targetUser = \App\Models\User::where('id', $data['user_id'])
                                     ->where('deleted_flag', 'N')
                                     ->first();

            if (!$targetUser) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Target user not found'
                ], 404);
            }

            // Send connection request
            $result = UserRequestsHelper::sendConnectionRequest(
                $user->id,
                $data['user_id'],
                $data['social_id'] ?? null,
                $data['request_type'],
                $data['message'] ?? null
            );

            \Log::info('Connection request result', $result);

            if (!$result['success']) {
                return response()->json([
                    'status' => 0,
                    'message' => $result['message']
                ], 400);
            }

            // Get updated swipe stats
            $swipeStats = UserHelper::getSwipeStats($user->id);

            // Get a random user from the same social circle
            $randomUser = null;
            if (isset($data['social_id']) && !empty($data['social_id'])) {
                $randomUser = UserHelper::getRandomUserFromSocialCircle(
                    $data['social_id'],
                    $user->id,
                    [$data['user_id']] // Exclude the user we just swiped on
                );

                if ($randomUser) {
                    $randomUser = Utility::convertString($randomUser);
                }
            }

            return response()->json([
                'status' => 1,
                'message' => 'Connection request sent successfully',
                'data' => [
                    'request_id' => $result['request_id'],
                    'swipe_stats' => $swipeStats,
                    'suggested_user' => $randomUser // Add random user from same social circle
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Send connection request failed', [
                'user_id' => $request->user()->id,
                'target_user_id' => $data['user_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to send connection request',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/connections",
     *     summary="Get connected users",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(response=200, description="Connected users retrieved successfully")
     * )
     */
    public function getConnectedUsers(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $connectedUsers = UserHelper::getConnectedUsers($user->id);

            if ($connectedUsers->isEmpty()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'No connections found',
                    'data' => []
                ], $this->successStatus);
            }

            return response()->json([
                'status' => 1,
                'message' => 'Connected users retrieved successfully',
                'data' => UserProfileResource::collection($connectedUsers)
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get connected users failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve connected users'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/connections/request/{id}/respond",
     *     summary="Accept or reject connection request",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="action", type="string", enum={"accept", "reject", "block"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Request responded successfully")
     * )
     */
    public function respondToRequest(RespondToConnectionRequest $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $data = $request->validated();

            $connectionRequest = UserRequest::find($id);

            if (!$connectionRequest) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Connection request not found'
                ], 404);
            }

            if ($connectionRequest->receiver_id !== $user->id) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Unauthorized to respond to this request'
                ], 403);
            }

            $success = false;
            $message = '';

            switch ($data['action']) {
                case 'accept':
                    $success = UserRequestsHelper::acceptRequest($id, $user->id);
                    $message = 'Connection request accepted successfully';
                    break;
                case 'reject':
                    $success = UserRequestsHelper::rejectRequest($id, $user->id);
                    $message = 'Connection request rejected successfully';
                    break;
                case 'block':
                    // Block user and reject request
                    BlockUserHelper::insert([
                        'user_id' => $user->id,
                        'block_user_id' => $connectionRequest->sender_id
                    ]);
                    $success = UserRequestsHelper::rejectRequest($id, $user->id);
                    $message = 'User blocked and request rejected';
                    break;
            }

            if (!$success) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Failed to respond to connection request'
                ], 400);
            }

            return response()->json([
                'status' => 1,
                'message' => $message,
                'data' => ['action' => $data['action']]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Respond to connection request failed', [
                'user_id' => $request->user()->id,
                'request_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to respond to connection request'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/connections/requests",
     *     summary="Get pending connection requests",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Connection requests retrieved successfully")
     * )
     */
    public function getConnectionRequests(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $requests = UserRequestsHelper::getPendingRequests($user->id);

            return response()->json([
                'status' => 1,
                'message' => 'Connection requests retrieved successfully',
                'data' => ConnectionRequestResource::collection($requests)
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get connection requests failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve connection requests'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/connections/{id}/disconnect",
     *     summary="Disconnect from a user",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Disconnected successfully")
     * )
     */
    public function disconnect(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();

            $success = UserRequestsHelper::disconnectUsers($user->id, $id);

            if (!$success) {
                return response()->json([
                    'status' => 0,
                    'message' => 'No connection found to disconnect'
                ], 404);
            }

            return response()->json([
                'status' => 1,
                'message' => 'Disconnected successfully'
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Disconnect failed', [
                'user_id' => $request->user()->id,
                'target_user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to disconnect'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/{id}/like",
     *     summary="Like or unlike a user",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"profile", "photo", "super_like"}, default="profile")
     *         )
     *     ),
     *     @OA\Response(response=200, description="User liked/unliked successfully")
     * )
     */
    public function likeUser(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $type = $request->input('type', 'profile');

            if ($user->id == $id) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Cannot like yourself'
                ], 400);
            }

            $result = UserLikeHelper::toggleLike($user->id, $id, $type);

            return response()->json([
                'status' => 1,
                'message' => ucfirst($result) . ' successfully',
                'data' => ['action' => $result]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Like user failed', [
                'user_id' => $request->user()->id,
                'target_user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to like user'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/stats",
     *     summary="Get user connection and like statistics",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="User stats retrieved successfully")
     * )
     */
    public function getUserStats(Request $request)
    {
        try {
            $auth = $request->user();
            $swipeStats = UserHelper::getSwipeStats($auth->id);
            $userSubscriptions = UserSubscriptionHelper::getByUserId($auth->id);

            $stats = [
                'user_id' => $auth->id,
                'swipe_stats' => $swipeStats,
                'subscriptions' => $userSubscriptions,
                'is_premium' => count($userSubscriptions) > 0
            ];

            return response()->json([
                'message' => 'Successfully!',
                'status' => 1,
                'data' => $stats
            ], $this->successStatus);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred: ' . $e->getMessage(),
                'status' => 0,
                'data' => []
            ], $this->successStatus);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/likes/received",
     *     summary="Get users who liked me",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Users who liked me retrieved successfully")
     * )
     */
    public function getUsersWhoLikedMe(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $usersWhoLikedMe = UserLikeHelper::getUsersWhoLikedMe($user->id);

            \Log::info('Users who liked me query result', [
                'user_id' => $user->id,
                'count' => $usersWhoLikedMe->count()
            ]);

            if ($usersWhoLikedMe->isEmpty()) {
                return response()->json([
                    'status' => 1,
                    'message' => 'No users have liked you yet',
                    'data' => []
                ], $this->successStatus);
            }

            return response()->json([
                'status' => 1,
                'message' => 'Users who liked you retrieved successfully',
                'data' => $usersWhoLikedMe->toArray() // Convert to array instead of using Resource for now
            ], $this->successStatus);

        } catch (\Exception $e) {
            \Log::error('Get users who liked me failed', [
                'user_id' => $request->user()?->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve users who liked you',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/matches",
     *     summary="Get mutual likes (matches)",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Mutual matches retrieved successfully")
     * )
     */
    public function getMutualMatches(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => 'User not authenticated'
                ], 401);
            }

            \Log::info("Getting mutual matches for user ID: " . $user->id);

            $mutualMatches = UserLikeHelper::getMutualLikes($user->id);

            \Log::info("Mutual matches count: " . $mutualMatches->count());

            if ($mutualMatches->isEmpty()) {
                return response()->json([
                    'status' => 1,
                    'message' => 'No mutual matches found',
                    'data' => []
                ], $this->successStatus);
            }

            return response()->json([
                'status' => 1,
                'message' => 'Mutual matches retrieved successfully',
                'data' => $mutualMatches->toArray() // Use toArray() for now instead of Resource
            ], $this->successStatus);

        } catch (\Exception $e) {
            \Log::error('Get mutual matches failed', [
                'user_id' => $request->user()?->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve mutual matches',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Helper method to get connection status between two users
     */
    private function getConnectionStatus($userId, $targetUserId): string
    {
        $request = UserRequestsHelper::getByCheckRequest($userId, $targetUserId);
        $reverseRequest = UserRequestsHelper::getByCheckRequest($targetUserId, $userId);

        if ($request) {
            return $request->status;
        } elseif ($reverseRequest) {
            return $reverseRequest->status === 'pending' ? 'received_request' : $reverseRequest->status;
        }

        return 'none';
    }

    /**
     * @OA\Get(
     *     path="/api/v1/connections/requests",
     *     summary="Get incoming connection requests",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Incoming requests retrieved successfully")
     * )
     */
    public function getIncomingRequests(Request $request): JsonResponse
    {

        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get pending requests where current user is the receiver
            $incomingRequests = UserRequestsHelper::getPendingRequests($user->id);

            \Log::info('Getting incoming requests', [
                'user_id' => $user->id,
                'count' => $incomingRequests->count()
            ]);

            if ($incomingRequests->isEmpty()) {
                return response()->json([
                    'status' => 1,
                    'message' => 'No incoming requests found',
                    'data' => []
                ], $this->successStatus);
            }

            // Transform the data for response
            $transformedRequests = $incomingRequests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'sender_id' => $request->sender_id,
                    'receiver_id' => $request->receiver_id,
                    'social_id' => $request->social_id,
                    'request_type' => $request->request_type,
                    'message' => $request->message,
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                    'sender' => $request->sender ? [
                        'id' => $request->sender->id,
                        'name' => $request->sender->name,
                        'username' => $request->sender->username,
                        'profile' => $request->sender->profile,
                        'profile_url' => $request->sender->profile_url,
                        'bio' => $request->sender->bio ?? ''
                    ] : null
                ];
            });

            return response()->json([
                'status' => 1,
                'message' => 'Incoming requests retrieved successfully',
                'data' => $transformedRequests
            ], $this->successStatus);

        } catch (\Exception $e) {
            \Log::error('Get incoming requests failed', [
                'user_id' => $request->user()?->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve incoming requests',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/connections/sent",
     *     summary="Get sent connection requests",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Sent requests retrieved successfully")
     * )
     */
    public function getSentRequests(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get requests where current user is the sender
            $sentRequests = UserRequest::with(['receiver.profileImages', 'receiver.country'])
                ->where('sender_id', $user->id)
                ->where('deleted_flag', 'N')
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('Getting sent requests', [
                'user_id' => $user->id,
                'count' => $sentRequests->count()
            ]);

            if ($sentRequests->isEmpty()) {
                return response()->json([
                    'status' => 1,
                    'message' => 'No sent requests found',
                    'data' => []
                ], $this->successStatus);
            }

            // Transform the data for response
            $transformedRequests = $sentRequests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'receiver_id' => $request->receiver_id,
                    'status' => $request->status,
                    'message' => $request->message,
                    'request_type' => $request->request_type,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at,
                    'receiver' => [
                        'id' => $request->receiver->id,
                        'name' => $request->receiver->name,
                        'username' => $request->receiver->username,
                        'email' => $request->receiver->email,
                        'profile_image' => $request->receiver->profileImages->first()?->image_url ?? null,
                        'country' => $request->receiver->country ? [
                            'id' => $request->receiver->country->id,
                            'name' => $request->receiver->country->name,
                            'code' => $request->receiver->country->code,
                        ] : null,
                        'bio' => $request->receiver->bio,
                        'is_verified' => $request->receiver->is_verified ?? false,
                    ]
                ];
            });

            return response()->json([
                'status' => 1,
                'message' => 'Sent requests retrieved successfully',
                'data' => $transformedRequests
            ], $this->successStatus);

        } catch (\Exception $e) {
            \Log::error('Get sent requests failed', [
                'user_id' => $request->user()?->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve sent requests',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/discover-by-post",
     *     summary="Discover users based on posts and social circles",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="post_id", type="integer", description="Optional post ID to find users from same social circle"),
     *             @OA\Property(property="social_circle_ids", type="array", @OA\Items(type="integer"), description="Optional social circle IDs to filter by"),
     *             @OA\Property(property="limit", type="integer", default=20, description="Number of users to return"),
     *             @OA\Property(property="country_id", type="integer", description="Filter by country")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Users discovered successfully based on posts")
     * )
     */
    public function getUsersByPost(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'post_id' => 'nullable|integer|exists:posts,id',
                'social_circle_ids' => 'nullable|array',
                'social_circle_ids.*' => 'integer|exists:social_circles,id',
                'limit' => 'nullable|integer|min:1|max:50',
                'country_id' => 'nullable|integer|exists:countries,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->errors()->first(),
                    'data' => []
                ], 400);
            }

            $postId = $request->input('post_id');
            $socialCircleIds = $request->input('social_circle_ids', []);
            $limit = $request->input('limit', 20);
            $countryId = $request->input('country_id');

            Log::info('getUsersByPost called', [
                'user_id' => $user->id,
                'post_id' => $postId,
                'social_circle_ids' => $socialCircleIds,
                'limit' => $limit,
                'country_id' => $countryId
            ]);

            // If post_id is provided, get the social circle from that post
            if ($postId) {
                $post = \App\Models\Post::find($postId);
                if ($post && $post->social_circle_id) {
                    $socialCircleIds[] = $post->social_circle_id;
                }
            }

            // If no social circles specified, get popular social circles from recent posts
            if (empty($socialCircleIds)) {
                $popularCircles = \App\Models\Post::where('created_at', '>=', now()->subDays(7))
                    ->where('is_published', true)
                    ->whereNotNull('social_circle_id')
                    ->groupBy('social_circle_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->limit(5)
                    ->pluck('social_circle_id')
                    ->toArray();

                $socialCircleIds = array_merge($socialCircleIds, $popularCircles);
            }

            // Remove duplicates
            $socialCircleIds = array_unique($socialCircleIds);

            // Build query for users
            $query = User::where('users.deleted_flag', 'N')
                        ->where('users.id', '!=', $user->id)
                        ->whereNull('users.deleted_at');

            // Filter by social circles if provided
            if (!empty($socialCircleIds)) {
                $query->whereHas('socialCircles', function ($q) use ($socialCircleIds) {
                    $q->whereIn('social_id', $socialCircleIds)
                      ->where('user_social_circles.deleted_flag', 'N');
                });
            }

            // Filter by country if provided
            if ($countryId) {
                $query->where('users.country_id', $countryId);
            }

            // Exclude already swiped users
            $swipedUserIds = UserRequestsHelper::getSwipedUserIds($user->id);
            if (!empty($swipedUserIds)) {
                $query->whereNotIn('users.id', $swipedUserIds);
            }

            // Exclude blocked users
            $blockedUserIds = BlockUserHelper::blockUserList($user->id);
            if (!empty($blockedUserIds)) {
                $query->whereNotIn('users.id', $blockedUserIds);
            }

            // Prioritize users with recent posts
            $usersWithRecentPosts = $query->whereHas('posts', function ($q) {
                $q->where('posts.created_at', '>=', now()->subDays(3))
                  ->where('posts.is_published', true);
            })
            ->with(['profileImages', 'country', 'socialCircles'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();

            // If we don't have enough users with recent posts, fill with random users
            if ($usersWithRecentPosts->count() < $limit) {
                $remainingLimit = $limit - $usersWithRecentPosts->count();
                $existingUserIds = $usersWithRecentPosts->pluck('id')->toArray();

                $additionalUsers = User::where('users.deleted_flag', 'N')
                    ->where('users.id', '!=', $user->id)
                    ->whereNotIn('users.id', $existingUserIds)
                    ->whereNotIn('users.id', $swipedUserIds)
                    ->whereNotIn('users.id', $blockedUserIds)
                    ->whereNull('users.deleted_at')
                    ->with(['profileImages', 'country', 'socialCircles'])
                    ->inRandomOrder()
                    ->limit($remainingLimit)
                    ->get();

                $usersWithRecentPosts = $usersWithRecentPosts->merge($additionalUsers);
            }

            Log::info('getUsersByPost results', [
                'user_id' => $user->id,
                'found_users' => $usersWithRecentPosts->count(),
                'social_circles_used' => $socialCircleIds
            ]);

            if ($usersWithRecentPosts->isEmpty()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'No users found for discovery',
                    'data' => []
                ], $this->successStatus);
            }

            // Add additional user data
            $usersWithRecentPosts = $usersWithRecentPosts->map(function($discoveredUser) use ($user) {
                // Get connection count
                $connectionCount = UserRequestsHelper::getConnectionCount($discoveredUser->id);
                $discoveredUser->total_connections = $connectionCount;

                // Check if connected to current user
                $isConnected = UserRequestsHelper::areUsersConnected($user->id, $discoveredUser->id);
                $discoveredUser->is_connected_to_current_user = $isConnected;

                // Add country details
                if ($discoveredUser->country) {
                    $discoveredUser->country_details = new CountryResource($discoveredUser->country);
                }

                // Get recent posts count
                $recentPostsCount = \App\Models\Post::where('user_id', $discoveredUser->id)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->where('is_published', true)
                    ->count();
                $discoveredUser->recent_posts_count = $recentPostsCount;

                return $discoveredUser;
            });

            // Use UserResource collection to properly handle profile URLs with legacy user logic
            $formattedUsers = \App\Http\Resources\V1\UserResource::collection($usersWithRecentPosts);

            return response()->json([
                'status' => 1,
                'message' => 'Users discovered successfully based on posts',
                'data' => $formattedUsers,
                'meta' => [
                    'total_found' => $usersWithRecentPosts->count(),
                    'social_circles_used' => $socialCircleIds,
                    'discovery_method' => 'post_based'
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('getUsersByPost failed', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to discover users based on posts',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
