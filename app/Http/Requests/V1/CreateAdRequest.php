<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ad_name' => 'required|string|max:255',
            'type' => 'required|in:banner,video,carousel,story,feed',
            'description' => 'nullable|string|max:1000',
            'media_files.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:50000',
            'call_to_action' => 'nullable|string|max:100',
            'destination_url' => 'nullable|url',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'required|numeric|min:10|max:100000',
            'daily_budget' => 'nullable|numeric|min:1|max:10000',
            'target_impressions' => 'nullable|integer|min:100|max:10000000',
            'target_audience' => 'nullable|array',
            'target_audience.age_min' => 'nullable|integer|min:13|max:100',
            'target_audience.age_max' => 'nullable|integer|min:13|max:100|gte:target_audience.age_min',
            'target_audience.gender' => 'nullable|in:male,female,all',
            'target_audience.locations' => 'nullable|array',
            'target_audience.locations.*' => 'string|max:100',
            'target_audience.interests' => 'nullable|array',
            'target_audience.interests.*' => 'string|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'ad_name.required' => 'Advertisement name is required.',
            'type.required' => 'Advertisement type is required.',
            'type.in' => 'Invalid advertisement type selected.',
            'media_files.*.file' => 'Each media file must be a valid file.',
            'media_files.*.mimes' => 'Media files must be of type: jpg, jpeg, png, gif, mp4, mov, avi.',
            'media_files.*.max' => 'Each media file must not exceed 50MB.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date must be today or later.',
            'end_date.required' => 'End date is required.',
            'end_date.after' => 'End date must be after start date.',
            'budget.required' => 'Budget is required.',
            'budget.min' => 'Minimum budget is $10.',
            'budget.max' => 'Maximum budget is $100,000.',
            'target_audience.age_max.gte' => 'Maximum age must be greater than or equal to minimum age.',
        ];
    }
}
