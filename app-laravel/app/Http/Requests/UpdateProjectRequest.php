<?php

namespace App\Http\Requests;

use App\Exceptions\SsrfException;
use App\Services\Security\UrlValidator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // L'auth est gérée par le middleware + Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = auth()->id();
        $project = $this->route('project'); // Objet Project via route model binding
        $projectId = $project instanceof \App\Models\Project ? $project->id : $project;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                "unique:projects,name,{$projectId},id,user_id,{$userId}",
            ],
            'url' => [
                'sometimes',
                'required',
                'url',
                'max:2048',
                function ($attribute, $value, $fail) {
                    try {
                        app(UrlValidator::class)->validate($value);
                    } catch (SsrfException $e) {
                        $fail("L'URL est bloquée pour des raisons de sécurité : " . $e->getMessage());
                    }
                },
            ],
            'status' => [
                'sometimes',
                'in:active,paused,archived',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du projet est requis.',
            'name.unique' => 'Vous avez déjà un projet avec ce nom.',
            'url.required' => 'L\'URL est requise.',
            'url.url' => 'L\'URL doit être une URL valide.',
            'status.in' => 'Le statut doit être active, paused ou archived.',
        ];
    }
}
