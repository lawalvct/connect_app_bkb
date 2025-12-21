<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterStep1Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Extract email username (part before @) as default for name/username
        $email = $this->input('email');
        if ($email) {
            $emailUsername = explode('@', $email)[0];

            // Set default for username if not provided
            if (!$this->has('username') || empty($this->input('username'))) {
                $this->merge([
                    'username' => $emailUsername
                ]);
            }

            // Set default for name if not provided
            if (!$this->has('name') || empty($this->input('name'))) {
                $this->merge([
                    'name' => $emailUsername
                ]);
            }
        }
    }

    public function rules()
    {
        return [
            'username' => 'nullable|string|max:255|unique:users',
            'name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'device_token' => 'nullable|string',
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
