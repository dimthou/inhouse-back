<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Foundation\Http\FormRequest;

class AuthorizationRequest extends FormRequest
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
            'response_type' => 'required|string|in:code',
            'client_id' => 'required|string',
            'redirect_uri' => 'nullable|string|url',
            'scope' => 'nullable|string',
            'state' => 'nullable|string',
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'response_type.required' => 'Response type is required.',
            'response_type.in' => 'Response type must be "code".',
            'client_id.required' => 'Client ID is required.',
            'redirect_uri.url' => 'Redirect URI must be a valid URL.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'password.required' => 'Password is required.',
        ];
    }
}
