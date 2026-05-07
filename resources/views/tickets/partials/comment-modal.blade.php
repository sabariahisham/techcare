{{-- modal dialog box for comment input and submit button --}}
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true"
    style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="commentModalLabel">Komen</h5>
                    <small class="text-muted">Masukkan komen anda di bawah</small>
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

                    // Auto capitalize
                    commentQuill.on('text-change', function(delta, oldDelta, source) {

                        // Only for user typing
                        if (source !== 'user') {
                            return;
                        }

                        const text = commentQuill.getText();
                        const range = commentQuill.getSelection();

                        if (!range) {
                            return;
                        }

                        // Capitalize first letter
                        if (range.index === 1) {

                            const firstChar = text.charAt(0);

                            if (firstChar >= 'a' && firstChar <= 'z') {

                                commentQuill.deleteText(0, 1);
                                commentQuill.insertText(0, firstChar.toUpperCase());

                                commentQuill.setSelection(range.index, 0);
                            }
                        }

                        // Capitalize after . ! ?
                        const prevText = text.substring(0, range.index);

                        const match = prevText.match(/([.!?]\s+)([a-z])$/);

                        if (match) {

                            const letterIndex = range.index - 1;
                            const letter = text.charAt(letterIndex);

                            commentQuill.deleteText(letterIndex, 1);
                            commentQuill.insertText(letterIndex, letter.toUpperCase());

                            commentQuill.setSelection(range.index, 0);
                        }
                    });
                }
            });

            // Reset editor when modal closed
            commentModal.addEventListener('hidden.bs.modal', function() {

                if (commentQuill) {
                    commentQuill.setText('');
                }
            });
        }

        // Handle form submit
        const commentForm = document.getElementById('commentForm');

        if (commentForm) {

            commentForm.addEventListener('submit', function(e) {

                if (commentQuill) {

                    const content = commentQuill.root.innerHTML;
                    const textContent = commentQuill.getText().trim();

                    if (textContent.length === 0) {

                        e.preventDefault();

                        alert('Sila masukkan komen anda.');

                        return false;
                    }

                    document.getElementById('comment').value = content;
                }
            });
        }
    });
</script>
