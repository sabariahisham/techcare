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

      // Configure Purifier to allow safe HTML from Quill (inline color/background often land on strong/em/span, not only span)
      $config = [
        'HTML.Allowed' =>
          'p[class|style],br,' .
          'strong[class|style],em[class|style],u[class|style],ins[class|style],s[class|style],b[class|style],i[class|style],' .
          'ol,ul,li[class|style],' .
          'h1[class|style],h2[class|style],h3[class|style],h4[class|style],h5[class|style],h6[class|style],' .
          'blockquote[class|style],pre[class|style],code[class|style],' .
          'a[href|target|rel|class|style],span[class|style],div[class|style]',
        'CSS.AllowedProperties' =>
          'font-weight,font-style,text-decoration,text-decoration-line,text-decoration-style,text-decoration-color,text-align,color,background-color,white-space',
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
