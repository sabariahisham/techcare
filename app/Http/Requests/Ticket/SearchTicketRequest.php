<?php

/**
 * Search Ticket Request Validation
 * Validates input data when searching for tickets
 */

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class SearchTicketRequest extends FormRequest
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
            'query' => 'required|string|min:1|max:255',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'query.required' => 'Carian diperlukan.',
            'query.min' => 'Carian mestilah sekurang-kurangnya 1 aksara.',
            'query.max' => 'Carian tidak boleh melebihi 255 aksara.',
        ];
    }
} 
