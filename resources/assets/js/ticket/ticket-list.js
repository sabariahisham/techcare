/**
 * Page Ticket List - Role-based DataTables
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
          text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">TAMBAH TIKET</span>',
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
        searchPlaceholder: 'Carian Tiket Saya'
      }
    });

    // Add the filter dropdown to the ticket-filter-container
    if (dt_my_tickets) {
      $('.ticket-filter-container').html(`
        <div class="d-flex align-items-center gap-2 me-3">
          <label for="ticketFilterSelect" class="form-label mb-0 text-nowrap" style="font-size: 0.875rem;">Tapis Tiket:</label>
          <select id="ticketFilterSelect" class="form-select form-select-sm" style="min-width: 200px;">
            <option value="all">Semua Tiket</option>
            <option value="diri_sendiri">Diri Sendiri</option>
            <option value="bagi_pihak">Bagi Pihak (Pengadu)</option>
            <option value="bagi_pihak_sendiri">Bagi Pihak (Sendiri)</option>
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
        searchPlaceholder: 'Carian Tiket untuk Diurus'
      }
    });
  }

  // Function to reload all tables
  function reloadAllTables() {
    if (dt_my_tickets) {
      dt_my_tickets.ajax.reload();
    }
    if (dt_manage_tickets) {
      dt_manage_tickets.ajax.reload();
    }
  }

  // Delete Record - Handle for both tables
  $(document).on('click', '.delete-record', function () {
    var id = $(this).data('id');

    // Use SweetAlert2 if available, otherwise use regular confirm
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Adakah anda pasti?',
        text: "Tiket ini akan dipadam secara kekal!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, padam!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          deleteTicket(id);
        }
      });
    } else {
      if (confirm('Adakah anda pasti untuk memadam tiket ini?')) {
        deleteTicket(id);
      }
    }
  });

  // Function to handle the actual delete operation
  function deleteTicket(id) {
    $.ajax({
      url: '/tickets/' + id,
      type: 'DELETE',
      data: {
        "_token": $('meta[name="csrf-token"]').attr('content'),
      },
      success: function (response) {
        reloadAllTables();

        // Show success message
        if (typeof Swal !== 'undefined') {
          Swal.fire('Dipadam!', response.message || 'Tiket telah dipadam.', 'success');
        } else if (typeof toastr !== 'undefined') {
          toastr.success(response.message || 'Tiket telah dipadam.');
        } else {
          alert(response.message || 'Tiket telah dipadam.');
        }
      },
      error: function (xhr) {
        var message = 'Ralat berlaku semasa memadam tiket.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        }

        if (typeof Swal !== 'undefined') {
          Swal.fire('Ralat!', message, 'error');
        } else if (typeof toastr !== 'undefined') {
          toastr.error(message);
        } else {
          alert(message);
        }
      }
    });
  }

  // Edit Record - Handle edit button click for both tables
  $(document).on('click', '.edit-record', function () {
    var ticketId = $(this).data('id');

    // Show loading if available
    if (typeof toastr !== 'undefined') {
      toastr.info('Memuat data tiket...');
    }

    // Fetch ticket data
    $.ajax({
      url: '/tickets/' + ticketId + '/edit',
      type: 'GET',
      success: function (response) {
        // Load data into edit form using the global function from ticket.js
        if (typeof window.loadTicketData === 'function') {
          window.loadTicketData(response.ticket);
          $('#offcanvasEditTicket').offcanvas('show');
        } else {
          console.error('loadTicketData function not found - make sure ticket.js is loaded');
          if (typeof toastr !== 'undefined') {
            toastr.error('Fungsi edit tidak dijumpai. Sila muat semula halaman.');
          } else {
            alert('Fungsi edit tidak dijumpai. Sila muat semula halaman.');
          }
        }
      },
      error: function (xhr) {
        var message = 'Ralat memuat data tiket';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        }

        if (typeof toastr !== 'undefined') {
          toastr.error(message);
        } else {
          alert(message);
        }
      }
    });
  });

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

  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});
