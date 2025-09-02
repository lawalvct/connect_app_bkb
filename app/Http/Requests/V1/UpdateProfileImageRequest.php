<?php
namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Setting;

class UpdateProfileImageRequest extends FormRequest
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
        // Get max file upload size from settings table (in KB), default to 2MB (2048 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 2048);

        return [
            'image' => "required|image|max:{$maxFileSize}",
        ];
    }
}
