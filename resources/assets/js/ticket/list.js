/**
 * Handles DataTable initialization and interactions for ticket listing
 * Includes "My Tickets" and "Tickets to Manage" tables with server-side processing
 * Also manages ticket filtering, viewing, and deletion functionalities
 */

'use strict';

// Datatable (jquery)
$(function () {
  let borderColor, bodyBg, headingColor;

  if (typeof isDarkStyle !== 'undefined' && isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for tables
  var dt_my_tickets_table = $('.datatables-my-tickets'),
    dt_manage_tickets_table = $('.datatables-manage-tickets'),
    select2 = $('.select2');

  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Select Country',
      dropdownParent: $this.parent()
    });
  }

  // My Tickets DataTable
  var dt_my_tickets;
  if (dt_my_tickets_table.length) {
    dt_my_tickets = dt_my_tickets_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/tickets/data',
        data: function (d) {
          d.type = 'my';
          d.ticket_filter = $('#ticketFilterSelect').val(); // Add filter parameter
        }
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }, // Bil
        { data: 'no_log_tiket', name: 'no_log_tiket' },
        { data: 'tarikh_aduan', name: 'tarikh_aduan' },
        { data: 'jenis_tiket', name: 'jenis_tiket', orderable: false },
        { data: 'kategori', name: 'kategori' },
        { data: 'sub_kategori', name: 'sub_kategori' },
        { data: 'status', name: 'status' },
        { data: 'pegawai_tugasan', name: 'pegawai_tugasan' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
      ],
      dom:
        '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0 gap-md-4"f<"ticket-filter-container">B>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle me-4 d-none',
          text: '<i class="bx bx-export me-2 bx-sm"></i>Export',
          buttons: [
            { extend: 'print', text: '<i class="bx bx-printer me-2" ></i>Print', className: 'dropdown-item' },
            { extend: 'csv', text: '<i class="bx bx-file me-2" ></i>Csv', className: 'dropdown-item' },
            { extend: 'excel', text: '<i class="bx bxs-file-export me-2"></i>Excel', className: 'dropdown-item' },
            { extend: 'pdf', text: '<i class="bx bxs-file-pdf me-2"></i>Pdf', className: 'dropdown-item' },
            { extend: 'copy', text: '<i class="bx bx-copy me-2" ></i>Copy', className: 'dropdown-item' }
          ]
        },
        {
          text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">ADUAN BARU</span>',
          className: 'add-new btn btn-primary',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddTicket'
          }
        }
      ],
      responsive: true,
      language: {
        search: '',
        searchPlaceholder: 'Carian Tiket Saya',
        emptyTable: 'Tiada data tersedia dalam jadual',
        info: 'Menunjukkan _START_ hingga _END_ daripada _TOTAL_ entri',
        infoEmpty: 'Menunjukkan 0 hingga 0 daripada 0 entri',
        infoFiltered: '(ditapis daripada _MAX_ jumlah entri)',
        lengthMenu: 'Tunjuk _MENU_ entri',
        loadingRecords: 'Memuatkan...',
        processing: 'Memproses...',
        zeroRecords: 'Tiada rekod yang sepadan dijumpai'
      }
    });

    // Expose to window for global access
    window.dt_my_tickets = dt_my_tickets;
    console.log('My Tickets DataTable initialized and exposed globally');

    // Add the filter dropdown to the ticket-filter-container
    if (dt_my_tickets) {
      $('.ticket-filter-container').html(`
        <div class="d-flex align-items-center gap-2 me-3">
          <label for="ticketFilterSelect" class="form-label mb-0 text-nowrap" style="font-size: 0.875rem;">Tapis Tiket:</label>
          <select id="ticketFilterSelect" class="form-select form-select-sm" style="min-width: 200px;">
            <option value="all">Semua Tiket</option>
            <option value="diri_sendiri">Diri Sendiri</option>
            <option value="bagi_pihak">Bagi Pihak</option>
          </select>
        </div>
      `);
    }
  }

  // Tickets to Manage DataTable
  var dt_manage_tickets;
  if (dt_manage_tickets_table.length) {
    dt_manage_tickets = dt_manage_tickets_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/tickets/data',
        data: { type: 'manage' }
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }, // Bil
        { data: 'no_log_tiket', name: 'no_log_tiket' },
        { data: 'tarikh_aduan', name: 'tarikh_aduan' },
        { data: 'nama_pengadu', name: 'nama_pengadu' },
        { data: 'kategori', name: 'kategori' },
        { data: 'sub_kategori', name: 'sub_kategori' },
        { data: 'status', name: 'status' },
        { data: 'pegawai_tugasan', name: 'pegawai_tugasan' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
      ],
      dom:
        '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0 gap-md-4"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle me-4',
          text: '<i class="bx bx-export me-2 bx-sm"></i>Export',
          buttons: [
            { extend: 'print', text: '<i class="bx bx-printer me-2" ></i>Print', className: 'dropdown-item' },
            { extend: 'csv', text: '<i class="bx bx-file me-2" ></i>Csv', className: 'dropdown-item' },
            { extend: 'excel', text: '<i class="bx bxs-file-export me-2"></i>Excel', className: 'dropdown-item' },
            { extend: 'pdf', text: '<i class="bx bxs-file-pdf me-2"></i>Pdf', className: 'dropdown-item' },
            { extend: 'copy', text: '<i class="bx bx-copy me-2" ></i>Copy', className: 'dropdown-item' }
          ]
        }
      ],
      responsive: true,
      language: {
        search: '',
        searchPlaceholder: 'Carian Tiket untuk Diurus',
        emptyTable: 'Tiada data tersedia dalam jadual',
        info: 'Menunjukkan _START_ hingga _END_ daripada _TOTAL_ entri',
        infoEmpty: 'Menunjukkan 0 hingga 0 daripada 0 entri',
        infoFiltered: '(ditapis daripada _MAX_ jumlah entri)',
        lengthMenu: 'Tunjuk _MENU_ entri',
        loadingRecords: 'Memuatkan...',
        processing: 'Memproses...',
        zeroRecords: 'Tiada rekod yang sepadan dijumpai'
      }
    });

    // Expose to window for global access
    window.dt_manage_tickets = dt_manage_tickets;
    console.log('Manage Tickets DataTable initialized and exposed globally');
  }

  // Global reloadAllTables function is defined in config.js
  // It will automatically detect and reload the exposed DataTable instances

  // Edit and delete functionality removed - complainants should only log and submit tickets

  // View Record - Handle view button click for both tables
  $(document).on('click', '.view-record', function () {
    var ticketId = $(this).data('id');

    // Redirect to ticket detail page
    window.location.href = '/tickets/' + ticketId;
  });

  // Manage Ticket - Handle manage/status button click
  $(document).on('click', '.manage-ticket', function () {
    var ticketId = $(this).data('id');

    // You can implement a modal or redirect to ticket management page
    // For now, redirect to the ticket detail page where status can be managed
    window.location.href = '/tickets/' + ticketId;
  });

  // Ticket Filter Dropdown Change Event
  $('#ticketFilterSelect').on('change', function () {
    if (dt_my_tickets) {
      dt_my_tickets.ajax.reload(); // Reload the my tickets table with new filter
    }
  });

  // Handle delete ticket
  $(document).on('click', '.delete-record', function () {
    var ticketId = $(this).data('id');

    Swal.fire({
      title: 'Adakah anda pasti?',
      text: "Tiket ini akan dipadam secara kekal!",
      icon: 'warning',
      showCancelButton: true,
      showDenyButton: false,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, padam!',
      cancelButtonText: 'Tidak'
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading
        Swal.fire({
          title: 'Memproses...',
          text: 'Tiket sedang dipadam',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        $.ajax({
          url: '/tickets/' + ticketId,
          type: 'DELETE',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            console.log('Ticket deleted successfully:', response);

            Swal.fire({
              title: 'Berjaya!',
              text: response.message || 'Tiket berjaya dipadam.',
              icon: 'success',
              timer: 3000,
              timerProgressBar: true
            });

            // Reload DataTables after successful deletion
            setTimeout(function () {
              if (typeof window.reloadAllTables === 'function') {
                window.reloadAllTables();
              } else {
                // Fallback reload
                if (window.dt_my_tickets) {
                  window.dt_my_tickets.ajax.reload();
                }
                if (window.dt_manage_tickets) {
                  window.dt_manage_tickets.ajax.reload();
                }
              }
            }, 1000);
          },
          error: function (xhr) {
            console.error('Delete ticket failed:', xhr);
            var response = xhr.responseJSON;
            var errorMessage = response && response.message
              ? response.message
              : 'Ralat tidak dijangka berlaku. Sila cuba lagi.';

            Swal.fire({
              title: 'Ralat!',
              text: errorMessage,
              icon: 'error'
            });
          }
        });
      }
    });
  });

  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});
