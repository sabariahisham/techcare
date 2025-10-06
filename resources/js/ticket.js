'use strict';

// Ticket form functionality
$(function () {
  // Handle ticket type change (ADD FORM)
  $('#ticketType').on('change', function() {
    if ($(this).val() == '0') {
      $('#selfInfo').addClass('d-none');
      $('#otherInfo').removeClass('d-none');
    } else {
      $('#selfInfo').removeClass('d-none');
      $('#otherInfo').addClass('d-none');
    }
  });

  // Handle ticket type change (EDIT FORM) - TAMBAH INI
  $('#edit_ticketType').on('change', function () {
    if ($(this).val() == '0') {
      $('#edit_selfInfo').addClass('d-none');
      $('#edit_otherInfo').removeClass('d-none');
    } else {
      $('#edit_selfInfo').removeClass('d-none');
      $('#edit_otherInfo').addClass('d-none');
    }
  });

  // Handle category change for subcategory (ADD FORM)
  $('#cat_id').change(function(){
    var catID = $(this).val();
    if(catID){
      $.ajax({
        url: '/get-subcategory/'+catID,
        type: 'GET',
        success: function(data){
          $('#subcat_id').empty();
          $('#subcat_id').append('<option value="">-- Pilih Subkategori --</option>');
          $.each(data, function(key, value){
            $('#subcat_id').append('<option value="'+value.id+'">'+value.name+'</option>');
          });
        },
        error: function() {
          toastr.error('Ralat memuat subkategori');
        }
      });
    } else {
      $('#subcat_id').empty();
      $('#subcat_id').append('<option value="">-- Pilih Subkategori --</option>');
    }
  });

  // Handle category change for subcategory (EDIT FORM) - TAMBAH INI
  $('#edit_cat_id').change(function(){
    var catID = $(this).val();
    if(catID){
      $.ajax({
        url: '/get-subcategory/'+catID,
        type: 'GET',
        success: function(data){
          $('#edit_subcat_id').empty();
          $('#edit_subcat_id').append('<option value="">-- Pilih Subkategori --</option>');
          $.each(data, function(key, value){
            $('#edit_subcat_id').append('<option value="'+value.id+'">'+value.name+'</option>');
          });
        },
        error: function() {
          toastr.error('Ralat memuat subkategori');
        }
      });
    } else {
      $('#edit_subcat_id').empty();
      $('#edit_subcat_id').append('<option value="">-- Pilih Subkategori --</option>');
    }
  });

  // Handle form submission (ADD FORM)
  $('#addTicketForm').on('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    var submitBtn = $(this).find('button[type="submit"]');

    // Disable submit button
    submitBtn.prop('disabled', true).text('Menghantar...');

    $.ajax({
      url: '/tickets',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        // Close offcanvas
        $('#offcanvasAddTicket').offcanvas('hide');

        // Reset form
        $('#addTicketForm')[0].reset();
        $('#subcat_id').empty().append('<option value="">-- Pilih Subkategori --</option>');

        // Reset ticket type display
        $('#selfInfo').removeClass('d-none');
        $('#otherInfo').addClass('d-none');
        $('#ticketType').val('1');

        // Reload datatable
        if ($.fn.DataTable.isDataTable('.datatables-tickets')) {
          $('.datatables-tickets').DataTable().ajax.reload();
        }

        // Show success message
        if (typeof toastr !== 'undefined') {
          toastr.success('Tiket berjaya dicipta!');
        }
      },
      error: function(xhr) {
        var errors = xhr.responseJSON?.errors;
        if (errors) {
          Object.keys(errors).forEach(function(key) {
            toastr.error(errors[key][0]);
          });
        } else {
          toastr.error('Ralat berlaku. Sila cuba lagi.');
        }
      },
      complete: function() {
        // Re-enable submit button
        submitBtn.prop('disabled', false).text('Simpan & Hantar');
      }
    });
  });

  // Handle form submission (EDIT FORM) - TAMBAH INI
  $('#editTicketForm').on('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    var ticketId = $('#edit_ticket_id').val();
    var submitBtn = $(this).find('button[type="submit"]');

    // Add method spoofing for PUT request
    formData.append('_method', 'PUT');

    // Disable submit button
    submitBtn.prop('disabled', true).text('Mengemas kini...');

    $.ajax({
      url: '/tickets/' + ticketId,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        // Close offcanvas
        $('#offcanvasEditTicket').offcanvas('hide');

        // Reload datatable
        if ($.fn.DataTable.isDataTable('.datatables-tickets')) {
          $('.datatables-tickets').DataTable().ajax.reload();
        }

        // Show success message
        if (typeof toastr !== 'undefined') {
          toastr.success('Tiket berjaya dikemas kini!');
        }
      },
      error: function(xhr) {
        var errors = xhr.responseJSON?.errors;
        if (errors) {
          Object.keys(errors).forEach(function(key) {
            toastr.error(errors[key][0]);
          });
        } else {
          toastr.error('Ralat berlaku. Sila cuba lagi.');
        }
      },
      complete: function() {
        // Re-enable submit button
        submitBtn.prop('disabled', false).text('Kemaskini Tiket');
      }
    });
  });

  // Handle delete ticket
  $(document).on('click', '.delete-record', function() {
    var ticketId = $(this).data('id');

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
        $.ajax({
          url: '/tickets/' + ticketId,
          type: 'DELETE',
          data: {
            "_token": $('meta[name="csrf-token"]').attr('content'),
          },
          success: function(response) {
            $('.datatables-tickets').DataTable().ajax.reload();
            Swal.fire('Dipadam!', 'Tiket telah dipadam.', 'success');
          },
          error: function() {
            Swal.fire('Ralat!', 'Ralat berlaku semasa memadam tiket.', 'error');
          }
        });
      }
    });
  });

  // Function to load ticket data for editing - TAMBAH INI
  window.loadTicketData = function(ticket) {
    $('#edit_ticket_id').val(ticket.id);
    $('#edit_tick_title').val(ticket.tick_title);
    $('#edit_tick_desc').val(ticket.tick_desc);

    // Set category and trigger change to load subcategories
    $('#edit_cat_id').val(ticket.cat_id).trigger('change');

    // Wait for subcategories to load, then set the value
    setTimeout(function() {
      $('#edit_subcat_id').val(ticket.subcat_id);
    }, 500);

    // Set ticket type and trigger change
    $('#edit_ticketType').val(ticket.ticket_type).trigger('change');

    // If it's for other person, populate the fields
    if (ticket.ticket_type == 0) {
      $('#edit_other_name').val(ticket.other_name);
      $('#edit_other_email').val(ticket.other_email);
      $('#edit_other_sektor').val(ticket.other_sektor);
      $('#edit_other_agency').val(ticket.other_agency);
      $('#edit_other_seksyen').val(ticket.other_seksyen);
      $('#edit_other_unit').val(ticket.other_unit);
    }
  };

  // Handle edit button click - TAMBAH INI
  $(document).on('click', '.edit-record', function() {
    var ticketId = $(this).data('id');

    // Fetch ticket data
    $.ajax({
      url: '/tickets/' + ticketId + '/edit',
      type: 'GET',
      success: function(response) {
        // Load data into form
        loadTicketData(response.ticket);

        // Show offcanvas
        $('#offcanvasEditTicket').offcanvas('show');
      },
      error: function() {
        toastr.error('Ralat memuat data tiket');
      }
    });
  });
});
