<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class SymlinkUploadHelper
{
    /**
     * Upload file to external directory via symlink
     *
     * @param UploadedFile $file
     * @param string $folder Subfolder within uploads directory
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

            // Verify the temp file exists and is readable
            $tempPath = $file->getPathname();
            if (!file_exists($tempPath) || !is_readable($tempPath)) {
                throw new \Exception("Temporary file not accessible: {$tempPath}");
            }

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Create external directory path
            $externalPath = 'C:/laragon/uploads/' . $folder;

            // Ensure directory exists
            if (!file_exists($externalPath)) {
                if (!mkdir($externalPath, 0755, true)) {
                    throw new \Exception("Failed to create directory: {$externalPath}");
                }
            }

            // Check if directory is writable
            if (!is_writable($externalPath)) {
                throw new \Exception("Directory not writable: {$externalPath}");
            }

            // Copy file directly instead of using move
            if (!copy($tempPath, $externalPath . '/' . $filename)) {
                throw new \Exception("Failed to copy file to: {$externalPath}/{$filename}");
            }

            // Create public URL
            $url = 'uploads/' . $folder . '/' . $filename;

            // Also ensure the file is accessible via web
            $publicPath = public_path('uploads/' . $folder);
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            // Create symlink if it doesn't exist
            if (!file_exists(public_path('uploads'))) {
                symlink('C:/laragon/uploads', public_path('uploads'));
            }

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $folder . '/' . $filename,
                'url' => $url,
                'full_url' => asset($url),
                'size' => filesize($externalPath . '/' . $filename),
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
