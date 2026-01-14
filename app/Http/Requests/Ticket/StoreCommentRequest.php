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
   * Preserves plain text formatting including line breaks, numbers, and symbols
   */

  public function prepareForValidation()
  {
    if ($this->has('comment')) {
      $comment = $this->input('comment');

      // If comment doesn't contain HTML tags, convert plain text to HTML
      if (strip_tags($comment) === $comment) {
        // Simply convert line breaks to <br> tags
        // This preserves numbers (1., 2., 3.) and symbols (-, •) exactly as typed
        $comment = nl2br($comment, false);
      }

      // Configure Purifier to allow safe HTML tags for formatting
      $config = [
        'HTML.Allowed' => 'p,br,strong,em,u,ol,ul,li,h1,h2,h3,h4,h5,h6,blockquote,pre,code,a[href|target],span[style],div',
        'CSS.AllowedProperties' => 'font-weight,font-style,text-decoration,color,background-color',
        'AutoFormat.AutoParagraph' => false,
        'AutoFormat.RemoveEmpty' => true,
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
