<?php

/**
 * Assign Ticket Request Validation
 * Validates input data when assigning a ticket to a user
 */

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
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
            'assignee_id' => 'required|integer|exists:users,id',
            'ticket_id' => 'required|integer|exists:tickets,id',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'assignee_id.required' => 'ID Penugasan diperlukan.',
            'ticket_id.required' => 'ID Tiket diperlukan.',
            'assignee_id.exists' => 'Pengguna yang dipilih tidak wujud.',
        ];

