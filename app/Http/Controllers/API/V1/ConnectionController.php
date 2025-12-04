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
use App\Models\UserNotification;
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

        // Load country relationship if not already loaded
        if (!$user->relationLoaded('country')) {
            $user->load('country');
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
        'social_id' => 'nullable|array',
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

            // Create notification for the target user about the connection request
            try {
                if ($data['request_type'] === 'right_swipe') {
                    UserNotification::createConnectionRequestNotification(
                        $user->id,
                        $data['user_id'],
                        $user->name,
                        $result['request_id']
                    );
                }
            } catch (\Exception $notificationException) {
                \Log::error('Failed to create connection request notification', [
                    'sender_id' => $user->id,
                    'receiver_id' => $data['user_id'],
                    'error' => $notificationException->getMessage()
                ]);
                // Don't fail the connection request if notification fails
            }

            // Get updated swipe stats
            $swipeStats = UserHelper::getSwipeStats($user->id);

            // Get a random user from the same social circle
            $randomUser = null;
            $socialCircleName = null;
            if (isset($data['social_id']) && !empty($data['social_id'])) {
                // Get the social circle name
                $socialCircle = \App\Models\SocialCircle::find($data['social_id']);
                $socialCircleName = $socialCircle ? $socialCircle->name : null;

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
                    'suggested_user' => $randomUser, // Add random user from same social circle
                    'social_circle_name' => $socialCircleName // Add social circle name being filtered
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

            // Create notification for the sender when their request is accepted
            try {
                if ($data['action'] === 'accept') {
                    UserNotification::createConnectionAcceptedNotification(
                        $user->id,
                        $connectionRequest->sender_id,
                        $user->name,
                        $id
                    );
                }
            } catch (\Exception $notificationException) {
                \Log::error('Failed to create connection accepted notification', [
                    'sender_id' => $connectionRequest->sender_id,
                    'accepter_id' => $user->id,
                    'error' => $notificationException->getMessage()
                ]);
                // Don't fail the response if notification fails
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

            // Get pending requests where current user is the receiver - same as getIncomingRequests
            // Add additional safety checks for valid sender and receiver
            $requests = UserRequest::with(['sender.profileImages', 'sender.country'])
                ->where('receiver_id', $user->id)
                ->where('status', 'pending')
                ->where('deleted_flag', 'N')
                ->whereNotNull('sender_id')
                ->whereNotNull('receiver_id')
                ->whereHas('sender', function($query) {
                    $query->where('deleted_flag', 'N')->whereNull('deleted_at');
                })
                ->orderBy('created_at', 'desc')
                ->get();

            if ($requests->isEmpty()) {
                return response()->json([
                    'status' => 1,
                    'message' => 'No connection requests found',
                    'data' => []
                ], $this->successStatus);
            }

            // Transform the data for response with null safety
            $transformedRequests = $requests->map(function ($request) {
                $senderData = null;
                if ($request->sender) {
                    // Get first profile image safely
                    $profileImage = $request->sender->profileImages->first();

                    $senderData = [
                        'id' => $request->sender->id,
                        'name' => $request->sender->name ?? '',
                        'username' => $request->sender->username ?? '',
                        'email' => $request->sender->email ?? '',
                        'profile' => $request->sender->profile ?? null,
                        'profile_url' => $request->sender->profile_url ?? null,
                        'profile_image' => $profileImage ? $profileImage->image_url : null,
                        'bio' => $request->sender->bio ?? '',
                        'age' => $request->sender->age ?? null,
                        'country' => $request->sender->country ? [
                            'id' => $request->sender->country->id,
                            'name' => $request->sender->country->name,
                            'code' => $request->sender->country->code ?? null,
                        ] : null,
                        'is_verified' => $request->sender->is_verified ?? false,
                    ];
                }

                return [
                    'id' => $request->id,
                    'sender_id' => $request->sender_id,
                    'receiver_id' => $request->receiver_id,
                    'social_id' => $request->social_id,
                    'request_type' => $request->request_type ?? 'right_swipe',
                    'message' => $request->message ?? null,
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at,
                    'time_ago' => $request->created_at ? $request->created_at->diffForHumans() : null,
                    'sender' => $senderData
                ];
            })->filter(function($request) {
                // Extra safety: only return requests that have valid sender data
                return $request['sender'] !== null;
            })->values(); // Re-index array after filtering

            return response()->json([
                'status' => 1,
                'message' => 'Connection requests retrieved successfully',
                'data' => $transformedRequests
            ], $this->successStatus);

        } catch (\Exception $e) {
            \Log::error('Get connection requests failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
     *     summary="Discover posts with user details based on various criteria",
     *     tags={"Connections"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="post_id", type="integer", description="Optional post ID to find users from same social circle"),
     *             @OA\Property(property="social_circle_ids", type="array", @OA\Items(type="integer"), description="Optional social circle IDs to filter by"),
     *             @OA\Property(property="page", type="integer", default=1, description="Page number for pagination"),
     *             @OA\Property(property="per_page", type="integer", default=50, description="Number of posts to return per page"),
     *             @OA\Property(property="country_id", type="integer", description="Filter by country")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Posts with user details retrieved successfully")
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
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
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
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 50);
            $countryId = $request->input('country_id');
            $offset = ($page - 1) * $perPage;

            Log::info('getUsersByPost called', [
                'user_id' => $user->id,
                'post_id' => $postId,
                'social_circle_ids' => $socialCircleIds,
                'page' => $page,
                'per_page' => $perPage,
                'country_id' => $countryId
            ]);

            // Get auth user's social circles for relevance scoring
            $authUserSocialCircles = DB::table('user_social_circles')
                ->where('user_id', $user->id)
                ->where('deleted_flag', 'N')
                ->pluck('social_id')
                ->toArray();

            // If post_id is provided, get the social circle from that post
            if ($postId) {
                $post = \App\Models\Post::find($postId);
                if ($post && $post->social_circle_id) {
                    $socialCircleIds[] = $post->social_circle_id;
                }
            }

            // If no social circles specified, get popular social circles from recent posts
            if (empty($socialCircleIds)) {
                $popularCircles = \App\Models\Post::where('created_at', '>=', now()->subDays(30))
                    ->where('is_published', true)
                    ->whereNotNull('social_circle_id')
                    ->groupBy('social_circle_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->limit(10)
                    ->pluck('social_circle_id')
                    ->toArray();

                $socialCircleIds = array_merge($socialCircleIds, $popularCircles);
            }

            // Remove duplicates
            $socialCircleIds = array_unique($socialCircleIds);

            // Get excluded user IDs (swiped and blocked users)
            $swipedUserIds = UserRequestsHelper::getSwipedUserIds($user->id);
            $blockedUserIds = BlockUserHelper::blockUserList($user->id);
            $excludedUserIds = array_merge($swipedUserIds, $blockedUserIds, [$user->id]);

            // Build base query for posts with multiple criteria
            // ONLY POSTS WITH MEDIA
            $postsQuery = \App\Models\Post::with([
                'user.profileImages',
                'user.country',
                'user.socialCircles',
                'media',
                'socialCircle',
                'likes',
                'comments'
            ])
            ->where('is_published', true)
            ->whereNull('deleted_at')
            ->whereHas('media') // Only posts that have media
            ->whereHas('user', function ($q) use ($excludedUserIds, $countryId) {
                $q->where('deleted_flag', 'N')
                  ->whereNull('deleted_at')
                  ->whereNotIn('id', $excludedUserIds);

                if ($countryId) {
                    $q->where('country_id', $countryId);
                }
            });

            // Filter by social circles if provided
            if (!empty($socialCircleIds)) {
                $postsQuery->whereIn('social_circle_id', $socialCircleIds);
            }

            // Create multiple queries for different criteria
            $queries = [];

            // 1. Most recent posts (30%)
            $recentPosts = clone $postsQuery;
            $recentPosts->orderBy('created_at', 'desc');
            $queries['recent'] = $recentPosts;

            // 2. Most liked posts (25%)
            $mostLikedPosts = clone $postsQuery;
            $mostLikedPosts->where('likes_count', '>', 0)
                          ->orderBy('likes_count', 'desc');
            $queries['liked'] = $mostLikedPosts;

            // 3. Most commented posts (20%)
            $mostCommentedPosts = clone $postsQuery;
            $mostCommentedPosts->where('comments_count', '>', 0)
                              ->orderBy('comments_count', 'desc');
            $queries['commented'] = $mostCommentedPosts;

            // 4. Most viewed posts (15%)
            $mostViewedPosts = clone $postsQuery;
            $mostViewedPosts->where('views_count', '>', 0)
                           ->orderBy('views_count', 'desc');
            $queries['viewed'] = $mostViewedPosts;

            // 5. Posts from auth user's social circles (10% - highest priority)
            $relevantPosts = null;
            if (!empty($authUserSocialCircles)) {
                $relevantPosts = clone $postsQuery;
                $relevantPosts->whereIn('social_circle_id', $authUserSocialCircles)
                             ->orderByRaw('(likes_count + comments_count + views_count) DESC');
                $queries['relevant'] = $relevantPosts;
            }

            // Calculate distribution of posts per category
            $distributions = [
                'relevant' => $relevantPosts ? (int)($perPage * 0.10) : 0, // 10%
                'recent' => (int)($perPage * 0.30), // 30%
                'liked' => (int)($perPage * 0.25),  // 25%
                'commented' => (int)($perPage * 0.20), // 20%
                'viewed' => (int)($perPage * 0.15)  // 15%
            ];

            //    $distributions = [
            //     'relevant' => $relevantPosts ? (int)($perPage * 0.0) : 0,
            //     'recent' => (int)($perPage * 0.90),
            //     'liked' => (int)($perPage * 0.0),
            //     'commented' => (int)($perPage * 0.0),
            //     'viewed' => (int)($perPage * 0.10)
            // ];

            // Collect posts from each category
            $collectedPosts = collect();
            $usedPostIds = [];

            foreach ($distributions as $type => $count) {
                if ($count > 0 && isset($queries[$type])) {
                    $categoryPosts = $queries[$type]
                        ->whereNotIn('id', $usedPostIds)
                        ->limit($count * 2) // Get more to account for duplicates
                        ->get();

                    // Add type for tracking
                    $categoryPosts = $categoryPosts->map(function ($post) use ($type) {
                        $post->discovery_type = $type;
                        return $post;
                    });

                    $collectedPosts = $collectedPosts->merge($categoryPosts->take($count));
                    $usedPostIds = array_merge($usedPostIds, $categoryPosts->pluck('id')->toArray());
                }
            }

            // If we don't have enough posts, fill with random posts
            if ($collectedPosts->count() < $perPage) {
                $remainingCount = $perPage - $collectedPosts->count();
                $randomPosts = (clone $postsQuery)
                    ->whereNotIn('id', $usedPostIds)
                    ->inRandomOrder()
                    ->limit($remainingCount)
                    ->get()
                    ->map(function ($post) {
                        $post->discovery_type = 'random';
                        return $post;
                    });

                $collectedPosts = $collectedPosts->merge($randomPosts);
            }

            // Shuffle the collection for random arrangement
            $collectedPosts = $collectedPosts->shuffle();

            // Apply pagination
            $total = $collectedPosts->count();
            $paginatedPosts = $collectedPosts->slice($offset, $perPage)->values();

            Log::info('getUsersByPost results', [
                'user_id' => $user->id,
                'total_posts' => $total,
                'returned_posts' => $paginatedPosts->count(),
                'social_circles_used' => $socialCircleIds,
                'auth_user_circles' => $authUserSocialCircles,
                'distributions' => $distributions
            ]);

            if ($paginatedPosts->isEmpty()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'No posts found for discovery',
                    'data' => [],
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 0
                    ]
                ], $this->successStatus);
            }

            // Format posts with user details
            $formattedPosts = $paginatedPosts->map(function ($post) use ($user) {
                // Get user connection data
                $connectionCount = UserRequestsHelper::getConnectionCount($post->user->id);
                $isConnected = UserRequestsHelper::areUsersConnected($user->id, $post->user->id);

                // Format post media
                $mediaItems = $post->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'type' => $media->type,
                        'url' => $media->file_url, // Fixed: use file_url instead of url
                        'thumbnail_url' => $media->thumbnail_url,
                        'file_path' => $media->file_path,
                        'original_name' => $media->original_name,
                        'mime_type' => $media->mime_type,
                        'file_size' => $media->file_size,
                        'file_size_human' => $media->file_size_human,
                        'width' => $media->width,
                        'height' => $media->height,
                        'duration' => $media->duration,
                        'duration_human' => $media->duration_human,
                        'order' => $media->order
                    ];
                });

                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'type' => $post->type,
                    'location' => $post->location,
                    'likes_count' => $post->likes_count,
                    'comments_count' => $post->comments_count,
                    'shares_count' => $post->shares_count,
                    'views_count' => $post->views_count,
                    'created_at' => $post->created_at->toISOString(),
                    'time_since_created' => $post->created_at->diffForHumans(),
                    'discovery_type' => $post->discovery_type ?? 'unknown',
                    'media' => $mediaItems,
                    'social_circle' => $post->socialCircle ? [
                        'id' => $post->socialCircle->id,
                        'name' => $post->socialCircle->name,
                        'logo' => $post->socialCircle->logo,
                        'color' => $post->socialCircle->color
                    ] : null,
                    'user' => new \App\Http\Resources\V1\UserResource($post->user),
                    'user_additional_data' => [
                        'total_connections' => $connectionCount,
                        'is_connected_to_current_user' => $isConnected,
                        'posts_count' => \App\Models\Post::where('user_id', $post->user->id)->count(),
                        'recent_posts_count' => \App\Models\Post::where('user_id', $post->user->id)
                            ->where('created_at', '>=', now()->subDays(7))
                            ->count()
                    ]
                ];
            });

            // Calculate pagination data
            $lastPage = (int) ceil($total / $perPage);

            return response()->json([
                'status' => 1,
                'message' => 'Posts with user details retrieved successfully',
                'data' => $formattedPosts,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                    'has_more_pages' => $page < $lastPage
                ],
                'meta' => [
                    'social_circles_used' => $socialCircleIds,
                    'auth_user_circles' => $authUserSocialCircles,
                    'discovery_method' => 'post_based_with_criteria',
                    'criteria_distribution' => $distributions
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
                'message' => 'Failed to retrieve posts with user details',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
