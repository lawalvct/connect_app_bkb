<?php
namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Setting;

class CreatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get max file upload size from settings table (in KB), default to 100MB (102400 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 102400);

        return [
            'message' => 'nullable|string',
            'file' => "nullable|file|max:{$maxFileSize}", // Dynamic max from settings
            'type' => 'required|string|in:text,image,video',
            'social_id' => 'required|integer|exists:social_circles,id',
            'tagged_user_ids' => 'nullable|array',
            'tagged_user_ids.*' => 'exists:users,id',
        ];
    }
}
