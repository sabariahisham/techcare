<div class="col-xl-4 col-lg-6 col-md-12 d-flex align-items-stretch dashboard-main-cards chart-card">
    <div class="card flex-fill" style="height: 332px;">
        <div class="card-header" style="padding: 0.75rem 1.25rem;">
            <h5 class="card-title mb-0" style="font-size: 1rem;">Kategori Tiket</h5>
            <p class="card-subtitle mb-0" style="font-size: 0.75rem;">Sehingga {{ date('F Y') }} <small
                    class="text-muted"><i>(tahun semasa)</i></small></p>
            <small class="text-muted" style="font-size: 0.7rem;">
                ⓘ Klik kategori untuk paparan terperinci
            </small>
        </div>
        <div class="card-body" style="padding: 0.75rem 1rem;">
            <div style="height: 230px;">
                <canvas id="categoryChart" style="cursor: pointer;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Subcategory Drill-down -->
<div class="modal fade" id="categoryDrilldownModal" tabindex="-1" aria-labelledby="categoryDrilldownModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryDrilldownModalLabel">
                    <i class="ti ti-list-details me-2"></i>
                    <span id="categoryDrilldownTitle">Sub Kategori</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-striped small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start" style="width: 5%;">Bil.</th>
                                <th class="text-start" style="width: 70%;">Sub Kategori</th>
                                <th class="text-center" style="width: 25%;">Tiket</th>
                            </tr>
                        </thead>
                        <tbody id="categoryDrilldownTableBody">
                            <!-- Data will be populated dynamically -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Jumlah Keseluruhan</th>
                                <th class="text-center" id="categoryDrilldownTotal">0</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
