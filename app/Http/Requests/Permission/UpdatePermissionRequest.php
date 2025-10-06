<?php

namespace App\Http\Requests\Permission;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && (Auth::user()->can('permission-management.edit') ?? true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $permissionId = request()->route('permission')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permissionId)],
            'module' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'permission name',
            'is_active' => 'status',
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
            'name.required' => 'The permission name field is required.',
            'name.unique' => 'This permission name is already taken.',
            'module.required' => 'The module field is required.',
            'roles.*.exists' => 'One or more selected roles are invalid.',
        ];
    }
}
