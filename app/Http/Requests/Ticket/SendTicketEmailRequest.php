<?php

/**
 * Send Ticket Email Request Validation
 * Validates input data when sending a ticket email
 */

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class SendTicketEmailRequest extends FormRequest
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
            'email' => 'required|email|max:255',
            'subject' => 'required|string|min:1|max:255',
            'message' => 'required|string|min:1|max:5000',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Emel diperlukan.',
            'email.email' => 'Sila masukkan alamat emel yang sah.',
            'email.max' => 'Emel tidak boleh melebihi 255 aksara.',
            'subject.required' => 'Subjek diperlukan.',
            'subject.min' => 'Subjek mestilah sekurang-kurangnya 1 aksara.',
            'subject.max' => 'Subjek tidak boleh melebihi 255 aksara.',
            'message.required' => 'Mesej diperlukan.',
            'message.min' => 'Mesej mestilah sekurang-kurangnya 1 aksara.',
            'message.max' => 'Mesej tidak boleh melebihi 5000 aksara.',
        ];
    }
}
