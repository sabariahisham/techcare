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
  clean: 'Clear Formatting',
};

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
                ['link'],
                ['clean'],
            ],
        },
    });

    applyQuillToolbarTooltips(quill);

    return quill;
};

window.applyQuillToolbarTooltips = applyQuillToolbarTooltips;
