<?php

/**
 * Delete Attachment Request Validation
 * Validates input data and authorization when deleting a ticket attachment
 */

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class DeleteAttachmentRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    $user = Auth::user();
    $document = $this->route('document');

    if (!$user || !$document) {
      return false;
    }

    // Check if it's a ticket attachment
    if ($document->doc_jenis !== 'TICKET') {
      return false;
    }

    // Allow deletion if user is admin, helpdesk, or the creator of the attachment
    return $user->hasAnyRole(['Administrator', 'Pentadbir Sistem', 'Penyelia Helpdesk', 'Helpdesk On Duty']) ||
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
      // No additional validation needed since we're using route model binding
      // The Document model will be automatically validated by Laravel
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
    $document = $this->route('document');

    if ($document && $document->doc_jenis !== 'TICKET') {
      throw new \Illuminate\Auth\Access\AuthorizationException('Lampiran ini bukan lampiran tiket.');
    }

    throw new \Illuminate\Auth\Access\AuthorizationException('Anda tidak mempunyai kebenaran untuk memadam lampiran ini.');
  }
}
