<?php

/**
 * Store Ticket Comment Request Validation
 * Validates input data when storing a ticket comment
 */

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Mews\Purifier\Facades\Purifier;

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
   * Prepare the data for validation.
   * Cleans the 'comment' input to prevent XSS attacks.
   * Accepts rich text HTML from Quill editor
   */

  public function prepareForValidation()
  {
    if ($this->has('comment')) {
      $comment = $this->input('comment');

      // Configure Purifier to allow safe HTML tags from Quill editor
      $config = [
        'HTML.Allowed' => 'p,br,strong,em,u,s,ol,ul,li,h1,h2,h3,h4,h5,h6,blockquote,pre,code,a[href|target],span[style],div',
        'CSS.AllowedProperties' => 'font-weight,font-style,text-decoration,color,background-color',
        'AutoFormat.AutoParagraph' => false,
        'AutoFormat.RemoveEmpty' => false,
      ];

      $cleanedComment = Purifier::clean($comment, $config);
      $this->merge([
        'comment' => $cleanedComment,
      ]);
    }
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      'comment' => 'required|string|min:1|max:10000',
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
      'comment.max' => 'Komen tidak boleh melebihi 10000 aksara.',
    ];
  }
}
