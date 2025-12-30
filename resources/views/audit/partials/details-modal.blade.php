<!-- Detail Modal -->
<div class="modal fade" id="auditDetailModal" tabindex="-1" aria-labelledby="auditDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"> <!-- Changed from modal-xl -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="auditDetailModalLabel">Maklumat Audit Trail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                <div class="row g-4">
                    <!-- Basic Info -->
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Info Asas</h6>
                        <table class="table table-sm table-borderless">
                            <tbody id="basicInfo"></tbody>
                        </table>
                    </div>

                    <!-- Technical Info -->
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Info Teknikal</h6>
                        <table class="table table-sm table-borderless">
                            <tbody id="technicalInfo"></tbody>
                        </table>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-4 audit-values-wrapper">
                    <!-- OLD VALUES -->
                    <div class="col-md-6 col-12" id="oldValuesSection">
                        <h6 class="fw-bold mb-3 text-danger">
                            <i class="bx bx-minus-circle me-2"></i>Value Sebelum
                        </h6>
                        <div id="oldValuesContent" class="audit-card p-3">
                        </div>
                    </div>

                    <!-- NEW VALUES -->
                    <div class="col-md-6 col-12" id="newValuesSection">
                        <h6 class="fw-bold mb-3 text-success">
                            <i class="bx bx-plus-circle me-2"></i>Value Selepas
                        </h6>
                        <div id="newValuesContent" class="audit-card p-3">
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
<style> 


/* Smaller, cleaner modal */
#auditDetailModal .modal-dialog {
    max-width: 820px; /* Smaller than modal-xl */
}

#auditDetailModal .modal-body {
    max-height: 75vh;
    overflow-y: auto;
}

/* Card style for value boxes */
.audit-card {
    background: #f8f9fa;
    border: 1px solid #e4e6eb;
    border-radius: .5rem;
    min-height: 120px;
}

/* Table styling */
#auditDetailModal .table td {
    vertical-align: top;
    word-break: break-word;
}

/* PRE formatting for long text */
#auditDetailModal pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 200px;
    overflow-y: auto;
    font-size: 0.85rem;
}

/* Auto-stack old/new values when screen smaller */
@media (max-width: 992px) {
    .audit-values-wrapper .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

/* Additional beautify for labels + values */
.audit-field-name {
    background-color: #eef1f6;
    font-weight: 600;
    border-right: 2px solid #dee2e6;
    padding: 6px;
}

.audit-value-cell {
    max-width: 280px;
    overflow: hidden;
}

</style>