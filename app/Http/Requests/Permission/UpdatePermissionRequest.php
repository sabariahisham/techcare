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
        return Auth::check() && (Auth::user()->can('capaian.edit') ?? true);
    }

    /**
     * Get the validated data with sanitization.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if (is_array($validated)) {
            foreach (['name', 'description', 'module'] as $field) {
                if (isset($validated[$field])) {
                    $validated[$field] = $this->sanitizeInput($validated[$field]);
                }
            }
        }

        return $validated;
    }

    /**
     * Sanitize input data
     */
    private function sanitizeInput(?string $input): ?string
    {
        if (is_null($input)) {
            return null;
        }

        return trim(strip_tags($input));
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions')->ignore($permissionId),
                'regex:/^[a-zA-Z0-9\s\-_.]+$/' // Only allow alphanumeric, spaces, hyphens, underscores, and dots
            ],
            'module' => [
                'required',
                'string',
                'max:255',
                'exists:permissions,module' // Ensure module exists from existing permissions
            ],
            'description' => ['nullable', 'string', 'max:500'],
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
            'name.required' => 'Sila masukkan nama permission.',
            'name.unique' => 'Nama permission ini sudah wujud. Sila gunakan nama lain.',
            'name.regex' => 'Nama permission hanya boleh mengandungi huruf, nombor, ruang, tanda tolak, garis bawah dan titik.',
            'module.required' => 'Sila pilih modul.',
            'module.exists' => 'Modul yang dipilih tidak wujud. Sila pilih modul yang sah.',
            'roles.*.exists' => 'Satu atau lebih peranan yang dipilih tidak sah.',
        ];
    }
}
