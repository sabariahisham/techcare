<?php

/**
 * Assign Ticket Request Validation
 * Validates input data when updating a ticket's SLA
 */

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSLARequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'sla_id' => 'required|integer|exists:slas,id',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'sla_id.required' => 'SLA diperlukan.',
            'sla_id.integer' => 'SLA mesti nombor bulat yang sah.',
            'sla_id.exists' => 'SLA yang dipilih tidak wujud.',
        ];
    }
} 
