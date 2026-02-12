<?php

namespace App\Http\Requests;

use App\Exceptions\SsrfException;
use App\Services\Security\UrlValidator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBacklinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_url' => [
                'sometimes',
                'required',
                'url',
                'max:2048',
                function ($attribute, $value, $fail) {
                    try {
                        app(UrlValidator::class)->validate($value);
                    } catch (SsrfException $e) {
                        $fail("L'URL source est bloquée pour des raisons de sécurité : " . $e->getMessage());
                    }
                },
            ],
            'target_url' => [
                'sometimes',
                'required',
                'url',
                'max:2048',
            ],
            'anchor_text' => 'nullable|string|max:500',
            'status' => 'sometimes|in:active,lost,changed',
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
            'source_url.required' => "L'URL source est requise.",
            'source_url.url' => "L'URL source doit être une URL valide.",
            'target_url.required' => "L'URL cible est requise.",
            'target_url.url' => "L'URL cible doit être une URL valide.",
            'status.in' => 'Le statut doit être active, lost ou changed.',
        ];
    }
}
