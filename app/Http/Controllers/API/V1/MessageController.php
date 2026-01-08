<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ConversationParticipant;
use App\Http\Resources\V1\MessageResource;
use App\Events\MessageSent;
use App\Helpers\FileUploadHelper;
use App\Http\Resources\V1\ConversationResource;
use App\Jobs\SendNewMessageNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Pusher\Pusher;

class MessageController extends BaseController
{
    /**
     * Get messages in a conversation
     */
    public function index(Request $request, $conversationId)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return $this->sendError('Conversation not found', null, 404);
            }

            // Check if user is participant
            if (!$conversation->hasParticipant($request->user()->id)) {
                return $this->sendError('You are not a participant in this conversation', null, 403);
            }

            $perPage = $request->get('per_page', 20);
            $messages = $conversation->messages()
                ->with(['user', 'replyToMessage.user'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->sendResponse('Messages retrieved successfully', [
                'messages' => MessageResource::collection($messages->items()),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'has_more' => $messages->hasMorePages(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve messages', $e->getMessage(), 500);
        }
    }

    /**
     * Send a message with optional file upload
     *
     * For file uploads, you can use either:
     * 1. Multipart/form-data with actual file (Content-Type: multipart/form-data)
     *    - Send 'file' as actual file upload
     * 2. JSON with base64 encoded file (Content-Type: application/json)
     *    - Send 'file' as base64 string (with or without data:mime;base64, prefix)
     *    - Optionally include 'file_name' for original filename
     */
    public function store(Request $request, $conversationId)
    {
        // Debug: Log all request data
        Log::info('MessageController store - Request data:', [
            'all_data' => $request->all(),
            'input_type' => $request->input('type'),
            'has_file' => $request->hasFile('file'),
            'file_input' => $request->input('file'),
            'file_type' => gettype($request->input('file')),
            'content_type' => $request->header('Content-Type'),
            'method' => $request->method()
        ]);

        // Handle Postman's special form-data format
        $requestData = $request->all();
        $parsedData = [];

        // Check if request data is in Postman's UID format
        if (is_array($requestData) && isset($requestData[0]) && isset($requestData[0]['uid'])) {
            Log::info('Detected Postman UID format, parsing...');

            foreach ($requestData as $item) {
                if (isset($item['name']) && isset($item['value'])) {
                    if ($item['type'] === 'file' && isset($item['value'][0])) {
                        // Handle file - we need to get it differently for Postman format
                        $parsedData[$item['name']] = $request->file($item['name']);
                    } else {
                        $parsedData[$item['name']] = $item['value'];
                    }
                }
            }

            Log::info('Parsed Postman data:', $parsedData);
        } else {
            $parsedData = $requestData;
        }

        // Get the type from parsed data
        $messageType = $parsedData['type'] ?? $request->input('type');

        // Debug: Check if messageType is null or empty
        if (empty($messageType)) {
            Log::error('MessageController store - Type is empty or null', [
                'messageType' => $messageType,
                'request_all' => $request->all(),
                'parsed_data' => $parsedData,
                'request_keys' => array_keys($request->all())
            ]);
        }

        // Create validation data first to check file format
        $validationData = [];
        if (!empty($parsedData)) {
            $validationData = $parsedData;
            // For file uploads, we still need to get from request
            if ($request->hasFile('file')) {
                $validationData['file'] = $request->file('file');
            }
        } else {
            $validationData = $request->all();
        }

        // Early check: Handle case where mobile sends file as object/array with metadata
        if (in_array($messageType, ['image', 'video', 'audio', 'file'])) {
            $fileInput = $validationData['file'] ?? null;

            // Check if it's an array/object (metadata only) and not an actual file upload
            if (is_array($fileInput) && !$request->hasFile('file')) {
                Log::warning('Mobile app sent file metadata instead of actual file', [
                    'file_data' => $fileInput,
                    'message_type' => $messageType
                ]);

                return $this->sendError('File content required', [
                    'file' => [
                        'Please send the actual file content, not just metadata.',
                        'For mobile apps, send the file as a base64 encoded string.',
                        'Example: {"type": "image", "file": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."}',
                        'See MOBILE_FILE_UPLOAD_GUIDE.md for detailed examples.',
                        'Metadata received: ' . json_encode($fileInput)
                    ]
                ], 422);
            }
        }

        // Basic validation that always applies
        $rules = [
            'type' => 'required|in:text,image,video,audio,file,location',
            'reply_to_message_id' => 'nullable|exists:messages,id',
        ];

        // Add specific validation based on type
        if ($messageType === 'text') {
            // Only require message if no file is being sent
            $hasFileInput = $request->hasFile('file') ||
                           (isset($validationData['file']) && (is_string($validationData['file']) || is_array($validationData['file'])));

            if ($hasFileInput) {
                // File is present, so treat as file upload with wrong type
                Log::warning('File detected but type is "text". Type should be image/audio/video/file', [
                    'type_received' => $messageType,
                    'has_file_upload' => $request->hasFile('file'),
                    'file_input_type' => isset($validationData['file']) ? gettype($validationData['file']) : 'not_set'
                ]);

                return $this->sendError('Invalid type for file upload', [
                    'type' => [
                        'When sending a file, type must be one of: image, audio, video, file',
                        'You sent type="text" but included a file.',
                        'Change type to: "image" for photos, "audio" for voice notes, "video" for videos, "file" for documents'
                    ]
                ], 422);
            }

            $rules['message'] = 'required|string|max:4000';
        } elseif (in_array($messageType, ['image', 'video', 'audio', 'file'])) {
            // Check if file is base64 string or actual file upload
            $fileInput = $validationData['file'] ?? null;

            if (is_string($fileInput) && !$request->hasFile('file')) {
                // Base64 string from mobile app
                $rules['file'] = 'required|string|min:10';
                Log::info('Detected base64 file from mobile app', ['type' => $messageType]);
            } else {
                // Regular file upload
                $rules['file'] = 'required|file';
                // Add file-specific validation rules (mime types, dimensions, etc.)
                $this->addFileValidationRules($rules, $messageType);
            }

            $rules['message'] = 'nullable|string|max:1000'; // Optional caption
        } elseif ($messageType === 'location') {
            $rules['latitude'] = 'required|numeric|between:-90,90';
            $rules['longitude'] = 'required|numeric|between:-180,180';
            $rules['address'] = 'nullable|string|max:500';
            $rules['message'] = 'nullable|string|max:1000';
        }

        // Debug: Log the rules being applied
        Log::info('MessageController store - Validation rules:', ['rules' => $rules]);

        $validator = Validator::make($validationData, $rules);

        if ($validator->fails()) {
            Log::error('MessageController store - Validation failed:', [
                'errors' => $validator->errors()->toArray(),
                'validation_data' => $validationData,
                'rules_applied' => $rules,
                'file_type_received' => isset($validationData['file']) ? gettype($validationData['file']) : 'not_set'
            ]);
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return $this->sendError('Conversation not found', null, 404);
            }

            // Check if user is participant
            if (!$conversation->hasParticipant($request->user()->id)) {
                return $this->sendError('You are not a participant in this conversation', null, 403);
            }

            $messageData = [
                'conversation_id' => $conversationId,
                'user_id' => $request->user()->id,
                'type' => $parsedData['type'] ?? $request->input('type'),
                'reply_to_message_id' => $parsedData['reply_to_message_id'] ?? $request->input('reply_to_message_id'),
            ];

            // Get the final type
            $finalType = $parsedData['type'] ?? $request->input('type');

            // Handle different message types
            if ($finalType === 'text') {
                $messageData['message'] = $parsedData['message'] ?? $request->input('message');
                $messageData['metadata'] = null;
            } elseif (in_array($finalType, ['image', 'video', 'audio', 'file'])) {
                // Handle file upload - check if it's regular upload or base64
                $fileData = $parsedData['file'] ?? $request->input('file');

                if ($request->hasFile('file')) {
                    // Regular file upload
                    $metadata = $this->handleFileUpload($request->file('file'), $finalType, $request->user()->id);
                } else if (is_string($fileData)) {
                    // Base64 encoded file from mobile
                    $metadata = $this->handleBase64FileUpload($fileData, $finalType, $request->user()->id, $parsedData);
                } else {
                    throw new \Exception('Invalid file data provided');
                }

                $messageData['message'] = $parsedData['message'] ?? $request->input('message') ?: null; // Optional caption
                $messageData['metadata'] = $metadata;
            } elseif ($finalType === 'location') {
                $messageData['message'] = $parsedData['message'] ?? $request->input('message') ?: null;
                $messageData['metadata'] = [
                    'latitude' => $parsedData['latitude'] ?? $request->input('latitude'),
                    'longitude' => $parsedData['longitude'] ?? $request->input('longitude'),
                    'address' => $parsedData['address'] ?? $request->input('address'),
                ];
            }

            // Create message
            $message = Message::create($messageData);

            // Update conversation's last message time
            $conversation->update([
                'last_message_at' => now(),
            ]);

            // Load relationships for response
            $message->load(['user', 'replyToMessage.user']);

            // Dispatch notification job for other participants (background job)
            try {
                $currentUserId = $request->user()->id;

                // Get other participants in the conversation
                $otherParticipants = $conversation->participants()
                    ->where('user_id', '!=', $currentUserId)
                    ->where('is_active', true)
                    ->pluck('user_id');

                $messagePreview = $this->getMessagePreview($message);

                foreach ($otherParticipants as $participantId) {
                    \App\Jobs\SendNewMessageNotificationJob::dispatch(
                        $currentUserId,
                        $participantId,
                        $message->id,
                        $conversation->id,
                        $messagePreview,
                        $message->type
                    )->onQueue('notifications');

                    Log::info('Message notification job dispatched', [
                        'sender_id' => $currentUserId,
                        'receiver_id' => $participantId,
                        'message_id' => $message->id
                    ]);
                }
            } catch (\Exception $notificationException) {
                Log::error('Failed to dispatch message notification job', [
                    'message_id' => $message->id,
                    'error' => $notificationException->getMessage()
                ]);
                // Don't fail the message sending if notification dispatch fails
            }

            // Broadcast the message to Pusher for real-time updates
            try {
                Log::info('Starting Pusher broadcast', [
                    'conversation_id' => $conversationId,
                    'message_id' => $message->id,
                    'channel' => 'conversation.' . $conversationId
                ]);

                // Get Pusher configuration with fallbacks
                $pusherKey = config('broadcasting.connections.pusher.key');
                $pusherSecret = config('broadcasting.connections.pusher.secret');
                $pusherAppId = config('broadcasting.connections.pusher.app_id');
                $pusherCluster = config('broadcasting.connections.pusher.options.cluster');

                // Validate Pusher configuration
                if (empty($pusherKey) || empty($pusherSecret) || empty($pusherAppId)) {
                    Log::warning('Pusher configuration missing, skipping broadcast', [
                        'key_exists' => !empty($pusherKey),
                        'secret_exists' => !empty($pusherSecret),
                        'app_id_exists' => !empty($pusherAppId),
                        'cluster' => $pusherCluster
                    ]);

                    // Skip broadcasting but don't fail the request
                    return $this->sendResponse('Message sent successfully', [
                        'message' => new MessageResource($message)
                    ], 201);
                }

                // Direct Pusher broadcast using config values
                $pusher = new \Pusher\Pusher(
                    $pusherKey,
                    $pusherSecret,
                    $pusherAppId,
                    [
                        'cluster' => $pusherCluster ?: 'eu',
                        'useTLS' => true
                    ]
                );

                Log::info('Pusher instance created successfully');

                // Create simplified broadcast data to avoid resource serialization issues
                $broadcastData = [
                    'message' => [
                        'id' => $message->id,
                        'conversation_id' => $message->conversation_id,
                        'user_id' => $message->user_id,
                        'message' => $message->message,
                        'type' => $message->type,
                        'metadata' => $message->metadata,
                        'created_at' => $message->created_at->toISOString(),
                        'user' => [
                            'id' => $message->user->id,
                            'name' => $message->user->name,
                            'profile_image' => $message->user->profile_image ?? null
                        ]
                    ]
                ];

                Log::info('Broadcast data prepared', [
                    'data_structure' => array_keys($broadcastData),
                    'message_keys' => array_keys($broadcastData['message'])
                ]);

                $result = $pusher->trigger('conversation.' . $conversationId, 'message.sent', $broadcastData);

                Log::info('Direct Pusher broadcast successful', [
                    'message_id' => $message->id,
                    'conversation_id' => $conversationId,
                    'channel' => 'conversation.' . $conversationId,
                    'pusher_result' => $result,
                    'result_type' => gettype($result)
                ]);
            } catch (\Exception $broadcastException) {
                Log::error('Failed to broadcast message via direct Pusher', [
                    'message_id' => $message->id ?? 'not_available',
                    'conversation_id' => $conversationId,
                    'error_message' => $broadcastException->getMessage(),
                    'error_code' => $broadcastException->getCode(),
                    'error_file' => $broadcastException->getFile(),
                    'error_line' => $broadcastException->getLine(),
                    'trace' => $broadcastException->getTraceAsString()
                ]);
                // Don't fail the request if broadcast fails
            }

            return $this->sendResponse('Message sent successfully', [
                'message' => new MessageResource($message)
            ], 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to send message', $e->getMessage(), 500);
        }
    }

    /**
     * Add file validation rules based on type
     */
    private function addFileValidationRules(&$rules, $type)
    {
        // Define max file sizes (in KB)
        $maxSizes = [
            'image' => 951200,  // 951200KB = 950MB
            'video' => 951200, // 950MB
            'audio' => 951200, // 950MB
            'file' => 951200,  // 950MB
        ];

        // Define allowed mime types
        $allowedTypes = [
            'image' => 'jpg,jpeg,png,gif,webp',
            'video' => 'mp4,mov,avi,wmv,flv,webm',
            'audio' => 'mp3,wav,aac,ogg,m4a,webm',
            'file' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar',
        ];

        $maxSizeKB = $maxSizes[$type] ?? 10240;
        $mimeTypes = $allowedTypes[$type] ?? 'jpg,jpeg,png,gif';

        $rules['file'] .= "|max:$maxSizeKB|mimes:$mimeTypes";

        // Specific rules for each type
        switch ($type) {
            case 'image':
                // More flexible image validation - increased max dimensions to 8000x8000
                // This allows for high-resolution mobile photos
                $rules['file'] .= '|image|dimensions:max_width=8000,max_height=8000';
                break;
            case 'video':
                // Add video-specific rules if needed
                break;
            case 'audio':
                // Add audio-specific rules if needed
                break;
        }
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload($file, $type, $userId)
    {
        try {
            // Get file information before moving
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            $extension = $file->getClientOriginalExtension();

            // Create uploads/messages directory if it doesn't exist
            $uploadPath = public_path('uploads/messages');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $filename = 'user_' . $userId . '_' . time() . '_' . $type . '.' . $extension;

            // Move file to public/uploads/messages
            $file->move($uploadPath, $filename);

            // Generate file URL
            $fileUrl = url('uploads/messages/' . $filename);

            // Return metadata
            return [
                'file_name' => $filename,
                'file_path' => 'uploads/messages/' . $filename,
                'file_url' => $fileUrl,
                'file_size' => $fileSize,
                'file_type' => $mimeType,
                'original_name' => $originalName
            ];
        } catch (\Exception $e) {
            Log::error('File upload error:', [
                'error' => $e->getMessage(),
                'file_info' => $file ? [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'temp_path' => $file->getPathname()
                ] : 'No file provided'
            ]);
            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Handle base64 encoded file upload from mobile apps
     */
    private function handleBase64FileUpload($base64String, $type, $userId, $requestData = [])
    {
        try {
            // Check if the base64 string contains data:image prefix
            if (strpos($base64String, 'data:') === 0) {
                // Extract mime type and base64 data
                preg_match('/data:([^;]+);base64,(.+)/', $base64String, $matches);
                if (count($matches) === 3) {
                    $mimeType = $matches[1];
                    $base64Data = $matches[2];
                } else {
                    // Fallback if format is different
                    $base64Data = explode(',', $base64String)[1] ?? $base64String;
                    $mimeType = $this->getMimeTypeForFileType($type);
                }
            } else {
                // Pure base64 without prefix
                $base64Data = $base64String;
                $mimeType = $this->getMimeTypeForFileType($type);
            }

            // Decode base64
            $fileData = base64_decode($base64Data);

            if ($fileData === false) {
                throw new \Exception('Invalid base64 data');
            }

            // Get file extension from mime type or from file name in request
            $extension = $this->getExtensionFromMimeType($mimeType);

            // If we have file name in request data, use its extension
            if (isset($requestData['file']['name'])) {
                $pathInfo = pathinfo($requestData['file']['name']);
                if (isset($pathInfo['extension'])) {
                    $extension = $pathInfo['extension'];
                }
            }

            // Create uploads/messages directory if it doesn't exist
            $uploadPath = public_path('uploads/messages');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $filename = 'user_' . $userId . '_' . time() . '_' . $type . '.' . $extension;
            $filePath = $uploadPath . '/' . $filename;

            // Save file
            file_put_contents($filePath, $fileData);

            // Get file size
            $fileSize = strlen($fileData);

            // Get original name from request data if available
            $originalName = $requestData['file']['name'] ?? $filename;

            // Generate file URL
            $fileUrl = url('uploads/messages/' . $filename);

            Log::info('Base64 file uploaded successfully', [
                'filename' => $filename,
                'size' => $fileSize,
                'type' => $type,
                'mime_type' => $mimeType
            ]);

            // Return metadata
            return [
                'file_name' => $filename,
                'file_path' => 'uploads/messages/' . $filename,
                'file_url' => $fileUrl,
                'file_size' => $fileSize,
                'file_type' => $mimeType,
                'original_name' => $originalName
            ];
        } catch (\Exception $e) {
            Log::error('Base64 file upload error:', [
                'error' => $e->getMessage(),
                'type' => $type,
                'user_id' => $userId
            ]);
            throw new \Exception('Failed to upload base64 file: ' . $e->getMessage());
        }
    }

    /**
     * Get mime type for file type
     */
    private function getMimeTypeForFileType($type)
    {
        $mimeTypes = [
            'image' => 'image/jpeg',
            'video' => 'video/mp4',
            'audio' => 'audio/mpeg',
            'file' => 'application/octet-stream'
        ];
        return $mimeTypes[$type] ?? 'application/octet-stream';
    }

    /**
     * Get file extension from mime type
     */
    private function getExtensionFromMimeType($mimeType)
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'video/webm' => 'webm',
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'audio/wav' => 'wav',
            'audio/aac' => 'aac',
            'audio/ogg' => 'ogg',
            'audio/mp4' => 'm4a',
            'audio/x-m4a' => 'm4a',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];
        return $extensions[$mimeType] ?? 'bin';
    }

       /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, $conversationId)
    {
        try {
            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return $this->sendError('Conversation not found', null, 404);
            }

            // Check if user is participant
            $participant = ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->first();

            if (!$participant) {
                return $this->sendError('You are not a participant in this conversation', null, 403);
            }

            // Update last read timestamp
            $participant->update([
                'last_read_at' => now(),
            ]);

            return $this->sendResponse('Messages marked as read');
        } catch (\Exception $e) {
            return $this->sendError('Failed to mark messages as read', $e->getMessage(), 500);
        }
    }

    /**
     * Delete a message
     */
    public function destroy(Request $request, $conversationId, $messageId)
    {
        try {
            $message = Message::where('id', $messageId)
                ->where('conversation_id', $conversationId)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$message) {
                return $this->sendError('Message not found or you do not have permission to delete it', null, 404);
            }

            $message->update([
                'is_deleted' => true,
                'deleted_at' => now(),
            ]);

            return $this->sendResponse('Message deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete message', $e->getMessage(), 500);
        }
    }

    /**
     * Send a direct message (creates conversation if needed)
     */
    public function sendDirectMessage(Request $request)
    {
        // First, let's get the type from the request to build dynamic validation
        $messageType = $request->input('type');

        // Dynamic validation based on message type
        $rules = [
            'recipient_id' => 'required|exists:users,id|different:user_id',
            'type' => 'required|in:text,image,video,audio,file,location',
        ];

        // Add specific validation based on type
        if ($messageType === 'text') {
            $rules['message'] = 'required|string|max:4000';
        } elseif (in_array($messageType, ['image', 'video', 'audio', 'file'])) {
            $rules['file'] = 'required|file';
            $rules['message'] = 'nullable|string|max:1000'; // Optional caption

            // Add file-specific validation
            $this->addFileValidationRules($rules, $messageType);
        } elseif ($messageType === 'location') {
            $rules['latitude'] = 'required|numeric|between:-90,90';
            $rules['longitude'] = 'required|numeric|between:-180,180';
            $rules['address'] = 'nullable|string|max:500';
            $rules['message'] = 'nullable|string|max:1000';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $currentUser = $request->user();
            $recipientId = $request->recipient_id;

            // Check if private conversation already exists
            $conversation = Conversation::where('type', 'private')
                ->whereHas('participants', function ($query) use ($currentUser) {
                    $query->where('user_id', $currentUser->id)->where('is_active', true);
                })
                ->whereHas('participants', function ($query) use ($recipientId) {
                    $query->where('user_id', $recipientId)->where('is_active', true);
                })
                ->first();

            // Create conversation if it doesn't exist
            if (!$conversation) {
                $conversation = Conversation::create([
                    'type' => 'private',
                    'created_by' => $currentUser->id,
                ]);

                // Add participants
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $currentUser->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);

                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $recipientId,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
            }

            $messageData = [
                'conversation_id' => $conversation->id,
                'user_id' => $currentUser->id,
                'type' => $request->input('type'),
            ];

            // Handle different message types
            if ($request->input('type') === 'text') {
                $messageData['message'] = $request->input('message');
                $messageData['metadata'] = null;
            } elseif (in_array($request->input('type'), ['image', 'video', 'audio', 'file'])) {
                // Handle file upload
                $metadata = $this->handleFileUpload($request->file('file'), $request->input('type'), $currentUser->id);
                $messageData['message'] = $request->input('message') ?: null; // Optional caption
                $messageData['metadata'] = $metadata;
            } elseif ($request->input('type') === 'location') {
                $messageData['message'] = $request->input('message') ?: null;
                $messageData['metadata'] = [
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'address' => $request->input('address'),
                ];
            }

            // Create message
            $message = Message::create($messageData);

            // Update conversation's last message time
            $conversation->update([
                'last_message_at' => now(),
            ]);

            // Load relationships for response
            $message->load(['user']);

            // Dispatch notification job to recipient (background job)
            try {
                $messagePreview = $this->getMessagePreview($message);

                SendNewMessageNotificationJob::dispatch(
                    $currentUser->id,
                    $recipientId,
                    $message->id,
                    $conversation->id,
                    $messagePreview,
                    $message->type
                )->onQueue('notifications');

                Log::info('Direct message notification job dispatched', [
                    'sender_id' => $currentUser->id,
                    'receiver_id' => $recipientId,
                    'message_id' => $message->id
                ]);
            } catch (\Exception $notificationException) {
                Log::error('Failed to dispatch direct message notification job', [
                    'message_id' => $message->id,
                    'error' => $notificationException->getMessage()
                ]);
                // Don't fail the message sending if notification dispatch fails
            }

            // Broadcast the message to Pusher for real-time updates
            try {
                Log::info('Starting direct message Pusher broadcast', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'channel' => 'conversation.' . $conversation->id
                ]);

                // Get Pusher configuration with fallbacks
                $pusherKey = config('broadcasting.connections.pusher.key');
                $pusherSecret = config('broadcasting.connections.pusher.secret');
                $pusherAppId = config('broadcasting.connections.pusher.app_id');
                $pusherCluster = config('broadcasting.connections.pusher.options.cluster');

                // Validate Pusher configuration
                if (empty($pusherKey) || empty($pusherSecret) || empty($pusherAppId)) {
                    Log::warning('Pusher configuration missing for direct message, skipping broadcast', [
                        'key_exists' => !empty($pusherKey),
                        'secret_exists' => !empty($pusherSecret),
                        'app_id_exists' => !empty($pusherAppId),
                        'cluster' => $pusherCluster
                    ]);

                    // Skip broadcasting but don't fail the request
                    return $this->sendResponse('Message sent successfully', [
                        'message' => new MessageResource($message),
                        'conversation' => new ConversationResource($conversation->load(['users']))
                    ], 201);
                }

                // Direct Pusher broadcast using config values
                $pusher = new \Pusher\Pusher(
                    $pusherKey,
                    $pusherSecret,
                    $pusherAppId,
                    [
                        'cluster' => $pusherCluster ?: 'eu',
                        'useTLS' => true
                    ]
                );

                Log::info('Direct message Pusher instance created successfully');

                // Create simplified broadcast data to avoid resource serialization issues
                $broadcastData = [
                    'message' => [
                        'id' => $message->id,
                        'conversation_id' => $message->conversation_id,
                        'user_id' => $message->user_id,
                        'message' => $message->message,
                        'type' => $message->type,
                        'metadata' => $message->metadata,
                        'created_at' => $message->created_at->toISOString(),
                        'user' => [
                            'id' => $message->user->id,
                            'name' => $message->user->name,
                            'avatar' => $message->user->avatar ?? null
                        ]
                    ]
                ];

                Log::info('Direct message broadcast data prepared', [
                    'data_structure' => array_keys($broadcastData),
                    'message_keys' => array_keys($broadcastData['message'])
                ]);

                $result = $pusher->trigger('conversation.' . $conversation->id, 'message.sent', $broadcastData);

                Log::info('Direct message broadcasted successfully via direct Pusher', [
                    'message_id' => $message->id,
                    'conversation_id' => $conversation->id,
                    'channel' => 'conversation.' . $conversation->id,
                    'pusher_result' => $result,
                    'result_type' => gettype($result)
                ]);
            } catch (\Exception $broadcastException) {
                Log::error('Failed to broadcast direct message via direct Pusher', [
                    'message_id' => $message->id ?? 'not_available',
                    'conversation_id' => $conversation->id ?? 'not_available',
                    'error_message' => $broadcastException->getMessage(),
                    'error_code' => $broadcastException->getCode(),
                    'error_file' => $broadcastException->getFile(),
                    'error_line' => $broadcastException->getLine(),
                    'trace' => $broadcastException->getTraceAsString()
                ]);
                // Don't fail the request if broadcast fails
            }

            return $this->sendResponse('Message sent successfully', [
                'message' => new MessageResource($message),
                'conversation' => new ConversationResource($conversation->load(['users']))
            ], 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to send message', $e->getMessage(), 500);
        }
    }

    /**
     * Get message preview for notifications
     */
    private function getMessagePreview($message)
    {
        switch ($message->type) {
            case 'text':
                return Str::limit($message->message, 100);
            case 'image':
                return '📷 Photo';
            case 'video':
                return '🎥 Video';
            case 'audio':
                return '🎵 Audio';
            case 'file':
                return '📎 File';
            case 'location':
                return '📍 Location';
            default:
                return 'Sent a message';
        }
    }
}

