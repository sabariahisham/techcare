'use strict';

$(function () {

    // Initialize ringkasan table as DataTable
    let ringkasanTable = $('#table-ringkasan').DataTable({
        paging: true,
        searching: false,
        info: false,
        ordering: true,
        dom: "<'row mb-2'<'col-md-6'><'col-md-6 text-end'B>>tr",
        buttons: [
            {
                extend: 'excel',
                title: 'Ringkasan Rekod Laporan Aduan Sistem TechCare',
                filename: 'Ringkasan_Rekod_Laporan_' + new Date().getTime()
            },
            {
                extend: 'pdf',
                title: 'Ringkasan Rekod Laporan Aduan Sistem TechCare',
                filename: 'Ringkasan_Rekod_Laporan_' + new Date().getTime(),
                orientation: 'portrait',
                pageSize: 'A4',
                customize: function (doc) {
                    doc.content[1].table.widths = ['*', 'auto'];
                    // doc.content[1].alignment = 'center';
                }
            },
            {
                extend: 'print',
                title: 'Ringkasan Rekod Laporan Aduan Sistem TechCare'
            }
        ]
    });

    function loadRingkasan() {
        $.get('/report/rekod-laporan/ringkasan', {
            from: $('#filter-from').val(),
            to: $('#filter-to').val(),
            category: $('#filter-category').val()
        }, function (res) {

            $('#kpi-jumlah').text(res.kpi.jumlah);
            $('#kpi-purata').text(res.kpi.purata);
            $('#kpi-purata-jam').text(res.kpi.purata_jam);

            // Clear and update table data
            ringkasanTable.clear();
            
            res.table.forEach(r => {
                ringkasanTable.row.add([
                    r.kategori,
                    r.selesai
                ]);
            });
            
            ringkasanTable.draw();
        });
    }

    let perincianTable = $('#table-perincian').DataTable({
        processing: true,
        serverSide: true,
        responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal({
                    header: function (row) {
                        var data = row.data();
                        return 'Butiran untuk ' + data.ticket_nolog;
                    }
                }),
                type: ''
            }
        },
        columnDefs: [
            { responsivePriority: 1, targets: 0 },
            { responsivePriority: 2, targets: 2 },
            { responsivePriority: 3, targets: 6 },
            { responsivePriority: 4, targets: 1 },
            { responsivePriority: 5, targets: 4 }
        ],
        ajax: {
            url: '/report/rekod-laporan/perincian',
            data: function (d) {
                d.from = $('#filter-from').val();
                d.to   = $('#filter-to').val();
                d.category = $('#filter-category').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'nama_pengadu' },
            { data: 'ticket_nolog' },
            { data: 'aduan_diterima' },
            { data: 'cat_name' },
            { data: 'bahagian' },
            { data: 'status_display', orderable: false, searchable: false },
            { data: 'tarikh_selesai' },
            { data: 'pegawai_tugasan' }
        ],
        dom: "<'row mb-2'<'col-md-6'><'col-md-6 text-end'B>>tr<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [
            {
                extend: 'excel',
                title: 'Senarai Rekod Laporan Aduan Sistem TechCare',
                filename: 'Rekod_Laporan_' + new Date().getTime()
            },
            {
                extend: 'pdf',
                title: 'Senarai Rekod Laporan Aduan Sistem TechCare',
                filename: 'Rekod_Laporan_' + new Date().getTime(),
                orientation: 'landscape',
                pageSize: 'A4'
            },
            {
                extend: 'print',
                title: 'Senarai Rekod Laporan Aduan Sistem TechCare'
            }
        ],
        order: [[2, 'desc']]
    });

    $('#filter-from, #filter-to, #filter-category').on('change', function () {
        loadRingkasan();
        perincianTable.ajax.reload();
    });

    loadRingkasan();
});
