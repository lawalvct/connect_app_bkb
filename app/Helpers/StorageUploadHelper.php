<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class StorageUploadHelper
{
    /**
     * Upload file to public/uploads directory
     *
     * @param UploadedFile $file
     * @param string $folder Subfolder within public/uploads
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

            // Set the upload directory path
            $uploadDir = public_path('uploads/' . $folder);

            // Ensure the directory exists
            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }

            // Move the uploaded file to the destination
            $file->move($uploadDir, $filename);

            // Full path to the file
            $fullPath = $uploadDir . '/' . $filename;

            // Verify the file was uploaded successfully
            if (!File::exists($fullPath)) {
                throw new \Exception("File was not uploaded successfully");
            }

            // Generate URL
            $url = 'uploads/' . $folder . '/' . $filename;

            // Log successful upload
            Log::info('File uploaded successfully', [
                'filename' => $filename,
                'path' => $fullPath,
                'url' => $url
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => 'uploads/' . $folder . '/' . $filename,
                'url' => $url,
                'full_url' => asset($url),
                'size' => File::size($fullPath),
                'mime_type' => File::mimeType($fullPath),
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
