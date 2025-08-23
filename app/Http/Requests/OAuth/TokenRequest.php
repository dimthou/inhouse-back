<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Foundation\Http\FormRequest;

class TokenRequest extends FormRequest
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
        $rules = [
            'grant_type' => 'required|string|in:authorization_code,password,client_credentials,refresh_token',
            'client_id' => 'required|string',
            'client_secret' => 'nullable|string',
            'scope' => 'nullable|string',
        ];

        // Add grant-specific validation rules
        switch ($this->input('grant_type')) {
            case 'authorization_code':
                $rules['code'] = 'required|string';
                $rules['redirect_uri'] = 'nullable|string|url';
                break;
            
            case 'password':
                $rules['username'] = 'required|string';
                $rules['password'] = 'required|string';
                break;
            
            case 'client_credentials':
                // No additional fields required
                break;
            
            case 'refresh_token':
                $rules['refresh_token'] = 'required|string';
                break;
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'grant_type.required' => 'Grant type is required.',
            'grant_type.in' => 'Grant type must be one of: authorization_code, password, client_credentials, refresh_token.',
            'client_id.required' => 'Client ID is required.',
            'code.required' => 'Authorization code is required for authorization_code grant.',
            'username.required' => 'Username is required for password grant.',
            'password.required' => 'Password is required for password grant.',
            'refresh_token.required' => 'Refresh token is required for refresh_token grant.',
            'redirect_uri.url' => 'Redirect URI must be a valid URL.',
        ];
    }
}
