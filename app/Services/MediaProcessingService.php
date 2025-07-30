<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Helpers\StorageUploadHelper;

class MediaProcessingService
{
    protected $localDisk;
    protected $imageManager;
    protected $allowedImageTypes = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
    protected $allowedVideoTypes = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm'];
    protected $baseUploadPath = 'posts'; // Changed to match StorageUploadHelper pattern

    public function __construct()
    {
        $this->localDisk = Storage::disk('public');
        $this->imageManager = new ImageManager(new GdDriver());
    }

    /**
     * Process and upload media file
     */
    public function processMedia(UploadedFile $file, string $postId): array
    {
        $fileExtension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        $originalName = $file->getClientOriginalName();

        // Generate unique filename
        $filename = $this->generateFilename($fileExtension);

        // Determine file type
        $type = $this->determineFileType($fileExtension, $mimeType);

        $result = [
            'type' => $type,
            'original_name' => $originalName,
            'file_size' => $file->getSize(),
            'mime_type' => $mimeType,
        ];

        try {
            if ($type === 'image') {
                $result = array_merge($result, $this->processImageLocal($file, $filename, $fileExtension, $postId));
            } elseif ($type === 'video') {
                $result = array_merge($result, $this->processVideoLocal($file, $filename, $postId));
            } else {
                // Handle other file types (documents, etc.)
                $result = array_merge($result, $this->processDocumentLocal($file, $filename, $postId));
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Media processing failed, falling back to simple upload', [
                'file' => $originalName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback: Simple file upload without processing
            try {
                $folder = "posts/{$postId}";
                $uploadResult = StorageUploadHelper::uploadFile($file, $folder);

                if (!$uploadResult['success']) {
                    throw new \Exception('Fallback upload also failed');
                }

                return [
                    'type' => 'document', // Treat as document if processing fails
                    'original_name' => $originalName,
                    'file_size' => $file->getSize(),
                    'mime_type' => $mimeType,
                    'file_path' => $uploadResult['path'],
                    'file_url' => $uploadResult['full_url'],
                    'width' => null,
                    'height' => null,
                    'duration' => null,
                    'thumbnail_path' => null,
                    'thumbnail_url' => null,
                    'compressed_versions' => null,
                ];

            } catch (\Exception $fallbackError) {
                Log::error('Fallback upload also failed', [
                    'file' => $originalName,
                    'error' => $fallbackError->getMessage()
                ]);
                throw new \Exception('Failed to process media file: ' . $e->getMessage());
            }
        }
    }

    /**
     * Process image file locally using StorageUploadHelper approach
     */
    protected function processImageLocal(UploadedFile $file, string $filename, string $extension, string $postId): array
    {
        try {
            // Get image dimensions BEFORE uploading (while file is still accessible)
            $width = null;
            $height = null;
            $image = null;

            try {
                // Read image to get dimensions and for thumbnail creation
                $image = $this->imageManager->read($file->getPathname());
                $width = $image->width();
                $height = $image->height();
            } catch (\Exception $imageError) {
                Log::warning('Could not read image for dimensions', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $imageError->getMessage()
                ]);
                // Continue without dimensions - upload the file anyway
            }

            // Create subfolder for post images
            $folder = "posts/{$postId}";

            // Use StorageUploadHelper to save the file
            $uploadResult = StorageUploadHelper::uploadFile($file, $folder);

            if (!$uploadResult['success']) {
                throw new \Exception('Failed to upload image file');
            }

            // Generate thumbnail path
            $thumbnailFilename = 'thumb_' . $uploadResult['filename'];
            $thumbnailPath = 'uploads/' . $folder . '/' . $thumbnailFilename;
            $thumbnailUrl = null;

            // Create thumbnail if image was successfully read
            if ($image !== null) {
                try {
                    $this->createThumbnailLocal($image, $folder, $thumbnailFilename);
                    $thumbnailUrl = asset($thumbnailPath);
                } catch (\Exception $thumbError) {
                    Log::warning('Thumbnail creation failed but continuing', [
                        'error' => $thumbError->getMessage(),
                        'file' => $uploadResult['filename']
                    ]);
                    // Continue without thumbnail
                }
            }

            return [
                'file_path' => $uploadResult['path'],
                'file_url' => $uploadResult['full_url'],
                'width' => $width,
                'height' => $height,
                'thumbnail_path' => $thumbnailUrl ? $thumbnailPath : null,
                'thumbnail_url' => $thumbnailUrl,
                'compressed_versions' => null, // Can add compression logic later
            ];

        } catch (\Exception $e) {
            Log::error('Local image processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process video file locally
     */
    protected function processVideoLocal(UploadedFile $file, string $filename, string $postId): array
    {
        try {
            // Create subfolder for post videos
            $folder = "posts/{$postId}";

            // Use StorageUploadHelper to save the file
            $uploadResult = StorageUploadHelper::uploadFile($file, $folder);

            if (!$uploadResult['success']) {
                throw new \Exception('Failed to upload video file');
            }

            // Get video info (you can expand this to get duration, dimensions etc.)
            $duration = null;
            $width = null;
            $height = null;

            // Generate thumbnail for video (basic implementation)
            $thumbnailFilename = 'thumb_' . pathinfo($uploadResult['filename'], PATHINFO_FILENAME) . '.jpg';
            $thumbnailPath = 'uploads/' . $folder . '/' . $thumbnailFilename;

            // Create a basic video thumbnail (you can enhance this later)
            $this->createVideoThumbnailLocal($uploadResult['path'], $folder, $thumbnailFilename);

            return [
                'file_path' => $uploadResult['path'],
                'file_url' => $uploadResult['full_url'],
                'width' => $width,
                'height' => $height,
                'duration' => $duration,
                'thumbnail_path' => $thumbnailPath,
                'thumbnail_url' => asset($thumbnailPath),
                'compressed_versions' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Local video processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process document file locally
     */
    protected function processDocumentLocal(UploadedFile $file, string $filename, string $postId): array
    {
        try {
            // Create subfolder for post documents
            $folder = "posts/{$postId}";

            // Use StorageUploadHelper to save the file
            $uploadResult = StorageUploadHelper::uploadFile($file, $folder);

            if (!$uploadResult['success']) {
                throw new \Exception('Failed to upload document file');
            }

            return [
                'file_path' => $uploadResult['path'],
                'file_url' => $uploadResult['full_url'],
                'width' => null,
                'height' => null,
                'duration' => null,
                'thumbnail_path' => null,
                'thumbnail_url' => null,
                'compressed_versions' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Local document processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create thumbnail locally
     */
    protected function createThumbnailLocal($image, string $folder, string $thumbnailFilename): void
    {
        try {
            // Create thumbnail directory
            $thumbnailDir = public_path('uploads/' . $folder);
            if (!File::exists($thumbnailDir)) {
                File::makeDirectory($thumbnailDir, 0755, true);
            }

            // Clone the image to avoid modifying the original
            $thumbnail = clone $image;

            // Resize image to thumbnail (maintain aspect ratio)
            $thumbnail = $thumbnail->scale(width: 300);

            // Save thumbnail
            $thumbnailPath = $thumbnailDir . '/' . $thumbnailFilename;

            // Encode as JPEG with error handling
            try {
                $encoded = $thumbnail->encode(new JpegEncoder(quality: 80));
                File::put($thumbnailPath, $encoded->toString());
            } catch (\Exception $encodeError) {
                // Try saving as PNG if JPEG fails
                $encoded = $thumbnail->encode(new PngEncoder());
                $thumbnailPath = str_replace('.jpg', '.png', $thumbnailPath);
                File::put($thumbnailPath, $encoded->toString());
            }

        } catch (\Exception $e) {
            Log::error('Thumbnail creation failed', [
                'error' => $e->getMessage(),
                'folder' => $folder,
                'filename' => $thumbnailFilename
            ]);
            // Don't throw - thumbnail is optional
        }
    }

    /**
     * Create video thumbnail locally (basic implementation)
     */
    protected function createVideoThumbnailLocal(string $videoPath, string $folder, string $thumbnailFilename): void
    {
        try {
            // Create a simple placeholder thumbnail for videos
            // You can enhance this later with FFMpeg to extract actual video frames
            $thumbnailDir = public_path('uploads/' . $folder);
            if (!File::exists($thumbnailDir)) {
                File::makeDirectory($thumbnailDir, 0755, true);
            }

            // Create a simple placeholder image for now
            $image = $this->imageManager->create(300, 200)->fill('rgb(200, 200, 200)');
            $thumbnailPath = $thumbnailDir . '/' . $thumbnailFilename;
            $encoded = $image->encode(new JpegEncoder(quality: 80));
            File::put($thumbnailPath, $encoded);

        } catch (\Exception $e) {
            Log::error('Video thumbnail creation failed', [
                'error' => $e->getMessage(),
                'video_path' => $videoPath,
                'filename' => $thumbnailFilename
            ]);
            // Don't throw - thumbnail is optional
        }
    }

    /* ===== COMMENTED OUT S3 LOGIC - CAN BE RESTORED LATER =====

    /**
     * Process image file (S3 version - COMMENTED OUT)
     */
    /*
    protected function processImage(UploadedFile $file, string $basePath, string $filename, string $extension): array
    {
        $image = $this->imageManager->read($file->getPathname()); // Reads image and auto-orients
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Create directory if it doesn't exist
        $this->ensureDirectoryExists("{$basePath}/original");

        // Upload original (compressed)
        $originalPath = "{$basePath}/original/{$filename}";

        // Encode based on original extension or default to JPEG
        $encodedOriginal = match(strtolower($extension)) {
            'png' => $image->encode(new PngEncoder()),
            'gif' => $image->encode(new GifEncoder()),
            'webp' => $image->encode(new WebpEncoder(quality: 85)),
            'jpg', 'jpeg' => $image->encode(new JpegEncoder(quality: 85)),
            default => $image->encode(new JpegEncoder(quality: 85)) // Fallback
        };

        $this->localDisk->put($originalPath, $encodedOriginal->toString());

        // Create different sizes
        $compressedVersions = [];
        $sizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ];

        // Determine the extension for resized images (e.g., always jpg for thumbnails, or keep original format)
        $resizedExtension = 'jpg';
        $resizedFilenameBase = pathinfo($filename, PATHINFO_FILENAME);

        foreach ($sizes as $sizeName => $dimensions) {
            // Create directory if it doesn't exist
            $this->ensureDirectoryExists("{$basePath}/{$sizeName}");

            // Clone the image object to avoid modifying the original in loop
            $resizedImage = clone $image;
            $resizedImage->cover($dimensions['width'], $dimensions['height']); // Use cover or fit

            $resizedSpecificFilename = $resizedFilenameBase . '.' . $resizedExtension;
            $resizedPath = "{$basePath}/{$sizeName}/{$resizedSpecificFilename}";

            $resizedCompressed = $resizedImage->encode(new JpegEncoder(quality: 80));

            $this->localDisk->put($resizedPath, $resizedCompressed->toString());
            $compressedVersions[$sizeName] = asset("storage/{$resizedPath}");
        }

        return [
            'file_path' => $originalPath,
            'file_url' => asset("storage/{$originalPath}"),
            'width' => $originalWidth,
            'height' => $originalHeight,
            'compressed_versions' => $compressedVersions,
        ];
    }
    */

    /**
     * Process video file (S3 version - COMMENTED OUT)
     */
    /*
    protected function processVideo(UploadedFile $file, string $basePath, string $filename): array
    {
        // Create directory if it doesn't exist
        $this->ensureDirectoryExists("{$basePath}/videos");

        // Upload original video
        $originalPath = "{$basePath}/videos/{$filename}";
        $this->localDisk->putFileAs($basePath . '/videos', $file, $filename);

        // Get video info (you might need ffprobe for this)
        $videoInfo = $this->getVideoInfo($file);

        // Generate thumbnail
        $thumbnailPath = $this->generateVideoThumbnail($file, $basePath, $filename);

        return [
            'file_path' => $originalPath,
            'file_url' => asset("storage/{$originalPath}"),
            'width' => $videoInfo['width'] ?? null,
            'height' => $videoInfo['height'] ?? null,
            'duration' => $videoInfo['duration'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_url' => $thumbnailPath ? asset("storage/{$thumbnailPath}") : null,
        ];
    }

    /**
     * Process document file (S3 version - COMMENTED OUT)
     */
    /*
    protected function processDocument(UploadedFile $file, string $basePath, string $filename): array
    {
        // Create directory if it doesn't exist
        $this->ensureDirectoryExists("{$basePath}/documents");

        $documentPath = "{$basePath}/documents/{$filename}";
        $this->localDisk->putFileAs($basePath . '/documents', $file, $filename);

        return [
            'file_path' => $documentPath,
            'file_url' => asset("storage/{$documentPath}"),
        ];
    }

    /**
     * Generate video thumbnail (S3 version - COMMENTED OUT)
     */
    /*
    protected function generateVideoThumbnail(UploadedFile $file, string $basePath, string $filename): ?string
    {
        try {
            // Create directory if it doesn't exist
            $this->ensureDirectoryExists("{$basePath}/thumbnails");

            $thumbnailFilename = pathinfo($filename, PATHINFO_FILENAME) . '_thumb.jpg';
            $thumbnailPath = "{$basePath}/thumbnails/{$thumbnailFilename}";

            // Placeholder for FFMpeg logic
            // $ffmpeg = FFMpeg::create([...]); // Configure FFMpeg
            // $video = $ffmpeg->open($file->getPathname());
            // $frame = $video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(1));
            // $tempThumbnailPath = tempnam(sys_get_temp_dir(), 'thumb') . '.jpg';
            // $frame->save($tempThumbnailPath);
            // $this->localDisk->put($thumbnailPath, file_get_contents($tempThumbnailPath));
            // unlink($tempThumbnailPath);

            // For now, returning the path, assuming it will be generated
            // If FFMpeg is not set up, you might want to return null or handle it
            // $this->localDisk->put($thumbnailPath, ''); // Example: Put an empty file or placeholder
            return $thumbnailPath; // Or null if not implemented
        } catch (\Exception $e) {
            Log::warning('Thumbnail generation failed', ['file' => $filename, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get video information (S3 version - COMMENTED OUT)
     */
    /*
    protected function getVideoInfo(UploadedFile $file): array
    {
        // Placeholder for FFProbe logic
        // $ffmpeg = FFMpeg::create([...]);
        // $ffprobe = $ffmpeg->getFFProbe();
        // $format = $ffprobe->format($file->getPathname());
        // $stream = $ffprobe->streams($file->getPathname())->videos()->first();
        // return [
        //     'width' => $stream->get('width'),
        //     'height' => $stream->get('height'),
        //     'duration' => (int) round($format->get('duration')),
        // ];
        return [
            'width' => null,
            'height' => null,
            'duration' => null,
        ];
    }
    */

    /* ===== END COMMENTED OUT S3 LOGIC ===== */

    /**
     * Determine file type based on extension and mime type
     */
    protected function determineFileType(string $extension, string $mimeType): string
    {
        if (in_array($extension, $this->allowedImageTypes) || str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (in_array($extension, $this->allowedVideoTypes) || str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    /**
     * Generate unique filename
     */
    protected function generateFilename(string $extension): string
    {
        return Str::uuid() . '.' . $extension;
    }
}
