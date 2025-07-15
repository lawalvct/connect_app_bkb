<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class SymlinkUploadHelper
{
    /**
     * Upload file to external directory via symlink with environment detection
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

            // Determine environment-specific paths
            $isProduction = app()->environment('production');

            // Set external directory path based on environment
            if ($isProduction) {
                $externalPath = '/www/wwwroot/uploads/' . $folder;
                $publicUploadsPath = '/www/wwwroot/connectapp.myschoolep.com/public/uploads';
            } else {
                $externalPath = 'C:/laragon/uploads/' . $folder;
                $publicUploadsPath = public_path('uploads');
            }

            // Ensure external directory exists
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

            // Create symlink if it doesn't exist (environment-specific)
            if ($isProduction) {
                // For production server
                // Ensure the uploads directory exists in public
                if (!file_exists('/www/wwwroot/connectapp.myschoolep.com/public/uploads')) {
                    // Create the directory first
                    mkdir('/www/wwwroot/connectapp.myschoolep.com/public/uploads', 0755, true);

                    // Create symlink for the entire uploads directory
                    symlink('/www/wwwroot/uploads', '/www/wwwroot/connectapp.myschoolep.com/public/uploads');
                }

                // Ensure the specific folder exists
                $publicFolderPath = '/www/wwwroot/connectapp.myschoolep.com/public/uploads/' . $folder;
                if (!file_exists($publicFolderPath)) {
                    mkdir($publicFolderPath, 0755, true);
                }
            } else {
                // For local development
                // Also ensure the file is accessible via web
                $publicPath = public_path('uploads/' . $folder);
                if (!file_exists($publicPath)) {
                    mkdir($publicPath, 0755, true);
                }

                // Create symlink if it doesn't exist
                if (!file_exists(public_path('uploads'))) {
                    symlink('C:/laragon/uploads', public_path('uploads'));
                }
            }

            // Log successful upload
            Log::info('File uploaded successfully', [
                'environment' => $isProduction ? 'production' : 'local',
                'filename' => $filename,
                'path' => $externalPath . '/' . $filename,
                'url' => $url
            ]);

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

    /**
     * Alternative method that doesn't rely on symlinks
     * This is useful if symlinks don't work in your environment
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return array
     * @throws \Exception
     */
    public static function uploadFileWithoutSymlink(UploadedFile $file, string $folder = 'profiles')
    {
        try {
            // Check if file is valid
            if (!$file->isValid()) {
                throw new \Exception('Invalid file upload: ' . $file->getErrorMessage());
            }

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Determine environment-specific paths
            $isProduction = app()->environment('production');

            // Set external directory path based on environment
            if ($isProduction) {
                $externalPath = '/www/wwwroot/uploads/' . $folder;
            } else {
                $externalPath = 'C:/laragon/uploads/' . $folder;
            }

            // Ensure external directory exists
            if (!file_exists($externalPath)) {
                if (!mkdir($externalPath, 0755, true)) {
                    throw new \Exception("Failed to create directory: {$externalPath}");
                }
            }

            // Move the file to the external directory
            if (!$file->move($externalPath, $filename)) {
                throw new \Exception("Failed to move file to: {$externalPath}/{$filename}");
            }

            // Also copy to public directory for direct web access
            if ($isProduction) {
                $publicPath = '/www/wwwroot/connectapp.myschoolep.com/public/uploads/' . $folder;
            } else {
                $publicPath = public_path('uploads/' . $folder);
            }

            // Ensure public directory exists
            if (!file_exists($publicPath)) {
                if (!mkdir($publicPath, 0755, true)) {
                    throw new \Exception("Failed to create public directory: {$publicPath}");
                }
            }

            // Copy file to public directory
            if (!copy($externalPath . '/' . $filename, $publicPath . '/' . $filename)) {
                throw new \Exception("Failed to copy file to public directory");
            }

            // Create public URL
            $url = 'uploads/' . $folder . '/' . $filename;

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
