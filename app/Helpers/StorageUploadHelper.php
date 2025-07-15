<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StorageUploadHelper
{
    /**
     * Upload file to public storage
     *
     * @param UploadedFile $file
     * @param string $folder Subfolder within storage/app/public
     * @return array
     * @throws \Exception
     */
    public static function uploadFile(UploadedFile $file, string $folder = 'profiles')
    {
        try {
            // Check if file is valid
            if (!$file->isValid()) {
                throw new \Exception('Invalid file upload: ' . $file->getErrorMessage());
            }

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Store file in the public disk under the specified folder
            $path = $file->storeAs($folder, $filename, 'public');

            if (!$path) {
                throw new \Exception("Failed to store file");
            }

            // Get the public URL
            $url = Storage::disk('public')->url($path);

            // Log successful upload
            Log::info('File uploaded successfully', [
                'filename' => $filename,
                'path' => $path,
                'url' => $url
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $path,
                'url' => $url,
                'full_url' => asset($url),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'original_name' => $file->getClientOriginalName(),
            ];

        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        }
    }
}
