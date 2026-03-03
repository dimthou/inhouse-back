<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('role.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleId)
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('roles', 'slug')->ignore($roleId)
            ],
            'description' => 'nullable|string|max:500',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,slug'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required',
            'name.unique' => 'This role name already exists',
            'slug.required' => 'Role slug is required',
            'slug.unique' => 'This role slug already exists',
            'slug.alpha_dash' => 'Slug can only contain letters, numbers, dashes and underscores',
            'permissions.*.exists' => 'One or more selected permissions do not exist'
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'You do not have permission to manage roles.'
        );
    }
}
