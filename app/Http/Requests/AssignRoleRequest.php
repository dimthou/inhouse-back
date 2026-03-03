<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
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
        return [
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,slug'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'At least one role must be selected',
            'roles.array' => 'Roles must be provided as an array',
            'roles.min' => 'At least one role must be selected',
            'roles.*.exists' => 'One or more selected roles do not exist'
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'You do not have permission to assign roles.'
        );
    }
}
