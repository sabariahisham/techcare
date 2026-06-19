import logger from '../logger.js';

'use strict';

$(function () {
  const editOfficerForm = document.getElementById('editOfficerForm');

  // Initialize user management functionality
  initializeOfficerUserManagement();

  // Initialize select2 for edit form
  function initializeEditFormSelect2() {
    logger.log('Initializing select2 for edit form...');

    // Initialize select2 for edit form dropdowns
    $('#edit-officer-category').select2({
      placeholder: 'Pilih Kategori',
      dropdownParent: $('#offcanvasEditOfficer'),
      width: '100%'
    });

    $('#edit-officer-subcategory').select2({
      placeholder: 'Pilih Sub Kategori',
      dropdownParent: $('#offcanvasEditOfficer'),
      width: '100%'
    });

    $('#edit-officer-user').select2({
      placeholder: 'Pilih Pegawai (boleh pilih lebih dari satu)',
      dropdownParent: $('#offcanvasEditOfficer'),
      width: '100%',
      multiple: true
    });

    logger.log('Select2 initialization completed for edit form');
  }

  // Form Validation
  const fv = FormValidation.formValidation(editOfficerForm, {
    fields: {
      cat_id: {
        validators: {
          notEmpty: {
            message: 'Sila pilih kategori'
          }
        }
      },
      subcat_id: {
        validators: {
          notEmpty: {
            message: 'Sila pilih sub kategori'
          }
        }
      },
      user_id: {
        validators: {
          notEmpty: {
            message: 'Sila pilih sekurang-kurangnya seorang pegawai'
          }
        }
      },
      level_tugasan: {
        validators: {
          notEmpty: {
            message: 'Sila masukkan level tugasan'
          },
          between: {
            min: 1,
            max: 3,
            message: 'Level tugasan mesti antara 1 hingga 3'
          }
        }
      }
    },
    plugins: {
      trigger: new FormValidation.plugins.Trigger(),
      bootstrap5: new FormValidation.plugins.Bootstrap5({
        eleValidClass: '',
        rowSelector: '.mb-6'
      }),
      submitButton: new FormValidation.plugins.SubmitButton(),
      autoFocus: new FormValidation.plugins.AutoFocus()
    }
  }).on('core.form.valid', function () {
    const formData = new FormData(editOfficerForm);
    const officerId = $('#edit-officer-id').val();

    // Get multiple selected user IDs
    const selectedUserIds = $('#edit-officer-user').val();
    const relatedOfficerIds = $('#editOfficerForm').data('related-officer-ids') || [];

    // Prepare data object
    const requestData = {
      cat_id: formData.get('cat_id'),
      subcat_id: formData.get('subcat_id'),
      level_tugasan: formData.get('level_tugasan'),
      'user_id[]': selectedUserIds,
      'related_officer_ids': relatedOfficerIds,
      _token: $('meta[name="csrf-token"]').attr('content')
    };

    $.ajax({
      url: `/officer/${officerId}`,
      type: 'PUT',
      data: requestData,
      success: function (response) {
        if (response.success) {
          // Check if there are users selected to add to the officer
          const selectedUsers = $('#edit-officer-users').select2('data');

          if (selectedUsers && selectedUsers.length > 0) {
            logger.log('Adding selected users to officer:', selectedUsers);
            // Add each selected user to the officer
            let usersProcessed = 0;
            const totalUsers = selectedUsers.length;

            selectedUsers.forEach(function (userData) {
              // Extract nama from the text format "nama (email)"
              const userName = userData.text.split(' (')[0];
              const userEmail = userData.email || userData.text.split(' (')[1] ? .replace(')', '') || '';

              // Add user to officer via AJAX
              $.ajax({
                type: 'POST',
                url: `/officer/${officerId}/users`,
                data: {
                  user_id: userData.id
                },
                headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (userResponse) {
                  usersProcessed++;
                  if (usersProcessed === totalUsers) {
                    completeOfficerUpdate();
                  }
                },
                error: function (xhr) {
                  usersProcessed++;
                  console.warn('Failed to add user to officer:', xhr);
                  if (usersProcessed === totalUsers) {
                    completeOfficerUpdate();
                  }
                }
              });
            });
          } else {
            // No users to add, complete the update immediately
            completeOfficerUpdate();
          }

          function completeOfficerUpdate() {
            // Show success message first (before hiding offcanvas)
            const successMessage = selectedUsers && selectedUsers.length > 0 ?
              `Pegawai Tugasan berjaya dikemaskini dan ${selectedUsers.length} pengguna ditambah` :
              'Pegawai Tugasan berjaya dikemaskini';

            // Show success toastr immediately
            toastr.success(successMessage, '', {
              timeOut: 4000,
              closeButton: true,
              progressBar: true,
              positionClass: 'toast-top-right'
            });

            // Small delay to ensure toastr is displayed before offcanvas manipulation
            setTimeout(function () {
              $('#offcanvasEditOfficer').offcanvas('hide');
              editOfficerForm.reset();
              $('#edit-officer-users').val(null).trigger('change');
              $('.datatables-refcategory').DataTable().ajax.reload();
            }, 100);
          }
        }
      },
      error: function (xhr) {
        const errorMessage = xhr.responseJSON ? .message || 'Ralat mengemas kini pegawai tugasan';
        toastr.error(errorMessage, 'Ralat!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Load officer data when edit button is clicked
  $(document).on('click', '.edit-record', function () {
    const officerId = $(this).data('id');

    // Destroy all select2 instances to ensure clean initialization
    $('#edit-officer-category, #edit-officer-subcategory, #edit-officer-user').each(function () {
      if ($(this).hasClass('select2-hidden-accessible')) {
        $(this).select2('destroy');
      }
    });

    // Force re-initialize all select2 elements
    initializeEditFormSelect2();

    $.ajax({
      url: `/officer/${officerId}/edit`,
      type: 'GET',
      success: function (response) {
        if (response.success) {
          populateEditForm(response.data);
          $('#offcanvasEditOfficer').offcanvas('show');
        }
      },
      error: function (xhr) {
        toastr.error('Ralat memuatkan data pegawai', 'Ralat!', {
          closeButton: true,
          tapToDismiss: false,
          progressBar: true,
          rtl: false
        });
      }
    });
  });

  // Populate edit form with officer data
  function populateEditForm(officer) {
    // Set officer ID
    editOfficerForm.setAttribute('data-officer-id', officer.id);

    // Populate basic fields
    $('#edit-officer-id').val(officer.id);

    // Store related officer IDs for group editing
    $('#editOfficerForm').data('related-officer-ids', officer.related_officer_ids || [officer.id]);

    $('#edit-officer-category').val(officer.cat_id).trigger('change');
    $('#edit-officer-level').val(officer.level_tugasan);

    // Set multiple user IDs for Select2 (existing functionality)
    logger.log('Setting user IDs:', officer.user_id);
    if (Array.isArray(officer.user_id)) {
      $('#edit-officer-user').val(officer.user_id).trigger('change');
    } else if (officer.user_id) {
      $('#edit-officer-user').val([officer.user_id]).trigger('change');
    } else {
      $('#edit-officer-user').val([]).trigger('change');
    }

    // Load subcategories for the selected category
    loadSubcategories(officer.cat_id, officer.subcat_id, '#edit-officer-subcategory');

    // Load users assigned to this officer (new functionality matching roles)
    let users = officer.users || [];
    populateOfficerUsersSection(users);
  }

  // Handle category change to load subcategories
  $('#edit-officer-category').on('change', function () {
    const categoryId = $(this).val();
    loadSubcategories(categoryId, null, '#edit-officer-subcategory');
  });

  // Function to load subcategories
  function loadSubcategories(categoryId, selectedSubcatId = null, targetSelector) {
    if (!categoryId) {
      $(targetSelector).empty().append('<option value="">Pilih Sub Kategori</option>');
      return;
    }

    $.ajax({
      url: `/ref-subcategory-management/by-category/${categoryId}`,
      type: 'GET',
      success: function (response) {
        let options = '<option value="">Pilih Sub Kategori</option>';
        if (response.success && response.data) {
          response.data.forEach(function (subcategory) {
            const selected = selectedSubcatId && selectedSubcatId == subcategory.id ? 'selected' : '';
            options += `<option value="${subcategory.id}" ${selected}>${subcategory.subcat_name}</option>`;
          });
        }
        $(targetSelector).html(options);
      },
      error: function () {
        toastr.error('Ralat memuatkan sub kategori', 'Ralat!');
        $(targetSelector).empty().append('<option value="">Pilih Sub Kategori</option>');
      }
    });
  }

  // User Management functionality for edit form - matches roles-edit.js exactly
  function initializeOfficerUserManagement() {
    // Initialize Select2 for user selection in edit form
    $('#edit-officer-users').select2({
      dropdownParent: $('#offcanvasEditOfficer'),
      placeholder: 'Select users to add to this officer...',
      allowClear: true,
      multiple: true,
      ajax: {
        url: '/admin/users/search',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          const officerId = editOfficerForm.getAttribute('data-officer-id');
          return {
            q: params.term,
            format: 'select2',
            exclude_officer: officerId
          };
        },
        processResults: function (data) {
          return data;
        },
        cache: false // Don't cache for edit form as it changes based on officer
      },
      minimumInputLength: 0,
      templateResult: function (user) {
        if (user.loading) {
          return user.text;
        }

        if (!user.id) {
          return user.text;
        }

        // Extract name from text (format: "nama (email)")
        const displayName = user.text.split(' (')[0];
        const initials = displayName.substring(0, 2).toUpperCase();

        var $container = $(
          '<div class="d-flex align-items-center">' +
          '<div class="avatar avatar-sm me-3">' +
          '<span class="avatar-initial rounded-circle bg-label-primary">' + initials + '</span>' +
          '</div>' +
          '<div>' +
          '<div class="fw-medium">' + displayName + '</div>' +
          '<small class="text-muted">' + (user.email || '') + '</small>' +
          '</div>' +
          '</div>'
        );

        return $container;
      },
      templateSelection: function (user) {
        if (user.id) {
          return user.text.split(' (')[0] || user.text;
        }
        return user.text;
      }
    });
  }

  // Populate officers users section - matches roles-edit.js exactly
  function populateOfficerUsersSection(users) {
    const usersContainer = $('#editOfficerUsers');

    if (!users || users.length === 0) {
      usersContainer.html('<p class="text-muted">No users assigned to this officer</p>');
      return;
    }

    let usersList = '<div class="list-group list-group-flush">';
    users.forEach(function (user) {
      // Handle both field names for backward compatibility
      const userName = user.nama || user.name || 'Unknown User';
      const userEmail = user.email || '';
      const userId = user.id;

      if (!userId) {
        return; // Skip users without IDs
      }

      const initials = userName.substring(0, 2).toUpperCase();
      usersList += `
        <div class="list-group-item d-flex align-items-center px-0">
          <div class="avatar avatar-sm me-3">
            <span class="avatar-initial rounded-circle bg-label-primary">${initials}</span>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0">${userName}</h6>
            <small class="text-muted">${userEmail}</small>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger remove-user-officer"
                  data-user-id="${userId}"
                  data-officer-id="${editOfficerForm.getAttribute('data-officer-id') || $('#edit-officer-id').val()}"
                  title="Remove from officer">
            <i class="bx bx-x"></i>
          </button>
        </div>
      `;
    });
    usersList += '</div>';

    usersContainer.html(usersList);
  }

  // Handle remove user from officer assignment
  $(document).on('click', '.remove-user-officer', function () {
    const userId = $(this).data('user-id');
    const officerId = $(this).data('officer-id');
    const button = $(this);

    Swal.fire({
      title: 'Remove User?',
      text: "Are you sure you want to remove this user from the officer assignment?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, remove',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        removeUserFromOfficer(userId, officerId, button);
      }
    });
  });

  // Remove user from officer assignment
  function removeUserFromOfficer(userId, officerId, button) {
    $.ajax({
      type: 'DELETE',
      url: `/officer/${officerId}/users/${userId}`,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          button.closest('.list-group-item').fadeOut(300, function () {
            $(this).remove();
            // Check if no users left
            if ($('#editOfficerUsers .list-group-item').length === 0) {
              $('#editOfficerUsers').html('<p class="text-muted">Tiada pegawai ditugaskan</p>');
            }
          });

          toastr.success(response.message || 'Pengguna berjaya dikeluarkan dari officer', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });

          // Reload DataTable if it exists
          if ($('.datatables-refcategory').length) {
            $('.datatables-refcategory').DataTable().ajax.reload();
          }
        } else {
          toastr.error(response.message || 'Ralat mengeluarkan pegawai dari tugasan', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });
        }
      },
      error: function (xhr) {
        let errorMessage = 'Ralat mengeluarkan pegawai dari tugasan';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        toastr.error(errorMessage, '', {
          timeOut: 5000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right'
        });
      }
    });
  }

  // Add user directly to officer via AJAX
  function addUserToOfficer(userId, userName, userEmail, officerId) {
    $.ajax({
      type: 'POST',
      url: `/officer/${officerId}/users`,
      data: {
        user_id: userId
      },
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          // Add to current officer users display
          const initials = userName.substring(0, 2).toUpperCase();
          const userHtml = `
            <div class="list-group-item d-flex align-items-center px-0">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded-circle bg-label-primary">${initials}</span>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">${userName}</h6>
                <small class="text-muted">${userEmail}</small>
              </div>
              <button type="button" class="btn btn-sm btn-outline-danger remove-user-officer"
                      data-user-id="${userId}"
                      data-officer-id="${officerId}"
                      title="Remove from officer">
                <i class="bx bx-x"></i>
              </button>
            </div>
          `;

          // Check if users container is empty and replace placeholder
          if ($('#editOfficerUsers').find('.text-muted').length > 0) {
            $('#editOfficerUsers').html('<div class="list-group list-group-flush">' + userHtml + '</div>');
          } else {
            $('#editOfficerUsers .list-group').append(userHtml);
          }

          // Show success message
          toastr.success(response.message || 'Pengguna berjaya ditambah ke officer', '', {
            timeOut: 3000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });

          // Reload DataTable if it exists
          if ($('.datatables-refcategory').length) {
            $('.datatables-refcategory').DataTable().ajax.reload();
          }
        } else {
          toastr.error(response.message || 'Ralat menambah pegawai ke tugasan', '', {
            timeOut: 5000,
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right'
          });
        }
      },
      error: function (xhr) {
        let errorMessage = 'Ralat menambah pegawai ke tugasan';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }

        toastr.error(errorMessage, '', {
          timeOut: 5000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right'
        });
      }
    });
  }

  // Reset form when offcanvas is hidden
  $('#offcanvasEditOfficer').on('hidden.bs.offcanvas', function () {
    if (editOfficerForm) {
      // Reset the form completely
      editOfficerForm.reset();
      editOfficerForm.removeAttribute('data-officer-id');

      // Reset form validation
      fv.resetForm();

      // Clear subcategory dropdown
      $('#edit-officer-subcategory').empty().append('<option value="">Pilih Sub Kategori</option>');

      // Clear users sections
      $('#editOfficerUsers').html('<p class="text-muted">Tiada pegawai ditugaskan</p>');
      $('#edit-officer-users').val(null).trigger('change');

      // Reset form fields explicitly
      $('#edit-officer-id').val('');
      $('#edit-officer-category').val('').trigger('change');
      $('#edit-officer-level').val('');
      $('#edit-officer-user').val([]).trigger('change');

      // Ensure DataTable is reloaded when offcanvas closes
      setTimeout(function () {
        if ($('.datatables-refcategory').length) {
          $('.datatables-refcategory').DataTable().ajax.reload();
        }
      }, 200);
    }
  });

  // Initialize select2 on document ready
  $(document).ready(function () {
    // Initialize select2 for edit form on page load if elements exist
    if ($('#edit-officer-category').length && !$('#edit-officer-category').hasClass('select2-hidden-accessible')) {
      initializeEditFormSelect2();
    }
  });
});
