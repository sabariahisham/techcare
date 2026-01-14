{{-- modal dialog box for comment input and submit button --}}
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="knowledgeBaseModalLabel">Komen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('tickets.add-comment', $ticket->id) }}" id="commentForm">
                    @csrf
                    <div class="mb-3">
                        <label for="comment-editor" class="form-label">Masukkan Komen Anda</label>
                        <div id="comment-editor" style="min-height: 200px; background-color: white;"></div>
                        <input type="hidden" id="comment" name="comment" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Hantar Komen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let commentQuill = null;

        // Initialize Quill when modal is shown
        const commentModal = document.getElementById('commentModal');
        if (commentModal) {
            commentModal.addEventListener('shown.bs.modal', function() {
                if (!commentQuill && typeof window.initCommentEditor === 'function') {
                    commentQuill = window.initCommentEditor('#comment-editor');
                }
            });

            // Reset editor when modal is hidden
            commentModal.addEventListener('hidden.bs.modal', function() {
                if (commentQuill) {
                    commentQuill.setText('');
                }
            });
        }

        // Handle form submission
        const commentForm = document.getElementById('commentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                if (commentQuill) {
                    const content = commentQuill.root.innerHTML;
                    const textContent = commentQuill.getText().trim();

                    // Check if there's actual content
                    if (textContent.length === 0) {
                        e.preventDefault();
                        alert('Sila masukkan komen anda.');
                        return false;
                    }

                    // Set the hidden input value
                    document.getElementById('comment').value = content;
                }
            });
        }
    });
</script>
