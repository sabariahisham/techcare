<?php

/**
 * Download Attachment Request Validation
 * Validates authorization when downloading a ticket attachment
 */

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;

class DownloadAttachmentRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    $user = Auth::user();

    if (!$user) {
      return false;
    }

    $documentId = $this->route('documentId');
    $document = Document::where('id', $documentId)
      ->where('doc_jenis', 'TICKET')
      ->first();

    if (!$document) {
      return false;
    }

    // Get the associated ticket
    $ticket = \App\Models\Ticket::find($document->doc_id);
    if (!$ticket) {
      return false;
    }

    // Allow download if user is admin, helpdesk, ticket owner, assignee, or attachment creator
    return $user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty']) ||
      $ticket->user_id == $user->id ||
      $ticket->pegtugas_id == $user->id ||
      $document->created_by == $user->id;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
   */
  public function rules(): array
  {
    return [
      // No additional validation needed since we validate the document in authorize()
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
      //
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
    throw new \Illuminate\Auth\Access\AuthorizationException('Anda tidak mempunyai kebenaran untuk memuat turun lampiran ini.');
  }
}
