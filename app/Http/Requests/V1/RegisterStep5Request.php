<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Setting;

class RegisterStep5Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // Get max file upload size from settings table (in KB), default to 100MB (100000 KB)
        $maxFileSize = Setting::getValue('max_file_upload_size', 100000);

        return [
            'email' => 'required|email|exists:users,email',
            'profile_media' => "nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi,wmv|max:{$maxFileSize}",
            'bio' => 'nullable|string|max:500',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422));
    }
}
