<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:63206'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif', 'max:10240'],
            'remove_media' => ['nullable', 'boolean'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', 'integer', 'exists:social_accounts,id'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => __('Please enter your post content.'),
            'content.max' => __('Post content is too long.'),
            'media.mimes' => __('Please upload a valid image (JPG, PNG, or GIF).'),
            'media.max' => __('Image must be less than 10MB.'),
            'channels.required' => __('Please select at least one channel.'),
            'channels.min' => __('Please select at least one channel.'),
            'scheduled_at.after' => __('Scheduled time must be in the future.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        $validChannels = $user->activeSocialAccounts()->pluck('id')->toArray();

        if ($this->has('channels')) {
            $this->merge([
                'channels' => array_intersect($this->channels, $validChannels),
            ]);
        }
    }
}
