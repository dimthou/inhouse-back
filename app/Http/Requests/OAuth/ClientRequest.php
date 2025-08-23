<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'secret' => 'nullable|string|min:40',
            'redirect' => 'required|string|url',
            'personal_access_client' => 'boolean',
            'password_client' => 'boolean',
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Client name is required.',
            'name.max' => 'Client name cannot exceed 255 characters.',
            'secret.min' => 'Client secret must be at least 40 characters.',
            'redirect.required' => 'Redirect URI is required.',
            'redirect.url' => 'Redirect URI must be a valid URL.',
            'user_id.exists' => 'User does not exist.',
        ];
    }
}
