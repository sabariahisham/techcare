'use strict';

// Initialize delete functionality when document is ready
$(document).ready(function () {
  console.log('Permission delete script loaded');
  console.log('SweetAlert2 available:', typeof Swal !== 'undefined');
  console.log('jQuery available:', typeof $ !== 'undefined');
  console.log('DataTables available:', typeof $.fn.DataTable !== 'undefined');

  // Test if delete buttons exist
  setTimeout(function() {
    const deleteButtons = $('.delete-permission');
    console.log('Delete buttons found:', deleteButtons.length);
    if (deleteButtons.length > 0) {
      console.log('First delete button:', deleteButtons.first());
      console.log('First button data-id:', deleteButtons.first().data('id'));
    }
  }, 2000);
  // Add click handler to delete buttons
  $(document).on('click', '.delete-permission', function(e) {
    e.preventDefault();

    const permissionId = $(this).data('id');
    console.log('Delete button clicked!');
    console.log('Permission ID:', permissionId);
    console.log('Button element:', this);

    if (!permissionId) {
      console.error('Permission ID not found');
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Permission ID not found. Please refresh the page.',
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
      } else {
        alert('Permission ID not found. Please refresh the page.');
      }
      return;
    }

    // Simple confirmation since we're using backend-generated buttons
    const permissionName = 'this permission';
    console.log('Proceeding with delete for permission ID:', permissionId);

    // Check if SweetAlert2 is available
    if (typeof Swal === 'undefined') {
      const confirmDelete = confirm(`Adakah anda pasti ingin memadam ${permissionName}?`);
      if (confirmDelete) {
        executeDelete(permissionId);
      }
      return;
    }

    Swal.fire({
      title: 'Adakah anda pasti?',
      html: `Anda ingin memadam ${permissionName}?`,
      icon: 'warning',
      showCancelButton: true,
      cancelButtonText: 'Tidak, batal!',
      confirmButtonText: 'Ya, padam!',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        executeDelete(permissionId);
      }
    });
  });

  // Function to execute the delete operation
  function executeDelete(permissionId) {
    // Check if CSRF token exists
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    console.log('CSRF Token:', csrfToken);
    console.log('DELETE URL:', `/admin/permissions/${permissionId}`);
    console.log('About to make fetch request...');

    if (!csrfToken) {
      const errorMsg = 'CSRF token not found. Please refresh the page.';
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMsg,
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
      } else {
        alert(errorMsg);
      }
      return;
    }

    fetch(`/admin/permissions/${permissionId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => {
      console.log('Response status:', response.status);
      console.log('Response headers:', response.headers);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      return response.json();
    })
    .then(data => {
      console.log('Response data:', data);
      
      if(data.success) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Dipadam!',
            text: data.message,
            timer: 2000,
            showConfirmButton: false,
            customClass: {
              confirmButton: 'btn btn-success'
            },
            buttonsStyling: false
          }).then(() => {
            refreshDataTable();
          });
        } else {
          alert(data.message || 'Permission successfully deleted!');
          refreshDataTable();
        }
      } else {
        console.error('Delete failed:', data);
        const errorMsg = data.message || 'Ralat semasa memadam permission';
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMsg,
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        } else {
          alert(errorMsg);
        }
      }
    })
    .catch(error => {
      console.error('Delete error:', error);
      let errorMessage = 'Ralat semasa memadam permission. Sila cuba lagi.';

      if (error.message) {
        errorMessage = `Network error: ${error.message}`;
      }

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage,
          customClass: {
            confirmButton: 'btn btn-primary'
          },
          buttonsStyling: false
        });
      } else {
        alert(errorMessage);
      }
    });
  }

  // Function to refresh DataTable
  function refreshDataTable() {
    console.log('Reloading DataTable...');
    if ($.fn.DataTable.isDataTable('.datatables-permissions')) {
      $('.datatables-permissions').DataTable().ajax.reload();
    } else {
      console.log('DataTable not initialized, reloading page');
      window.location.reload();
    }
  }

  // Global test function for debugging (can be called from browser console)
  window.testDeleteButton = function(permissionId) {
    if (permissionId) {
      console.log('Testing delete for permission ID:', permissionId);
      executeDelete(permissionId);
    } else {
      console.log('Available delete buttons:');
      $('.delete-permission').each(function(index) {
        console.log(`Button ${index + 1}: data-id=${$(this).data('id')}`);
      });
    }
  };

  // Global test function for checking delete button event handlers
  window.testDeleteButtonEvents = function() {
    const deleteButtons = $('.delete-permission');
    console.log('Total delete buttons:', deleteButtons.length);
    
    deleteButtons.each(function(index) {
      const button = $(this);
      const events = $._data(this, 'events') || {};
      console.log(`Button ${index + 1}:`, {
        'data-id': button.data('id'),
        'class': button.attr('class'),
        'events': Object.keys(events)
      });
    });
  };
});
