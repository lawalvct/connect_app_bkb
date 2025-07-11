<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ad_name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:banner,video,carousel,story,feed',
            'description' => 'nullable|string|max:1000',
            'media_files.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:50000',
            'call_to_action' => 'nullable|string|max:100',
            'destination_url' => 'nullable|url',
            'ad_placement' => 'sometimes|array|min:1', // At least one social circle if provided
            'ad_placement.*' => 'integer|exists:social_circles,id',
            'start_date' => 'sometimes|date|after_or_equal:today',
            'end_date' => 'sometimes|date|after:start_date',
            'budget' => 'sometimes|numeric|min:10|max:100000',
            'daily_budget' => 'nullable|numeric|min:1|max:10000',
            'target_impressions' => 'nullable|integer|min:100|max:10000000',
            'target_audience' => 'nullable|array'
        ];
    }

    public function messages(): array
    {
        return [
            'ad_placement.array' => 'Ad placement must be an array of social circle IDs.',
            'ad_placement.min' => 'Please select at least one social circle for ad placement.',
            'ad_placement.*.integer' => 'Each ad placement must be a valid social circle ID.',
            'ad_placement.*.exists' => 'Selected social circle does not exist.',
        ];
    }
}
