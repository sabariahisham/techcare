<?php

/**
 * Store Ticket Comment Request Validation
 * Validates input data when storing a ticket comment
 */

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
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
            'comment' => 'required|string|min:1|max:1000',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'comment.required' => 'Komen diperlukan.',
            'comment.min' => 'Komen mestilah sekurang-kurangnya 1 aksara.',
            'comment.max' => 'Komen tidak boleh melebihi 1000 aksara.',
        ];
    }
}
