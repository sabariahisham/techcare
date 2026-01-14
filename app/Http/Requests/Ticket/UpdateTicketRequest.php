<?php

/**
 * Update Ticket Request Validation
 * Validates input data when updating a ticket
 */

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
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
   * Only validate fields that can be updated: tajuk, kategori, sub kategori, keterangan, lampiran
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      // Only allow updating these fields
      'tick_title' => 'required|string|max:255',
      'cat_id' => 'required|exists:ref_categories,id',
      'subcat_id' => 'required|exists:ref_sub_categories,id',
      'tick_desc' => 'required|string|max:5000',
      'no_siri' => 'nullable|string|max:255',
      'escalate_to' => 'nullable|exists:users,id', // For manual officer assignment by authorized users
      'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',

      // These fields should NOT be editable, but we accept them to avoid errors
      // They will be ignored in the controller
      'ticket_type' => 'sometimes',
      'name' => 'sometimes',
      'email' => 'sometimes',
      'other_name' => 'sometimes',
      'other_email' => 'sometimes',
      'other_sektor' => 'sometimes',
      'other_agency' => 'sometimes',
      'other_seksyen' => 'sometimes',
      'other_unit' => 'sometimes',
    ];
  }

  /**
   * Get custom attributes for validator errors.
   * Note: Some attributes kept for backward compatibility even though fields are not editable
   */
  public function attributes(): array
  {
    return [
      'tick_title' => 'tajuk',
      'cat_id' => 'kategori',
      'subcat_id' => 'sub kategori',
      'tick_desc' => 'keterangan',
      'no_siri' => 'no siri',
      'escalate_to' => 'pegawai tugasan',
      'attachments.*' => 'lampiran',
    ];
  }

  /**
   * Get custom validation messages.
   */
  public function messages(): array
  {
    return [
      'tick_title.required' => 'Tajuk aduan diperlukan.',
      'tick_title.string' => 'Tajuk mestilah dalam format teks.',
      'tick_title.max' => 'Tajuk tidak boleh melebihi 255 aksara.',
      'cat_id.required' => 'Sila pilih kategori.',
      'cat_id.exists' => 'Kategori yang dipilih tidak wujud.',
      'subcat_id.required' => 'Sila pilih sub kategori.',
      'subcat_id.exists' => 'Sub kategori yang dipilih tidak wujud.',
      'tick_desc.required' => 'Keterangan aduan diperlukan.',
      'tick_desc.string' => 'Keterangan mestilah dalam format teks.',
      'tick_desc.max' => 'Keterangan tidak boleh melebihi 5000 aksara.',
      'escalate_to.exists' => 'Pegawai tugasan yang dipilih tidak wujud.',
      'no_siri.max' => 'Nombor siri tidak boleh melebihi 255 aksara.',
      'attachments.*.file' => 'Lampiran mestilah fail yang sah.',
      'attachments.*.mimes' => 'Lampiran mestilah dalam format PDF, DOC, DOCX, JPG, JPEG, atau PNG.',
      'attachments.*.max' => 'Saiz lampiran tidak boleh melebihi 2MB.',
    ];
  }
}
