'use strict';

$(function () {

    /* ===============================
       SELECT2 (BULAN)
    =============================== */
    $('#filter-month').select2({
        placeholder: 'Semua bulan',
        allowClear: true,
        width: '100%'
    });

    /* ===============================
       LABEL HEADER
    =============================== */
    function updateHeaderLabel() {
        $('#label-year').text($('#filter-year').val());

        let months = $('#filter-month').select2('data');
        if (months.length === 0) {
            $('#label-month').text('Semua');
        } else {
            $('#label-month').text(months.map(m => m.text).join(', '));
        }
    }

    updateHeaderLabel();

    /* ===============================
       DATATABLE
    =============================== */
    let table = $('#laporan-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        searching: false,
        ordering: true,
        paging: false,
        info: false,
        dom: '<"row mb-3"<"col-12 text-end"B>>t',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bx bx-file me-1"></i> Excel',
                className: 'btn btn-success btn-sm me-2',
                title: 'Laporan Aduan Mengikut Status',
                footer: true,
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bx bx-file-pdf me-1"></i> PDF',
                className: 'btn btn-danger btn-sm me-2',
                title: 'Laporan Aduan Mengikut Status',
                footer: true,
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: ':visible'
                },
                customize: function(doc) {
                    // Center align all cells
                    doc.content[1].table.body.forEach(function(row) {
                        row.forEach(function(cell, index) {
                            // First column (kategori) left align, others center
                            if (index === 0) {
                                cell.alignment = 'left';
                            } else {
                                cell.alignment = 'center';
                            }
                        });
                    });
                    
                    // Style the footer row
                    var rowCount = doc.content[1].table.body.length;
                    var lastRow = doc.content[1].table.body[rowCount - 1];
                    lastRow.forEach(function(cell) {
                        cell.fillColor = '#28a745';
                        cell.color = '#ffffff';
                        cell.bold = true;
                        cell.alignment = 'center';
                    });
                }
            },
            {
                extend: 'print',
                text: '<i class="bx bx-printer me-1"></i> Print',
                className: 'btn btn-primary btn-sm',
                title: 'Laporan Aduan Mengikut Status',
                footer: true,
                exportOptions: {
                    columns: ':visible'
                },
                customize: function(win) {
                    $(win.document.body).find('tfoot tr')
                        .css('background-color', '#28a745')
                        .css('color', '#ffffff')
                        .css('font-weight', 'bold');
                }
            }
        ],

        ajax: {
            url: '/report/laporan-status-aduan-tahunan/data',
            type: 'POST',
            data: function (d) {
                d.year   = $('#filter-year').val();
                d.months = $('#filter-month').val() || [];
                d._token = $('meta[name="csrf-token"]').attr('content');
            },
            error: function (xhr, error) {
                console.error('AJAX Error:', error, xhr.responseText);
            }
        },

        columns: [
            { data: 'kategori', name: 'kategori' },

            renderNumberColumn('status_1'),
            renderNumberColumn('status_2'),
            renderNumberColumn('status_3'),
            renderNumberColumn('status_4'),
            renderNumberColumn('status_5'),
            renderNumberColumn('status_6'),

            {
                data: 'jumlah',
                className: 'fw-bold text-center text-primary'
            }
        ],

        language: {
            processing: '<div class="spinner-border text-primary"></div>',
            emptyTable: 'Tiada data tersedia',
            zeroRecords: 'Tiada rekod dijumpai'
        },

        footerCallback: function (row, data) {
            let api = this.api();

            let intVal = function (i) {
                return typeof i === 'string'
                    ? parseInt(i.replace(/[^\d]/g, '')) || 0
                    : (typeof i === 'number' ? i : 0);
            };

            // Kolum 1 hingga 7 = status + jumlah
            for (let col = 1; col <= 7; col++) {
                let total = api.column(col).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                $(api.column(col).footer()).html(total);
            }
        }
    });

    /* ===============================
       FILTER CHANGE
    =============================== */
    $('#filter-year, #filter-month').on('change', function () {
        updateHeaderLabel();
        table.ajax.reload();
    });

    /* ===============================
       HELPER RENDER
    =============================== */
    function renderNumberColumn(columnName) {
        return {
            data: columnName,
            className: 'text-center',
            render: function (data) {
                return data == 0
                    ? '<span class="text-muted">0</span>'
                    : data;
            }
        };
    }

});
