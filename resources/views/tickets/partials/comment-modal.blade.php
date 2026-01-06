{{-- modal dialog box for comment input and submit button --}}
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="knowledgeBaseModalLabel">Komen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('tickets.add-comment', $ticket->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="comment" class="form-label">Masukkan Komen Anda</label>
                        <textarea class="form-control" id="comment" name="comment" rows="5" required></textarea>
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
