<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For creating users
        if ($this->isMethod('POST')) {
            return $this->user()->hasPermission('user.create');
        }

        // For updating users
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $this->user()->hasPermission('user.edit');
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'password' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->mixedCase()
                    ->symbols()
            ],
            'password_confirmation' => 'required_with:password|same:password',
            'roles' => 'sometimes|array',
            'roles.*' => 'exists:roles,slug'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'User name is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'password.required' => 'Password is required',
            'password_confirmation.same' => 'Password confirmation does not match',
            'roles.*.exists' => 'One or more selected roles do not exist'
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'You do not have permission to manage users.'
        );
    }
}
