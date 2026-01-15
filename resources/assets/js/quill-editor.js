/**
 * Quill Rich Text Editor Configuration
 * Used for ticket comments
 */

import Quill from 'quill';
import 'quill/dist/quill.snow.css';

// Make Quill available globally
window.Quill = Quill;

// Initialize Quill editor for comments
window.initCommentEditor = function (selector = '#comment-editor') {
    const editorElement = document.querySelector(selector);
    if (!editorElement) return null;

    const quill = new Quill(selector, {
        theme: 'snow',
        placeholder: 'Masukkan komen anda di sini...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                ['blockquote', 'code-block'],
                ['link'],
                ['clean']
            ]
        }
    });

    return quill;
};
