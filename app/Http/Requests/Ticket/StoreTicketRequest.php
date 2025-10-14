<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
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
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'ticket_type' => 'required|in:0,1',
      'name' => 'required_if:ticket_type,1|nullable|string|max:255',
      'email' => 'required_if:ticket_type,1|nullable|email|max:255',
      'other_name' => 'required_if:ticket_type,0|nullable|string|max:255',
      'other_email' => 'required_if:ticket_type,0|nullable|email|max:255',
      'other_sektor' => 'nullable|string|max:255',
      'other_agency' => 'nullable|string|max:255',
      'other_seksyen' => 'nullable|string|max:255',
      'other_unit' => 'nullable|string|max:255',
      'tick_title' => 'required|string|max:255',
      'cat_id' => 'required|exists:ref_categories,id',
      'subcat_id' => 'required|exists:ref_sub_categories,id',
      'tick_desc' => 'required|string|max:5000',
      'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
    ];
  }

  /**
   * Get custom attributes for validator errors.
   */
  public function attributes(): array
  {
    return [
      'ticket_type' => 'jenis aduan',
      'name' => 'nama pengadu',
      'email' => 'emel',
      'other_name' => 'nama pengadu (bagi pihak)',
      'other_email' => 'emel (bagi pihak)',
      'other_sektor' => 'sektor (bagi pihak)',
      'other_agency' => 'bahagian (bagi pihak)',
      'other_seksyen' => 'seksyen (bagi pihak)',
      'other_unit' => 'unit (bagi pihak)',
      'tick_title' => 'tajuk',
      'cat_id' => 'kategori',
      'subcat_id' => 'sub kategori',
      'tick_desc' => 'keterangan',
      'attachments.*' => 'lampiran',
    ];
  }

  /**
   * Get the error messages for the defined validation rules.
   */
  public function messages(): array
  {
    return [
      'ticket_type.required' => 'Sila pilih jenis aduan.',
      'ticket_type.in' => 'Jenis aduan tidak sah.',
      'name.required_if' => 'Nama pengadu diperlukan untuk aduan diri sendiri.',
      'name.string' => 'Nama pengadu mestilah dalam format teks.',
      'name.max' => 'Nama pengadu tidak boleh melebihi 255 aksara.',
      'email.required_if' => 'Emel diperlukan untuk aduan diri sendiri.',
      'email.email' => 'Format emel tidak sah.',
      'email.max' => 'Emel tidak boleh melebihi 255 aksara.',
      'other_name.required_if' => 'Nama pengadu diperlukan untuk aduan bagi pihak.',
      'other_name.string' => 'Nama pengadu (bagi pihak) mestilah dalam format teks.',
      'other_name.max' => 'Nama pengadu (bagi pihak) tidak boleh melebihi 255 aksara.',
      'other_email.required_if' => 'Emel diperlukan untuk aduan bagi pihak.',
      'other_email.email' => 'Format emel (bagi pihak) tidak sah.',
      'other_email.max' => 'Emel (bagi pihak) tidak boleh melebihi 255 aksara.',
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
      'attachments.*.file' => 'Lampiran mestilah fail yang sah.',
      'attachments.*.mimes' => 'Lampiran mestilah dalam format PDF, DOC, DOCX, JPG, JPEG, atau PNG.',
      'attachments.*.max' => 'Saiz lampiran tidak boleh melebihi 2MB.',
    ];
  }
}
