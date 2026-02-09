'use strict';

$(function () {

    function loadRingkasan() {
        $.get('/report/rekod-laporan/ringkasan', {
            from: $('#filter-from').val(),
            to: $('#filter-to').val(),
            category: $('#filter-category').val()
        }, function (res) {

            $('#kpi-jumlah').text(res.kpi.jumlah);
            $('#kpi-purata').text(res.kpi.purata);
            $('#kpi-purata-jam').text(res.kpi.purata_jam);

            let tbody = '';
            res.table.forEach(r => {
                tbody += `
                    <tr>
                        <td>${r.kategori}</td>
                        <td class="text-center">${r.selesai}</td>
                        <td class="text-center fw-bold">${r.jumlah}</td>
                    </tr>`;
            });

            $('#table-ringkasan tbody').html(tbody);
        });
    }

    let perincianTable = $('#table-perincian').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
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
