<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Setting;

class CreateStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get max file upload size from settings table (in KB), default to 50MB (51200 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 51200);

        $type = $this->input('type');

        // Base rules
        $rules = [
            'type' => 'required|in:text,image,video',
            'privacy' => 'required|in:all_connections,close_friends,only_me,custom',
            'allow_replies' => 'required|boolean',
        ];

        // Conditional rules based on story type
        if ($type === 'text') {
            // Text story requires content, background_color, font_settings
            $rules['content'] = 'required|string|max:280';
            $rules['background_color'] = 'required|string|regex:/^#[0-9A-Fa-f]{6}$/';
            $rules['font_settings'] = 'required|array';
            $rules['font_settings.size'] = 'required|integer|min:12|max:48';
            $rules['font_settings.family'] = 'required|string|in:System,sans-serif,serif,monospace';
            $rules['font_settings.weight'] = 'required|string|in:normal,bold,600,700';
            $rules['file'] = 'nullable'; // File is NOT required for text stories
            $rules['caption'] = 'nullable';
        } elseif (in_array($type, ['image', 'video'])) {
            // Image/Video story requires file
            $rules['file'] = "required|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi,webm|max:{$maxFileSize}";
            $rules['caption'] = 'nullable|string|max:200'; // Optional caption
            $rules['content'] = 'nullable'; // Content is caption for media
            $rules['background_color'] = 'nullable';
            $rules['font_settings'] = 'nullable';
        }

        // Custom viewers (only for custom privacy)
        if ($this->input('privacy') === 'custom') {
            $rules['custom_viewers'] = 'required|array|min:1';
            $rules['custom_viewers.*'] = 'exists:users,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        // Get max file upload size from settings for error message
        $maxFileSize = Setting::getValue('max_file_upload_size', 51200);
        $maxFileSizeMB = round($maxFileSize / 1024, 1); // Convert KB to MB

        return [
            'type.required' => 'Story type is required.',
            'type.in' => 'Story type must be text, image, or video.',

            // Text story messages
            'content.required' => 'Content is required for text stories.',
            'content.max' => 'Text content cannot exceed 280 characters.',
            'background_color.required' => 'Background color is required for text stories.',
            'background_color.regex' => 'Background color must be a valid hex color (e.g., #FF0000).',
            'font_settings.required' => 'Font settings are required for text stories.',
            'font_settings.array' => 'Font settings must be an object.',
            'font_settings.size.required' => 'Font size is required.',
            'font_settings.size.min' => 'Font size must be at least 12.',
            'font_settings.size.max' => 'Font size cannot exceed 48.',
            'font_settings.family.required' => 'Font family is required.',
            'font_settings.family.in' => 'Font family must be one of: System, sans-serif, serif, monospace.',
            'font_settings.weight.required' => 'Font weight is required.',
            'font_settings.weight.in' => 'Font weight must be one of: normal, bold, 600, 700.',

            // Media story messages
            'file.required' => 'File is required for image and video stories.',
            'file.file' => 'The uploaded file must be a valid file.',
            'file.mimes' => 'File must be an image (jpg, jpeg, png, gif) or video (mp4, mov, avi, webm).',
            'file.max' => "File size cannot exceed {$maxFileSizeMB}MB.",
            'caption.max' => 'Caption cannot exceed 200 characters.',

            // Common messages
            'privacy.required' => 'Privacy setting is required.',
            'privacy.in' => 'Privacy must be one of: all_connections, close_friends, only_me, custom.',
            'allow_replies.required' => 'Allow replies setting is required.',
            'allow_replies.boolean' => 'Allow replies must be true or false.',
            'custom_viewers.required' => 'Custom viewers are required when privacy is set to custom.',
            'custom_viewers.array' => 'Custom viewers must be an array.',
            'custom_viewers.min' => 'At least one viewer must be selected for custom privacy.',
            'custom_viewers.*.exists' => 'One or more selected viewers do not exist.',
        ];
    }
}
