<?php

namespace App\Http\Requests\User;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && (Auth::user()->can('user-management.create') ?? true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['nullable', Password::defaults()],
            'ic_number' => ['nullable', 'string', 'max:12', 'unique:users'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'office_phone_number' => ['nullable', 'string', 'max:20'],
            'agency' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'scheme_and_grade' => ['nullable', 'string', 'max:10'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'is_active' => ['boolean'],
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
            'name' => 'full name',
            'email' => 'email address',
            'ic_number' => 'IC number',
            'phone_number' => 'mobile phone number',
            'office_phone_number' => 'office phone number',
            'scheme_and_grade' => 'scheme and grade',
            'position_title' => 'position title',
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
            'name.required' => 'The full name field is required.',
            'email.required' => 'The email address field is required.',
            'email.unique' => 'This email address is already registered.',
            'ic_number.unique' => 'This IC number is already registered.',
            'agency.required' => 'The agency field is required.',
            'role.exists' => 'The selected role is invalid.',
        ];
    }
}
