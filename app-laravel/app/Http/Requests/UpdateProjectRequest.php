<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
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
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')
                    ->where(function ($query) {
                        return $query->where('user_id', $this->user()->id);
                    })
                    ->ignore($this->route('project')),
            ],
            'url' => 'sometimes|required|url|max:2048',
            'status' => 'sometimes|in:active,paused,archived',
        ];
    }
}
