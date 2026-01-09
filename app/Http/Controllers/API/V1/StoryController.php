<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CreateStoryRequest;
use App\Http\Requests\V1\ReplyToStoryRequest;
use App\Http\Resources\V1\StoryResource;
use App\Http\Resources\V1\StoryReplyResource;
use App\Http\Resources\V1\UserStoriesResource;
use App\Models\Story;
use App\Models\StoryReply;
use App\Models\User;
use App\Services\MediaProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoryController extends Controller
{
    protected $successStatus = 200;

    public function __construct(
        protected MediaProcessingService $mediaService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/stories",
     *     summary="Create a new story",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="type", type="string", enum={"text", "image", "video"}),
     *                 @OA\Property(property="content", type="string", description="Required for text stories"),
     *                 @OA\Property(property="file", type="string", format="binary", description="Required for image/video stories"),
     *                 @OA\Property(property="caption", type="string"),
     *                 @OA\Property(property="background_color", type="string", example="#FF0000"),
     *                 @OA\Property(property="privacy", type="string", enum={"all_connections", "close_friends", "custom"}),
     *                 @OA\Property(property="allow_replies", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Story created successfully")
     * )
     */
    public function store(CreateStoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $data = $request->validated();

            $storyData = [
                'user_id' => $user->id,
                'type' => $data['type'],
                'privacy' => $data['privacy'] ?? 'all_connections',
                'allow_replies' => $data['allow_replies'] ?? true,
            ];

            // Handle different story types
            switch ($data['type']) {
                case 'text':
                    $storyData['content'] = $data['content'];
                    $storyData['background_color'] = $data['background_color'] ?? '#000000';
                    $storyData['font_settings'] = $data['font_settings']; // Will be cast to JSON by model
                    break;

                case 'image':
                case 'video':
                    if ($request->hasFile('file')) {
                        // Process media locally
                        $uploadResult = $this->processMediaLocal(
                            $request->file('file'),
                            $user->id
                        );

                        $storyData['content'] = $uploadResult['file_path'];
                        $storyData['file_url'] = $uploadResult['file_url'];
                        $storyData['file_size'] = $uploadResult['file_size'];
                        $storyData['mime_type'] = $uploadResult['mime_type'];
                        $storyData['width'] = $uploadResult['width'] ?? null;
                        $storyData['height'] = $uploadResult['height'] ?? null;
                        $storyData['duration'] = $uploadResult['duration'] ?? null;
                    }

                    // Use caption field for media stories
                    if (!empty($data['caption'])) {
                        $storyData['caption'] = $data['caption'];
                    }
                    break;
            }

            // Handle custom privacy
            if ($data['privacy'] === 'custom' && !empty($data['custom_viewers'])) {
                $storyData['custom_viewers'] = $data['custom_viewers']; // Will be cast to JSON by model
            }

            $story = Story::create($storyData);

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Story created successfully',
                'data' => new StoryResource($story)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Story creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to create story: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stories/feed",
     *     summary="Get stories feed from connections",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Stories feed retrieved successfully")
     * )
     */

   // this is just to list all stories - for development purpose
    public function feed(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get connected users with active stories
           // $connectedUserIds = $this->getConnectedUserIds($user->id);

            $usersWithStories = User::
                whereHas('activeStories', function ($query) use ($user) {
                    $query->visibleTo($user->id);
                })
                ->with(['activeStories' => function ($query) use ($user) {
                    $query->visibleTo($user->id)->orderBy('created_at', 'desc');
                }])
                ->get();

            // Add current user's stories at the beginning if they have any
            $currentUserStories = $user->activeStories()->orderBy('created_at', 'desc')->get();
            if ($currentUserStories->isNotEmpty()) {
                $usersWithStories->prepend($user->load(['activeStories' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }]));
            }

            return response()->json([
                'status' => 1,
                'message' => 'Stories feed retrieved successfully',
                'data' => UserStoriesResource::collection($usersWithStories)
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Stories feed retrieval failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve stories feed'
            ], 500);
        }
    }


    // this is what i will later use in production
    public function feedConnected(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get connected users with active stories
            $connectedUserIds = $this->getConnectedUserIds($user->id);

            $usersWithStories = User::whereIn('id', $connectedUserIds)
                ->whereHas('activeStories', function ($query) use ($user) {
                    $query->visibleTo($user->id);
                })
                ->with(['activeStories' => function ($query) use ($user) {
                    $query->visibleTo($user->id)->orderBy('created_at', 'desc');
                }])
                ->get();

            // Add current user's stories at the beginning if they have any
            $currentUserStories = $user->activeStories()->orderBy('created_at', 'desc')->get();
            if ($currentUserStories->isNotEmpty()) {
                $usersWithStories->prepend($user->load(['activeStories' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }]));
            }

            return response()->json([
                'status' => 1,
                'message' => 'Stories feed retrieved successfully',
                'data' => UserStoriesResource::collection($usersWithStories)
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Stories feed retrieval failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve stories feed'
            ], 500);
        }
    }

    /**
     * Display the stories for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userStories(Request $request)
    {
        $user = $request->user();
        $stories = $user->stories()->latest()->get();

        return $this->sendResponse('User stories retrieved successfully', [
            'stories' => StoryResource::collection($stories->load('user', 'taggedUsers')),
        ]);
    }




    /**
     * @OA\Get(
     *     path="/api/v1/stories/{story}",
     *     summary="View a specific story",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="story",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Story retrieved successfully")
     * )
     */
    public function show(Request $request, Story $story): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if story exists and is not expired
            if ($story->is_expired) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Story has expired'
                ], 404);
            }

            // Check if user can view this story
            if (!$story->canBeViewedBy($user->id)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You do not have permission to view this story'
                ], 403);
            }

            // Mark as viewed (if not the owner)
            if ($user->id !== $story->user_id) {
                $story->markAsViewed($user->id);
            }

            return response()->json([
                'status' => 1,
                'message' => 'Story retrieved successfully',
                'data' => new StoryResource($story->load('user'))
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Story view failed', [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve story'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/stories/{story}",
     *     summary="Delete a story",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="story",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Story deleted successfully")
     * )
     */
    public function destroy(Request $request, Story $story): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user owns the story
            if ($story->user_id !== $user->id) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You can only delete your own stories'
                ], 403);
            }

            // Delete associated file if exists
            if ($story->file_url && $story->type !== 'text') {
                $this->mediaService->deleteFile($story->content);
            }

            $story->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Story deleted successfully'
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Story deletion failed', [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to delete story'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stories/{story}/view",
     *     summary="Mark story as viewed",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="story",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Story marked as viewed")
     * )
     */
    public function markAsViewed(Request $request, Story $story): JsonResponse
    {
        try {
            $user = $request->user();

            if ($story->is_expired) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Story has expired'
                ], 404);
            }

            if (!$story->canBeViewedBy($user->id)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You do not have permission to view this story'
                ], 403);
            }

            $story->markAsViewed($user->id);

            return response()->json([
                'status' => 1,
                'message' => 'Story marked as viewed',
                'data' => [
                    'views_count' => $story->fresh()->views_count
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Mark story as viewed failed', [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to mark story as viewed'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stories/{story}/viewers",
     *     summary="Get story viewers",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="story",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Story viewers retrieved successfully")
     * )
     */
    public function getViewers(Request $request, Story $story): JsonResponse
    {
        try {
            $user = $request->user();

            // Only story owner can see viewers
            if ($story->user_id !== $user->id) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You can only view viewers of your own stories'
                ], 403);
            }

            $viewers = $story->views()
                ->with('viewer:id,name,username,profile,profile_url')
                ->orderBy('viewed_at', 'desc')
                ->get();

            return response()->json([
                'status' => 1,
                'message' => 'Story viewers retrieved successfully',
                'data' => [
                    'total_views' => $viewers->count(),
                    'viewers' => $viewers->map(function ($view) {
                        return [
                            'user' => [
                                'id' => $view->viewer->id,
                                'name' => $view->viewer->name,
                                'username' => $view->viewer->username,
                                'profile_image' => $view->viewer->profile_image_url,
                            ],
                            'viewed_at' => $view->viewed_at->toISOString(),
                        ];
                    })
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get story viewers failed', [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve story viewers'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stories/{story}/reply",
     *     summary="Reply to a story",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="story",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="type", type="string", enum={"text", "emoji", "media"}),
     *                 @OA\Property(property="content", type="string"),
     *                 @OA\Property(property="file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Reply sent successfully")
     * )
     */
    public function reply(ReplyToStoryRequest $request, Story $story): JsonResponse
    {
        try {
            $user = $request->user();

            if ($story->is_expired) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Cannot reply to expired story'
                ], 404);
            }

            if (!$story->allow_replies) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Replies are not allowed for this story'
                ], 403);
            }

            if (!$story->canBeViewedBy($user->id)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You do not have permission to reply to this story'
                ], 403);
            }

            $data = $request->validated();

            $replyData = [
                'story_id' => $story->id,
                'user_id' => $user->id,
                'type' => $data['type'],
            ];

            if ($data['type'] === 'media' && $request->hasFile('file')) {
                $uploadResult = $this->mediaService->processUpload(
                    $request->file('file'),
                    "story-replies/{$user->id}/" . date('Y/m'),
                    'image'
                );

                $replyData['content'] = $uploadResult['file_path'];
                $replyData['file_url'] = $uploadResult['file_url'];
            } else {
                $replyData['content'] = $data['content'];
            }

            $reply = StoryReply::create($replyData);

            // Send notification to story owner (implement your notification system)
            // NotificationHelper::sendStoryReplyNotification($story->user_id, $user->id, $story->id);

            return response()->json([
                'status' => 1,
                'message' => 'Reply sent successfully',
                'data' => new StoryReplyResource($reply->load('user'))
            ], 201);

        } catch (\Exception $e) {
            Log::error('Story reply failed', [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to send reply'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stories/{story}/replies",
     *     summary="Get story replies",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="story",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Story replies retrieved successfully")
     * )
     */
    public function getReplies(Request $request, Story $story): JsonResponse
    {
        try {
            $user = $request->user();

            // Only story owner can see replies
            if ($story->user_id !== $user->id) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You can only view replies to your own stories'
                ], 403);
            }

            $replies = $story->replies()
                ->with('user:id,name,username,profile,profile_url')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 1,
                'message' => 'Story replies retrieved successfully',
                'data' => StoryReplyResource::collection($replies)
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get story replies failed', [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve story replies'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{user}/stories",
     *     summary="Get user's stories",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="User stories retrieved successfully")
     * )
     */
    public function getUserStories(Request $request, User $targetUser): JsonResponse
    {
        try {
            $currentUser = $request->user();

            $stories = $targetUser->activeStories()
                ->when($currentUser->id !== $targetUser->id, function ($query) use ($currentUser) {
                    $query->visibleTo($currentUser->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 1,
                'message' => 'User stories retrieved successfully',
                'data' => new UserStoriesResource($targetUser->setRelation('activeStories', $stories))
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get user stories failed', [
                'target_user_id' => $targetUser->id,
                'current_user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve user stories'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stories/my-stories",
     *     summary="Get current user's stories",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="My stories retrieved successfully")
     * )
     */
    public function myStories(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $stories = $user->activeStories()
                ->with(['views.viewer:id,name,username,profile,profile_url'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 1,
                'message' => 'Your stories retrieved successfully',
                'data' => StoryResource::collection($stories)
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get my stories failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve your stories'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stories/archive",
     *     summary="Get current user's expired stories (archive)",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(response=200, description="Archived stories retrieved successfully")
     * )
     */
    public function archive(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $stories = $user->stories()
                ->expired()
                ->with(['views.viewer:id,name,username,profile,profile_url'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status' => 1,
                'message' => 'Archived stories retrieved successfully',
                'data' => StoryResource::collection($stories->items()),
                'pagination' => [
                    'current_page' => $stories->currentPage(),
                    'total_pages' => $stories->lastPage(),
                    'per_page' => $stories->perPage(),
                    'total' => $stories->total(),
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get archived stories failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve archived stories'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stories/{story}/react",
     *     summary="React to a story",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="story",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reaction_type"},
     *             @OA\Property(property="reaction_type", type="string", enum={"like", "love", "haha", "wow", "sad", "angry", "fire", "clap", "heart_eyes", "thinking"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Reaction added successfully")
     * )
     */
    public function react(Request $request, Story $story): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate reaction type
            $validated = $request->validate([
                'reaction_type' => 'required|string|in:like,love,haha,wow,sad,angry,fire,clap,heart_eyes,thinking'
            ]);

            if ($story->is_expired) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Cannot react to expired story'
                ], 404);
            }

            if (!$story->canBeViewedBy($user->id)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You do not have permission to react to this story'
                ], 403);
            }

            // Add or update reaction
            $reaction = $story->addReaction($user->id, $validated['reaction_type']);

            // Mark story as viewed if not already
            if ($user->id !== $story->user_id) {
                $story->markAsViewed($user->id);
            }

            // Get updated reactions summary
            $reactionsSummary = $story->getReactionsSummary();

            return response()->json([
                'status' => 1,
                'message' => 'Reaction added successfully',
                'data' => [
                    'reaction' => [
                        'type' => $reaction->reaction_type,
                        'emoji' => \App\Models\StoryReaction::supportedReactions()[$reaction->reaction_type] ?? '',
                        'created_at' => $reaction->created_at->toISOString(),
                    ],
                    'reactions_summary' => $reactionsSummary,
                    'total_reactions' => array_sum($reactionsSummary),
                ]
            ], $this->successStatus);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Story reaction failed', [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to add reaction'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/stories/{story}/react",
     *     summary="Remove reaction from a story",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="story",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Reaction removed successfully")
     * )
     */
    public function removeReaction(Request $request, Story $story): JsonResponse
    {
        try {
            $user = $request->user();

            $removed = $story->removeReaction($user->id);

            if (!$removed) {
                return response()->json([
                    'status' => 0,
                    'message' => 'No reaction found to remove'
                ], 404);
            }

            // Get updated reactions summary
            $reactionsSummary = $story->getReactionsSummary();

            return response()->json([
                'status' => 1,
                'message' => 'Reaction removed successfully',
                'data' => [
                    'reactions_summary' => $reactionsSummary,
                    'total_reactions' => array_sum($reactionsSummary),
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Remove story reaction failed', [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to remove reaction'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stories/{story}/reactions",
     *     summary="Get story reactions",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="story",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="reaction_type",
     *         in="query",
     *         description="Filter by specific reaction type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Story reactions retrieved successfully")
     * )
     */
    public function getReactions(Request $request, Story $story): JsonResponse
    {
        try {
            $user = $request->user();

            // Only story owner can see detailed reactions
            if ($story->user_id !== $user->id) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You can only view reactions of your own stories'
                ], 403);
            }

            $query = $story->reactions()->with('user:id,name,username,profile,profile_url');

            // Filter by reaction type if provided
            if ($request->has('reaction_type')) {
                $query->where('reaction_type', $request->reaction_type);
            }

            $reactions = $query->orderBy('created_at', 'desc')->get();

            // Get summary
            $summary = $story->getReactionsSummary();

            return response()->json([
                'status' => 1,
                'message' => 'Story reactions retrieved successfully',
                'data' => [
                    'total_reactions' => $reactions->count(),
                    'reactions_summary' => $summary,
                    'reactions' => $reactions->map(function ($reaction) {
                        return [
                            'id' => $reaction->id,
                            'reaction_type' => $reaction->reaction_type,
                            'emoji' => \App\Models\StoryReaction::supportedReactions()[$reaction->reaction_type] ?? '',
                            'user' => [
                                'id' => $reaction->user->id,
                                'name' => $reaction->user->name,
                                'username' => $reaction->user->username,
                                'profile_image' => $reaction->user->profile_image_url,
                            ],
                            'created_at' => $reaction->created_at->toISOString(),
                        ];
                    })
                ]
            ], $this->successStatus);

        } catch (\Exception $e) {
            Log::error('Get story reactions failed', [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve story reactions'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stories/reactions/supported",
     *     summary="Get list of supported reactions",
     *     tags={"Stories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Supported reactions retrieved successfully")
     * )
     */
    public function getSupportedReactions(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => 'Supported reactions retrieved successfully',
            'data' => \App\Models\StoryReaction::supportedReactions()
        ], $this->successStatus);
    }

    /**
     * Helper method to get connected user IDs
     */
    private function getConnectedUserIds(int $userId): array
    {
        // This should use your existing connection logic
        // Assuming you have a UserRequestsHelper or similar
        try {
            // Replace this with your actual connection retrieval logic
            $connectedUsers = DB::table('user_requests')
                ->where(function ($query) use ($userId) {
                    $query->where('sender_id', $userId)
                          ->orWhere('receiver_id', $userId);
                })
                ->where('status', 'Accepted')
                ->get();

            $connectedIds = [];
            foreach ($connectedUsers as $connection) {
                if ($connection->sender_id == $userId) {
                    $connectedIds[] = $connection->receiver_id;
                } else {
                    $connectedIds[] = $connection->sender_id;
                }
            }

            return array_unique($connectedIds);
        } catch (\Exception $e) {
            Log::error('Failed to get connected user IDs', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Process media file locally for stories
     */
    protected function processMediaLocal($file, $userId): array
    {
        try {
            // Create uploads/stories directory if it doesn't exist
            $uploadPath = public_path('uploads/stories');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Get file info
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Determine file type
            $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $videoTypes = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm'];

            if (in_array($extension, $imageTypes)) {
                $type = 'image';
            } elseif (in_array($extension, $videoTypes)) {
                $type = 'video';
            } else {
                $type = 'file';
            }

            // Move file to uploads/stories
            $file->move($uploadPath, $filename);

            // Generate relative path and full URL
            $relativePath = 'uploads/stories/' . $filename;
            $fullUrl = url($relativePath);

            $result = [
                'type' => $type,
                'file_path' => $relativePath,
                'file_url' => $fullUrl,
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'mime_type' => $mimeType
            ];

            // For images, try to get dimensions
            if ($type === 'image') {
                try {
                    $imagePath = $uploadPath . '/' . $filename;
                    $imageSize = getimagesize($imagePath);
                    if ($imageSize) {
                        $result['width'] = $imageSize[0];
                        $result['height'] = $imageSize[1];
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not get image dimensions', ['file' => $filename, 'error' => $e->getMessage()]);
                }
            }

            // For videos, you could add duration extraction here if needed
            // This would require additional packages like FFMpeg

            return $result;

        } catch (\Exception $e) {
            Log::error('Story media processing failed', [
                'file' => $originalName ?? 'unknown',
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
