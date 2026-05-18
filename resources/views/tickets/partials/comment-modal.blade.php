<style>
    /* Floating Quill toolbar tooltip (appended to body — not clipped by modal overflow) */
    .quill-toolbar-floating-tip {
        position: fixed;
        z-index: 2000;
        display: none;
        max-width: 240px;
        padding: 0.35rem 0.6rem;
        font-size: 0.75rem;
        line-height: 1.25;
        white-space: nowrap;
        color: #fff;
        background: rgba(47, 43, 61, 0.95);
        border-radius: 0.25rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        pointer-events: none;
        transform: translate(-50%, calc(-100% - 8px));
    }
</style>

{{-- modal dialog box for comment input and submit button --}}
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true"
    style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="commentModalLabel">Komen</h5>
                    <small class="text-muted" id="commentModalSubtitle">Masukkan komen anda di bawah</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('tickets.add-comment', $ticket->id) }}" id="commentForm">
                    @csrf
                    <div class="mb-3">
                        <div id="comment-editor" style="min-height: 200px; background-color: white;"></div>
                        <input type="hidden" id="comment" name="comment" required>
                    </div>
                    <div class="modal-footer py-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary" id="commentSubmitBtn">Hantar Komen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Modal label configurations
    const commentModalConfig = {
        pengadu: {
            title: 'Komen',
            subtitle: 'Masukkan komen anda di bawah',
            placeholder: 'Masukkan komen anda di sini...',
            submitBtn: 'Hantar Komen',
            emptyAlert: 'Sila masukkan komen anda.'
        },
        pegawai: {
            title: 'Catatan Tindakan Yang Diambil',
            subtitle: 'Masukkan catatan tindakan anda di bawah',
            placeholder: 'Masukkan catatan tindakan anda di sini...',
            submitBtn: 'Hantar Catatan',
            emptyAlert: 'Sila masukkan catatan tindakan anda.'
        }
    };

    let currentModalContext = 'pengadu';

    function openCommentModal(context) {
        currentModalContext = context || 'pengadu';
        const config = commentModalConfig[currentModalContext];

        document.getElementById('commentModalLabel').textContent = config.title;
        document.getElementById('commentModalSubtitle').textContent = config.subtitle;
        document.getElementById('commentSubmitBtn').textContent = config.submitBtn;

        $('#commentModal').modal('show');
    }

    document.addEventListener('DOMContentLoaded', function() {

        let commentQuill = null;

        const commentModal = document.getElementById('commentModal');

        if (commentModal) {

            commentModal.addEventListener('shown.bs.modal', function() {

                if (!commentQuill && typeof window.initCommentEditor === 'function') {
                    commentQuill = window.initCommentEditor('#comment-editor');
                }

                if (commentQuill && typeof window.applyQuillToolbarTooltips === 'function') {
                    window.applyQuillToolbarTooltips(commentQuill);
                }

                // Update placeholder based on context
                const config = commentModalConfig[currentModalContext];
                if (commentQuill) {
                    commentQuill.root.dataset.placeholder = config.placeholder;
                    commentQuill.root.setAttribute('data-placeholder', config.placeholder);
                }
            });

            commentModal.addEventListener('hidden.bs.modal', function() {

                if (commentQuill) {
                    commentQuill.setText('');
                }
            });
        }

        const commentForm = document.getElementById('commentForm');

        if (commentForm) {

            commentForm.addEventListener('submit', function(e) {

                if (commentQuill) {

                    const content = commentQuill.root.innerHTML;
                    const textContent = commentQuill.getText().trim();

                    if (textContent.length === 0) {

                        e.preventDefault();

                        const config = commentModalConfig[currentModalContext];
                        alert(config.emptyAlert);

                        return false;
                    }

                    document.getElementById('comment').value = content;
                }
            });
        }
    });
</script>
