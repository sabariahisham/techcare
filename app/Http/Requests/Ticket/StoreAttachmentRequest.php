<?php

/**
 * Store Attachment Request Validation
 * Validates input data when storing ticket attachments
 */

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreAttachmentRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    // Check if user is authenticated and has permission to upload attachments
    $user = Auth::user();

    if (!$user) {
      return false;
    }

    // Additional authorization: Check if user can access the ticket
    $ticketId = $this->input('ticket_id');
    if ($ticketId) {
      $ticket = \App\Models\Ticket::find($ticketId);
      if (!$ticket) {
        return false;
      }

      // Check if user is admin, helpdesk, or ticket owner/assignee
      return $user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty']) ||
        $ticket->user_id == $user->id ||
        $ticket->pegtugas_id == $user->id;
    }

    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    return [
      'ticket_id' => 'required|exists:tickets,id',
      'attachments' => 'required|array|min:1|max:10', // Allow 1-10 files
      'attachments.*' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif'
    ];
  }

  /**
   * Get the error messages for the defined validation rules.
   *
   * @return array<string, string>
   */
  public function messages(): array
  {
    return [
      'ticket_id.required' => 'ID tiket adalah wajib.',
      'ticket_id.exists' => 'Tiket yang dipilih tidak wujud.',
      'attachments.required' => 'Sekurang-kurangnya satu fail mesti dimuat naik.',
      'attachments.array' => 'Format lampiran tidak sah.',
      'attachments.min' => 'Sekurang-kurangnya satu fail mesti dimuat naik.',
      'attachments.max' => 'Maksimum 10 fail boleh dimuat naik pada satu masa.',
      'attachments.*.required' => 'Fail lampiran adalah wajib.',
      'attachments.*.file' => 'Lampiran mesti berupa fail.',
      'attachments.*.max' => 'Setiap fail tidak boleh melebihi 10MB.',
      'attachments.*.mimes' => 'Jenis fail yang dibenarkan: PDF, Word, Excel, PowerPoint, Text, dan gambar (JPG, PNG, GIF).'
    ];
  }

  /**
   * Handle a failed authorization attempt.
   *
   * @return void
   *
   * @throws \Illuminate\Auth\Access\AuthorizationException
   */
  protected function failedAuthorization()
  {
    throw new \Illuminate\Auth\Access\AuthorizationException('Anda tidak mempunyai kebenaran untuk memuat naik lampiran ke tiket ini.');
  }
}
