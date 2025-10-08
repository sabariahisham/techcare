/**
 * Page Sub Category List
 */

'use strict';

// Add custom CSS for row grouping
const groupingStyles = `
  <style>
    .dataTables_wrapper .group-header td {
      position: relative;
      overflow: hidden;
    }

    .dataTables_wrapper .group-header td::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, #696cff 0%, #8b5fbf 50%, #ff6b9d 100%);
      opacity: 0.95;
      z-index: -1;
    }

    .dataTables_wrapper .group-header:hover td::before {
      opacity: 1;
    }

    .dataTables_wrapper .group-header {
      cursor: default;
    }

    .dataTables_wrapper .group-header td {
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: all 0.2s ease;
    }
  </style>
`;

// Inject the styles
if (!document.querySelector('#row-grouping-styles')) {
  const styleElement = document.createElement('div');
  styleElement.id = 'row-grouping-styles';
  styleElement.innerHTML = groupingStyles;
  document.head.appendChild(styleElement);
}

// Datatable (jquery)
$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for table
  var dt_subcategory_table = $('.datatables-refcategory'),
    select2 = $('.select2');

  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Select Category',
      dropdownParent: $this.parent()
    });
  }

  // Sub Categories datatable
  if (dt_subcategory_table.length) {
    // Make dt_subcategory global so it can be accessed from other files
    window.dt_subcategory = dt_subcategory_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: '/ref-sub-category-management/data',
      columns: [
        {
          // For Responsive
          data: null,
          defaultContent: '',
          orderable: false,
          searchable: false
        },
        {
          // Index column
          data: 'DT_RowIndex',
          name: 'DT_RowIndex',
          orderable: false,
          searchable: false
        },
        // { data: 'id', name: 'id' },
        { data: 'cat_name', name: 'cat_name', visible: false },
        { data: 'subcat_name', name: 'subcat_name' },
        { data: 'subcat_desc', name: 'subcat_desc' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
      ],
      order: [[2, 'asc']], // Order by category name
      rowGroup: {
        dataSrc: 'cat_name',
        startRender: function (rows, group) {
          return $('<tr class="group-header"/>')
            .append('<td colspan="5" class="table-group-header" style="background: linear-gradient(90deg, #696cff 0%, #8b5fbf 100%); color: white; font-weight: 600; padding: 12px 16px; border: none;">' +
                    '<i class="bx bx-category me-2" style="font-size: 16px;"></i>' +
                    '<span style="font-size: 14px;">' + group + '</span>' +
                    ' <span class="badge bg-white text-primary ms-2" style="font-size: 11px; padding: 4px 8px;">' + rows.count() + ' sub kategori</span>' +
                    '</td>');
        },
        endRender: null,
        className: 'group-end'
      },
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
          className: 'btn btn-label-secondary dropdown-toggle me-4 d-none', // Add d-none class to hide the button
          text: '<i class="bx bx-export me-2 bx-sm"></i>Export',
          buttons: [
            {
              extend: 'print',
              text: '<i class="bx bx-printer me-2" ></i>Print',
              className: 'dropdown-item'
            },
            {
              extend: 'csv',
              text: '<i class="bx bx-file me-2" ></i>Csv',
              className: 'dropdown-item'
            },
            {
              extend: 'excel',
              text: '<i class="bx bxs-file-export me-2"></i>Excel',
              className: 'dropdown-item'
            },
            {
              extend: 'pdf',
              text: '<i class="bx bxs-file-pdf me-2"></i>Pdf',
              className: 'dropdown-item'
            },
            {
              extend: 'copy',
              text: '<i class="bx bx-copy me-2" ></i>Copy',
              className: 'dropdown-item'
            }
          ]
        },
        {
          text: '<i class="bx bx-plus bx-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">TAMBAH SUB KATEGORI</span>',
          className: 'add-new btn btn-primary',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasAddSubCategory'
          }
        }
      ],
      // Initialize category filter
      initComplete: function () {
        // Adding category filter once table initialized for the hidden category column
        this.api()
          .columns(2) // Category column (now hidden)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="CategoryFilter" class="form-select text-capitalize"><option value=""> Pilih Kategori </option></select>'
            )
              .appendTo('.category_filter')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

            // Get unique categories from server data
            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                if (d && d !== '-') {
                  select.append('<option value="' + d + '" class="text-capitalize">' + d + '</option>');
                }
              });
          });
      },
      responsive: true,
      language: {
        search: '',
        searchPlaceholder: 'Carian'
      }
    });
  }

  // Delete Record
  $('.datatables-refcategory tbody').on('click', '.delete-record', function () {
    var id = $(this).data('id');

    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        // Delete record
        $.ajax({
          type: 'DELETE',
          url: `/ref-sub-category-management/${id}`,
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function () {
            window.dt_subcategory.ajax.reload();
            toastr.success('Sub Category has been deleted successfully.');
          },
          error: function (error) {
            toastr.error('An error occurred while deleting the sub category.');
            console.error(error);
          }
        });
      }
    });
  });

  // Edit Record
  $('.datatables-refcategory tbody').on('click', '.edit-record', function () {
    var id = $(this).data('id');

    // Fetch sub category data and populate form
    $.ajax({
      type: 'GET',
      url: `/ref-sub-category-management/${id}/edit`,
      success: function (data) {
        // Populate the edit form with data
        $('#edit_subcat_name').val(data.subcat_name);
        $('#edit_subcat_desc').val(data.subcat_desc);
        $('#edit_cat_id').val(data.cat_id).trigger('change');
        $('#edit_subcategory_id').val(data.id);

        // Show the edit offcanvas
        var editOffcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasEditSubCategory'));
        editOffcanvas.show();
      },
      error: function (error) {
        toastr.error('An error occurred while fetching sub category data.');
        console.error(error);
      }
    });
  });

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.dataTables_filter .form-control').removeClass('form-control-sm');
    $('.dataTables_length .form-select').removeClass('form-select-sm');
  }, 300);
});

// Form Initialization and submission handlers
(function () {
  // Edit sub category form submission
  $('#editSubCategoryForm').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const id = $('#edit_subcategory_id').val();

    $.ajax({
      type: 'POST',
      url: `/ref-sub-category-management/${id}`,
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-HTTP-Method-Override': 'PUT'
      },
      success: function (response) {
        if (response.success) {
          // Hide offcanvas
          var editOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasEditSubCategory'));
          editOffcanvas.hide();

          // Reload DataTable
          window.dt_subcategory.ajax.reload();

          // Show success message
          toastr.success(response.message);
        } else {
          toastr.error(response.message);
        }
      },
      error: function (xhr) {
        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;
          let errorMessage = '';
          for (const field in errors) {
            errorMessage += errors[field][0] + '\n';
          }
          toastr.error(errorMessage);
        } else {
          toastr.error('An error occurred while updating the sub category.');
        }
      }
    });
  });
})();
