/**
 * Quill Rich Text Editor Configuration
 * Used for ticket comments
 */

import Quill from 'quill';
import 'quill/dist/quill.snow.css';

// Make Quill available globally
window.Quill = Quill;

/** Malay tooltips for Quill Snow toolbar controls */
const QUILL_TOOLBAR_TOOLTIPS = {
  header: 'Heading',
  bold: 'Bold',
  italic: 'Italic',
  underline: 'Underline',
  strike: 'Strikethrough',
  'list-ordered': 'Numbered List',
  'list-bullet': 'Bullet List',
  color: 'Text Color',
  background: 'Highlight Text',
  blockquote: 'Quote',
  'code-block': 'Code Block',
  link: 'Insert Link',
  image: 'Sisipkan Gambar',
  clean: 'Clear Formatting',
};

const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
const MAX_IMAGE_SIZE = 2 * 1024 * 1024; // 2MB

function notify(type, message) {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        alert(message);
    }
}

/**
 * Prompt for an image file, validate it (JPG/JPEG/PNG/WEBP, max 2MB), upload it to the
 * server, then insert the returned URL into the Quill editor at the cursor position.
 */
function handleImageUpload(quill) {
    const range = quill.getSelection(true);

    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/jpeg,image/jpg,image/png,image/webp');
    input.style.display = 'none';
    document.body.appendChild(input);

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        input.remove();

        if (!file) {
            return;
        }

        if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
            notify('error', 'Format gambar tidak dibenarkan. Hanya JPG, JPEG, PNG dan WEBP dibenarkan.');
            return;
        }

        if (file.size > MAX_IMAGE_SIZE) {
            notify('error', 'Saiz gambar tidak boleh melebihi 2MB.');
            return;
        }

        uploadImage(quill, file, range);
    });

    input.click();
}

function uploadImage(quill, file, range) {
    const ticketUuid = window.ticketData && window.ticketData.uuid;
    if (!ticketUuid) {
        notify('error', 'Tiket tidak ditemui. Sila muat semula halaman.');
        return;
    }

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const toolbarContainer = quill.getModule('toolbar')?.container;
    const imageButton = toolbarContainer ? toolbarContainer.querySelector('.ql-image') : null;

    if (imageButton) {
        imageButton.disabled = true;
    }

    const formData = new FormData();
    formData.append('image', file);

    fetch(`/ticket-comments/${ticketUuid}/upload-image`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : '',
            'Accept': 'application/json',
        },
        body: formData,
    })
        .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                throw new Error(data.message || 'Ralat semasa memuat naik gambar.');
            }

            quill.insertEmbed(range.index, 'image', data.url, 'user');
            quill.setSelection(range.index + 1, 0, 'user');
        })
        .catch((error) => {
            notify('error', error.message || 'Ralat semasa memuat naik gambar.');
        })
        .finally(() => {
            if (imageButton) {
                imageButton.disabled = false;
            }
        });
}

let floatingTipEl = null;

function getFloatingTipElement() {
    if (!floatingTipEl) {
        floatingTipEl = document.createElement('div');
        floatingTipEl.id = 'quill-toolbar-floating-tip';
        floatingTipEl.className = 'quill-toolbar-floating-tip';
        floatingTipEl.setAttribute('role', 'tooltip');
        document.body.appendChild(floatingTipEl);
    }

    return floatingTipEl;
}

function showFloatingTooltip(target) {
    const text = target.getAttribute('data-quill-tip');
    if (!text) {
        return;
    }

    const tip = getFloatingTipElement();
    const rect = target.getBoundingClientRect();

    tip.textContent = text;
    tip.style.display = 'block';
    tip.style.left = `${rect.left + rect.width / 2}px`;
    tip.style.top = `${rect.top}px`;
}

function hideFloatingTooltip() {
    if (floatingTipEl) {
        floatingTipEl.style.display = 'none';
    }
}

function bindFloatingToolbarTooltips(container) {
    container.querySelectorAll('[data-quill-tip]').forEach((element) => {
        if (element.dataset.quillTipBound === '1') {
            return;
        }

        element.dataset.quillTipBound = '1';

        element.addEventListener('mouseenter', () => showFloatingTooltip(element));
        element.addEventListener('mouseleave', hideFloatingTooltip);
        element.addEventListener('focus', () => showFloatingTooltip(element));
        element.addEventListener('blur', hideFloatingTooltip);
    });
}

/**
 * Apply tooltips to Quill toolbar buttons and pickers.
 */
function applyQuillToolbarTooltips(quill) {
    const toolbar = quill.getModule('toolbar');
    if (!toolbar?.container) {
        return;
    }

    const setTooltip = (element, title) => {
        if (!element || !title) {
            return;
        }

        element.setAttribute('data-quill-tip', title);
        element.setAttribute('aria-label', title);
    };

    const container = toolbar.container;

    container.querySelectorAll('button').forEach((button) => {
        let key = null;

        if (button.classList.contains('ql-list')) {
            key = button.getAttribute('value') === 'ordered' ? 'list-ordered' : 'list-bullet';
        } else {
            const qlClass = Array.from(button.classList).find(
                (cls) => cls.startsWith('ql-') && cls !== 'ql-active'
            );
            key = qlClass ? qlClass.replace('ql-', '') : null;
        }

        if (key && QUILL_TOOLBAR_TOOLTIPS[key]) {
            setTooltip(button, QUILL_TOOLBAR_TOOLTIPS[key]);
        }
    });

    container.querySelectorAll('.ql-picker').forEach((picker) => {
        const pickerType = Array.from(picker.classList).find(
            (cls) => cls.startsWith('ql-') && cls !== 'ql-picker' && cls !== 'ql-expanded'
        );

        if (!pickerType) {
            return;
        }

        const key = pickerType.replace('ql-', '');
        const label = picker.querySelector('.ql-picker-label');

        if (label && QUILL_TOOLBAR_TOOLTIPS[key]) {
            setTooltip(label, QUILL_TOOLBAR_TOOLTIPS[key]);
        }
    });

    bindFloatingToolbarTooltips(container);
}

// Initialize Quill editor for comments
window.initCommentEditor = function (selector = '#comment-editor') {
    const editorElement = document.querySelector(selector);
    if (!editorElement) {
        return null;
    }

    const quill = new Quill(selector, {
        theme: 'snow',
        placeholder: 'Masukkan komen anda di sini...',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ color: [] }, { background: [] }],
                ['blockquote', 'code-block'],
                ['link', 'image'],
                ['clean'],
            ],
        },
    });

    quill.getModule('toolbar').addHandler('image', () => handleImageUpload(quill));

    applyQuillToolbarTooltips(quill);

    return quill;
};

window.applyQuillToolbarTooltips = applyQuillToolbarTooltips;
