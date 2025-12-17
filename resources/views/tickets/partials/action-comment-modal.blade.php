{{-- modal dialog box for action comment datatable --}}
<div class="modal fade" id="actionComment" tabindex="-1" aria-labelledby="actionCommentLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionCommentLabel">
                    <i class="bx bx-message-dots me-2"></i>Catatan Tindakan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="actionCommentContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Memuat catatan tindakan...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom styles for responsive action comments modal */
    #actionComment .modal-dialog {
        max-width: 700px;
        width: 90%;
        margin: 1rem auto;
    }

    #actionComment .modal-content {
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        border-radius: 12px;
        overflow: hidden;
    }

    #actionComment .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
        padding: 1rem 1.5rem;
    }

    #actionComment .modal-header .btn-close {
        color: white;
        opacity: 0.8;
        filter: none;
    }

    #actionComment .modal-header .btn-close:hover {
        opacity: 1;
    }

    #actionComment .modal-body {
        padding: 0;
        max-height: 60vh;
        overflow-y: auto;
    }

    #actionComment .modal-footer {
        padding: 1rem 1.5rem;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }

    .action-comments-container {
        padding: 1rem;
    }

    .action-comments-container::-webkit-scrollbar {
        width: 6px;
    }

    .action-comments-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .action-comments-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .action-comments-container::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .comment-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }

    .comment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .comment-card.pengadu-comment {
        border-left-color: #17a2b8;
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.05), rgba(23, 162, 184, 0.01));
    }

    .comment-card.officer-comment {
        border-left-color: #007bff;
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.05), rgba(0, 123, 255, 0.01));
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        text-transform: uppercase;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .user-avatar.officer {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .comment-meta {
        background: rgba(0, 0, 0, 0.02);
        border-radius: 8px;
        padding: 8px 12px;
        margin-top: 10px;
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.6rem;
        border-radius: 12px;
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    /* Mobile responsive styles */
    @media (max-width: 768px) {
        #actionComment .modal-dialog {
            max-width: 95%;
            width: 95%;
            margin: 0.5rem auto;
        }

        #actionComment .modal-body {
            max-height: 50vh;
        }

        #actionComment .modal-header {
            padding: 0.75rem 1rem;
        }

        #actionComment .modal-footer {
            padding: 0.75rem 1rem;
        }

        .action-comments-container {
            padding: 0.75rem;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            font-size: 0.9rem;
        }

        .comment-text {
            font-size: 0.85rem;
        }

        .comment-card .card-body {
            padding: 0.75rem;
        }
    }

    @media (max-width: 576px) {
        #actionComment .modal-dialog {
            max-width: 98%;
            width: 98%;
            margin: 0.25rem auto;
        }

        #actionComment .modal-body {
            max-height: 45vh;
        }

        .action-comments-container {
            padding: 0.5rem;
        }

        .comment-card .card-body {
            padding: 0.5rem;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            font-size: 0.8rem;
        }

        .comment-text {
            font-size: 0.8rem;
        }

        #actionComment .modal-header h5 {
            font-size: 1rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize action comments modal functionality
        const actionCommentModal = document.getElementById('actionComment');

        if (actionCommentModal) {
            actionCommentModal.addEventListener('shown.bs.modal', function() {
                loadActionComments();
            });
        }

        // Load action comments when modal is shown
        function loadActionComments() {
            const ticketId = window.ticketData?.id || null;
            const contentDiv = document.getElementById('actionCommentContent');
            if (!ticketId) {
                contentDiv.innerHTML = '<div class="alert alert-warning">ID tiket tidak ditemui.</div>';
                return;
            }

            // Show loading state
            contentDiv.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Memuat catatan tindakan...</p>
            </div>
        `;

            // Fetch comments from server
            fetch('/ticket-assignments/' + ticketId + '/history-comments', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                            'content') || ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayActionComments(data.comments);
                    } else {
                        contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bx bx-error-circle me-2"></i>
                            ${data.message || 'Ralat memuat catatan tindakan'}
                        </div>
                    `;
                    }
                })
                .catch(error => {
                    console.error('Error loading comments:', error);
                    contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bx bx-error-circle me-2"></i>
                        Ralat sambungan semasa memuat catatan tindakan
                    </div>
                `;
                });
        }

        function displayActionComments(comments) {
            const contentDiv = document.getElementById('actionCommentContent');

            if (!comments || comments.length === 0) {
                contentDiv.innerHTML = `
                <div class="text-center py-5">
                    <i class="bx bx-message-alt-x display-1 text-muted mb-3"></i>
                    <h6 class="text-muted mb-2">Belum Ada Catatan Tindakan</h6>
                    <p class="text-muted small mb-0">Catatan tindakan akan dipaparkan di sini apabila terdapat komunikasi.</p>
                </div>
            `;
                return;
            }

            let commentsHTML = '<div class="action-comments-container p-3">';

            comments.forEach((comment, index) => {
                const userTypeClass = comment.user_type === 'Pengadu' ? 'pengadu-comment' :
                    'officer-comment';
                const avatarClass = comment.user_type === 'Pengadu' ? '' : 'officer';
                const userInitials = comment.user_name ? comment.user_name.substring(0, 2) : 'NA';
                const statusBadge = getStatusBadge(comment.ticket_status, comment.status_class);

                commentsHTML += `
                <div class="comment-card ${userTypeClass} card mb-3 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-auto">
                                <div class="user-avatar ${avatarClass}">
                                    ${userInitials}
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1 fw-semibold">${comment.user_name}</h6>
                                        <small class="text-muted">
                                            <i class="bx bx-building-house me-1"></i>${comment.user_unit}
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        ${getUserTypeBadge(comment.user_type)}
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <i class="bx bx-time me-1"></i>${comment.created_at}
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="comment-text mb-3">
                                    <p class="mb-0 text-dark">${comment.comment || '<em class="text-muted">Tiada catatan tersedia</em>'}</p>
                                </div>

                                <div class="comment-meta">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <small class="text-muted fw-medium">
                                                <i class="bx bx-flag me-1"></i>Status Semasa:
                                            </small>
                                            ${statusBadge}
                                        </div>
                                        ${comment.created_by && comment.created_by !== comment.user_name ?
                                            `<div class="col-md-6 text-md-end mt-2 mt-md-0">
                                                    <small class="text-muted">
                                                        <i class="bx bx-user-plus me-1"></i>Dicipta oleh: <strong>${comment.created_by}</strong>
                                                    </small>
                                                </div>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            });

            commentsHTML += '</div>';
            contentDiv.innerHTML = commentsHTML;
        }

        function getStatusBadge(statusName, statusClass) {
            return `<span class="badge status-badge ${statusClass}">${statusName}</span>`;
        }

        function getUserTypeBadge(userType) {
            switch (userType) {
                case 'Pengadu':
                    return '<span class="badge bg-info"><i class="bx bx-user me-1"></i>Pengadu</span>';
                case 'Pegawai Tugasan':
                    return '<span class="badge bg-primary"><i class="bx bx-user-check me-1"></i>Pegawai Tugasan</span>';
                default:
                    return '<span class="badge bg-secondary"><i class="bx bx-user-circle me-1"></i>' +
                        userType + '</span>';
            }
        }
    });
</script>
