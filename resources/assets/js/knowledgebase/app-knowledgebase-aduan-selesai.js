/**
 * Senarai Aduan Selesai DataTable
 */

'use strict';

$(function () {
  var dt_aduan_selesai_table = $('.datatables-aduan-selesai');

  if (dt_aduan_selesai_table.length) {
    dt_aduan_selesai_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: '/knowledgebase/aduan-selesai/data',
      columns: [{
          data: null,
          defaultContent: '',
          orderable: false,
          searchable: false
        },
        {
          data: 'DT_RowIndex',
          name: 'DT_RowIndex',
          orderable: false,
          searchable: false
        },
        {
          data: 'no_log_tiket',
          name: 'no_log_tiket'
        },
        {
          data: 'tarikh_aduan',
          name: 'tarikh_aduan'
        },
        {
          data: 'nama_pengadu',
          name: 'nama_pengadu'
        },
        {
          data: 'kategori',
          name: 'kategori'
        },
        {
          data: 'sub_kategori',
          name: 'sub_kategori'
        },
        {
          data: 'status',
          name: 'status'
        },
        {
          data: 'pegawai_tugasan',
          name: 'pegawai_tugasan'
        },
        {
          data: null,
          defaultContent: '',
          orderable: false,
          searchable: false
        }
      ],
      columnDefs: [{
          targets: 0,
          className: 'dtr-control',
          orderable: false,
          searchable: false,
          responsivePriority: 2
        },
        {
          targets: 1,
          responsivePriority: 3
        },
        {
          targets: 2,
          responsivePriority: 1
        },
        {
          targets: 7,
          orderable: false,
          searchable: false
        },
        {
          targets: 9,
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            return '<div class="d-flex gap-2">' +
              '<a href="/tickets/' + row.uuid + '" class="btn btn-sm btn-icon btn-outline-secondary" title="Lihat Tiket">' +
              '<i class="bx bx-show"></i>' +
              '</a>' +
              '<button type="button" class="btn btn-sm btn-icon btn-outline-primary btn-add-to-kb" data-uuid="' + row.uuid + '" data-no-log="' + row.no_log_tiket + '" title="Tambah ke Knowledge Base">' +
              '<i class="bx bx-plus"></i>' +
              '</button>' +
              '</div>';
          }
        }
      ],
      autoWidth: false,
      dom: '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0 gap-md-4"f>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        search: 'Carian..',
        lengthMenu: 'Tunjuk _MENU_ entri',
        info: 'Papar _START_ hingga _END_ daripada _TOTAL_ rekod',
        infoEmpty: 'Tiada rekod tersedia',
        infoFiltered: '(ditapis daripada _MAX_ jumlah rekod)',
        paginate: {
          first: 'Pertama',
          last: 'Terakhir',
          next: 'Seterusnya',
          previous: 'Sebelumnya'
        },
        processing: 'Memproses...',
        zeroRecords: 'Tiada rekod yang sepadan dijumpai'
      },
      order: [
        [3, 'desc']
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Butiran - ' + data['no_log_tiket'];
            }
          }),
          renderer: $.fn.dataTable.Responsive.renderer.tableAll({
            tableClass: 'table'
          })
        }
      }
    });
  }
});
