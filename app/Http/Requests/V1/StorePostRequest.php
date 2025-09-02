<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Setting;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // Get max file upload size from settings table (in KB), default to 50MB (50000 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 50000);

        return [
            'content' => 'nullable|string|max:5000',
            'social_circle_id' => 'required|exists:social_circles,id',
            'media' => 'nullable|array|max:10',
            'media.*' => "file|max:{$maxFileSize}|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi,wmv,flv,webm",
            'tagged_users' => 'nullable|array',
            'tagged_users.*' => 'exists:users,id',
            'location' => 'nullable|array',
            'location.lat' => 'nullable|numeric|between:-90,90',
            'location.lng' => 'nullable|numeric|between:-180,180',
            'location.address' => 'nullable|string|max:255',
            'scheduled_at' => 'nullable|date|after:now',
            'type' => ['nullable', Rule::in(['text', 'image', 'video', 'mixed'])],
        ];
    }

    public function messages(): array
    {
        // Get max file upload size from settings for error message
        $maxFileSize = Setting::getValue('max_file_upload_size', 50000);
        $maxFileSizeMB = round($maxFileSize / 1024, 1); // Convert KB to MB

        return [
            'social_circle_id.required' => 'Please select a social circle for your post.',
            'social_circle_id.exists' => 'Selected social circle does not exist.',
            'media.max' => 'You can upload maximum 10 media files.',
            'media.*.max' => "Each file must be less than {$maxFileSizeMB}MB.",
            'content.max' => 'Post content cannot exceed 5000 characters.',
            'scheduled_at.after' => 'Scheduled time must be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Auto-determine post type based on content and media
        if (!$this->has('type')) {
            $hasMedia = $this->hasFile('media') && count($this->file('media')) > 0;
            $hasContent = !empty($this->input('content'));

            if ($hasMedia && $hasContent) {
                $this->merge(['type' => 'mixed']);
            } elseif ($hasMedia) {
                // Determine if image or video based on first file
                $firstFile = $this->file('media')[0] ?? null;
                if ($firstFile) {
                    $mimeType = $firstFile->getMimeType();
                    $type = str_starts_with($mimeType, 'image/') ? 'image' :
                           (str_starts_with($mimeType, 'video/') ? 'video' : 'mixed');
                    $this->merge(['type' => $type]);
                }
            } else {
                $this->merge(['type' => 'text']);
            }
        }
    }
}
