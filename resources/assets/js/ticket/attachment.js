/**
 * Ticket Attachment Management
 * Handles file uploads, drag & drop, previews, validation, and deletion
 */

import logger from '../logger.js';

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  // Initialize file upload for both add and edit forms
  initializeFileUpload('attachment-drop-zone', 'ticket-attachments', 'attachments-preview', 'offcanvasAddTicket');
  initializeFileUpload('editAttachmentDropZone', 'editTicketAttachments', 'editAttachmentsPreview', 'offcanvasEditTicket');
});

function initializeFileUpload(dropZoneId, fileInputId, previewId, offcanvasId) {
  const dropZone = document.getElementById(dropZoneId);
  const fileInput = document.getElementById(fileInputId);
  const filesPreview = document.getElementById(previewId);

  // Check if elements exist before proceeding
  if (!dropZone || !fileInput || !filesPreview) {
    return; // Exit if required elements are not found
  }

  // Maximum file size (2MB) and maximum number of files (5)
  const maxFileSize = 2 * 1024 * 1024;
  const maxFiles = 5; //maximum number of files
  let selectedAttachments = [];

  // Allowed file types
  const allowedTypes = {
    'application/pdf': ['.pdf'],
    'application/msword': ['.doc'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': ['.docx'],
    'image/jpeg': ['.jpg', '.jpeg'],
    'image/png': ['.png']
  };

  // Make selectedAttachments available globally for form submission
  if (dropZoneId === 'attachment-drop-zone') {
    window.selectedAttachments = selectedAttachments;
  } else {
    window.editSelectedAttachments = selectedAttachments;
  }

  // Check if toastr is available, if not provide console fallback and DOM feedback
  const showToastr = {
    success: function (message) {
      if (typeof toastr !== 'undefined') {
        toastr.success(message);
      } else {
        logger.log('SUCCESS:', message);
        // Fallback: show in page if no toastr
        showFallbackMessage(message, 'success');
      }
    },
    error: function (message) {
      if (typeof toastr !== 'undefined') {
        toastr.error(message);
      } else {
        logger.log('ERROR:', message);
        // Fallback: show in page if no toastr
        showFallbackMessage(message, 'error');
      }
    },
    warning: function (message) {
      if (typeof toastr !== 'undefined') {
        toastr.warning(message);
      } else {
        logger.log('WARNING:', message);
        // Fallback: show in page if no toastr
        showFallbackMessage(message, 'warning');
      }
    },
    info: function (message) {
      if (typeof toastr !== 'undefined') {
        toastr.info(message);
      } else {
        logger.log('INFO:', message);
        // Fallback: show in page if no toastr
        showFallbackMessage(message, 'info');
      }
    }
  };

  // Fallback message display function
  function showFallbackMessage(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Insert before the file upload area
    dropZone.parentNode.insertBefore(alertDiv, dropZone);

    // Auto remove after 5 seconds
    setTimeout(() => {
      if (alertDiv.parentNode) {
        alertDiv.parentNode.removeChild(alertDiv);
      }
    }, 5000);
  }

  // Prevent default drag behaviors
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
  });

  // Highlight drop area when item is dragged over it
  ['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
  });

  // Handle dropped files
  dropZone.addEventListener('drop', handleDrop, false);

  // Handle file input change
  fileInput.addEventListener('change', function (e) {
    e.preventDefault();
    logger.log('File input changed, files selected:', this.files.length);
    if (this.files && this.files.length > 0) {
      handleFiles(this.files);
    }
  });

  // Make drop zone clickable
  dropZone.addEventListener('click', function (e) {
    e.preventDefault();
    fileInput.click();
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  function highlight() {
    dropZone.classList.add('dragover');
  }

  function unhighlight() {
    dropZone.classList.remove('dragover');
    dropZone.classList.remove('error');
  }

  function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    logger.log('Files dropped:', files.length);
    handleFiles(files);
  }

  function handleFiles(fileList) {
    if (!fileList || fileList.length === 0) {
      logger.log('No files to handle');
      return;
    }

    // Convert FileList to Array for easier manipulation
    const files = Array.from(fileList);

    logger.log(`Processing ${files.length} files for ${dropZoneId}`);

    // Reset previous states
    dropZone.classList.remove('error');
    clearValidationError();

    const filesToAdd = [];
    const errors = [];
    let hasErrors = false;

    // Check if adding these files would exceed the limit
    if (selectedAttachments.length + files.length > maxFiles) {
      const message =
        `Maksimum ${maxFiles} fail dibenarkan. Anda sudah mempunyai ${selectedAttachments.length} fail dipilih.`;
      showError(message);
      console.warn('File limit exceeded:', { current: selectedAttachments.length, adding: files.length, max: maxFiles });
      return;
    }

    // Validate each file
    files.forEach((file, index) => {
      logger.log(`Validating file ${index + 1}:`, {
        name: file.name,
        size: file.size,
        type: file.type,
        sizeFormatted: formatFileSize(file.size)
      });

      // Check if file is already selected
      const isDuplicate = selectedAttachments.some(selectedFile =>
        selectedFile.name === file.name &&
        selectedFile.size === file.size &&
        selectedFile.lastModified === file.lastModified
      );

      if (isDuplicate) {
        const errorMsg = `Fail "${file.name}" sudah dipilih`;
        errors.push(errorMsg);
        showToastr.warning(errorMsg);
        hasErrors = true;
        console.warn('Duplicate file detected:', file.name);
        return;
      }

      // Validate file type
      const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
      const isValidType = Object.values(allowedTypes).some(extensions =>
        extensions.includes(fileExtension)
      ) || Object.keys(allowedTypes).includes(file.type);

      logger.log(`File type validation for ${file.name}:`, {
        extension: fileExtension,
        mimeType: file.type,
        isValid: isValidType,
        allowedExtensions: Object.values(allowedTypes).flat(),
        allowedMimeTypes: Object.keys(allowedTypes)
      });

      if (!isValidType) {
        const errorMsg = `Fail "${file.name}" format tidak dibenarkan. Hanya PDF, DOC, DOCX, JPG, JPEG, PNG dibenarkan.`;
        errors.push(errorMsg);
        showToastr.error(errorMsg);
        hasErrors = true;
        console.error('Invalid file type:', { name: file.name, type: file.type, extension: fileExtension });
        return;
      }

      // Validate file size (2MB = 2 * 1024 * 1024 bytes)
      if (file.size > maxFileSize) {
        const fileSize = formatFileSize(file.size);
        const errorMsg = `Fail "${file.name}" (${fileSize}) melebihi saiz maksimum 2MB. Sila pilih fail yang lebih kecil.`;
        errors.push(errorMsg);
        showToastr.error(errorMsg);
        hasErrors = true;
        console.error('File size exceeded:', { name: file.name, size: file.size, maxSize: maxFileSize, formatted: fileSize });
        return;
      }

      logger.log(`File ${file.name} passed validation`);
      filesToAdd.push(file);
    });

    // Log consolidated errors if any (individual errors already showed toastr messages)
    if (hasErrors && errors.length > 0) {
      console.warn('Validation errors found:', errors);
    } else {
      logger.log('All files passed initial validation');
    }

    // Add valid files
    if (filesToAdd.length > 0) {
      // Clear any previous errors since we have valid files
      clearValidationError();

      // Check if we would exceed the limit with valid files
      if (selectedAttachments.length + filesToAdd.length > maxFiles) {
        const canAdd = maxFiles - selectedAttachments.length;
        const filesToAddLimited = filesToAdd.slice(0, canAdd);
        const filesRejected = filesToAdd.slice(canAdd);

        // Add only the files that fit within the limit
        filesToAddLimited.forEach(file => {
          selectedAttachments.push(file);
        });

        // Update global reference
        if (dropZoneId === 'attachment-drop-zone') {
          window.selectedAttachments = selectedAttachments;
        } else {
          window.editSelectedAttachments = selectedAttachments;
        }

        // Show message for added files
        if (filesToAddLimited.length === 1) {
          showToastr.success(`Fail "${filesToAddLimited[0].name}" berjaya ditambah`);
        } else {
          showToastr.success(`${filesToAddLimited.length} fail berjaya ditambah`);
        }

        // Show warning for rejected files
        if (filesRejected.length > 0) {
          showToastr.warning(
            `${filesRejected.length} fail tidak ditambah kerana melebihi had maksimum ${maxFiles} fail`
          );
        }
      } else {
        // Add all files as they don't exceed the limit
        filesToAdd.forEach(file => {
          selectedAttachments.push(file);
        });

        // Update global reference
        if (dropZoneId === 'attachment-drop-zone') {
          window.selectedAttachments = selectedAttachments;
        } else {
          window.editSelectedAttachments = selectedAttachments;
        }

        // Show success message
        if (filesToAdd.length === 1) {
          showToastr.success(`Fail "${filesToAdd[0].name}" berjaya ditambah`);
        } else {
          showToastr.success(`${filesToAdd.length} fail berjaya ditambah`);
        }
      }

      // Update file input and preview
      updateFileInput();
      updateAttachmentsPreview();

      logger.log(`Successfully added ${filesToAdd.length} files`);
    } else if (!hasErrors) {
      // If no files were added and there were no validation errors, show info message
      logger.log('No valid files to add');
      showToastr.info('Tiada fail yang valid untuk ditambah');
    } else {
      logger.log('Files rejected due to validation errors');
    }

    // Clear the file input to allow selecting the same file again if needed
    fileInput.value = '';
  }

  function updateFileInput() {
    // Create a new FileList-like object
    const dt = new DataTransfer();
    selectedAttachments.forEach(file => {
      dt.items.add(file);
    });
    fileInput.files = dt.files;
  }

  function updateAttachmentsPreview() {
    filesPreview.innerHTML = '';

    if (selectedAttachments.length === 0) {
      return;
    }

    // Add header showing file count
    if (selectedAttachments.length > 0) {
      const headerDiv = document.createElement('div');
      headerDiv.className = 'mb-2 d-flex justify-content-between align-items-center';

      // Use different onclick function based on which form we're dealing with
      const removeAllFunction = dropZoneId === 'attachment-drop-zone' ? 'removeAllAttachments()' : 'removeAllEditAttachments()';

      headerDiv.innerHTML = `
            <small class="text-muted fw-semibold">Fail dipilih (${selectedAttachments.length}/${maxFiles})</small>
            <button type="button" class="btn btn-sm btn-outline-secondary remove-all-attachments" onclick="${removeAllFunction}">
                <i class="bx bx-x me-1"></i>Buang Semua
            </button>
        `;
      filesPreview.appendChild(headerDiv);
    }

    selectedAttachments.forEach((file, index) => {
      const fileItem = createAttachmentPreviewItem(file, index);
      filesPreview.appendChild(fileItem);
    });
  }

  function createAttachmentPreviewItem(file, index) {
    const fileItem = document.createElement('div');
    fileItem.className = 'file-item';

    // Get file icon based on file type
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    let iconClass = 'bx bx-file';
    let iconColor = 'text-muted';

    if (['.pdf'].includes(fileExtension)) {
      iconClass = 'bx bxs-file-pdf';
      iconColor = 'text-danger';
    } else if (['.doc', '.docx'].includes(fileExtension)) {
      iconClass = 'bx bxs-file-doc';
      iconColor = 'text-primary';
    } else if (['.jpg', '.jpeg', '.png'].includes(fileExtension)) {
      iconClass = 'bx bxs-file-image';
      iconColor = 'text-success';
    }

    fileItem.innerHTML = `
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="${iconClass} ${iconColor} me-3 fs-4"></i>
                <div>
                    <div class="file-name fw-semibold">${file.name}</div>
                    <small class="file-size text-muted">${formatFileSize(file.size)}</small>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-attachment" data-index="${index}">
                <i class="bx bx-x"></i>
            </button>
        </div>
    `;

    // Add remove file functionality
    const removeBtn = fileItem.querySelector('.remove-attachment');
    removeBtn.addEventListener('click', function () {
      removeAttachment(index);
    });

    return fileItem;
  }

  function removeAttachment(index) {
    const fileName = selectedAttachments[index].name;
    selectedAttachments.splice(index, 1);

    // Update global reference
    if (dropZoneId === 'attachment-drop-zone') {
      window.selectedAttachments = selectedAttachments;
    } else {
      window.editSelectedAttachments = selectedAttachments;
    }

    updateFileInput();
    updateAttachmentsPreview();
    clearValidationError();

    showToastr.info(`Fail "${fileName}" telah dibuang`);
  }

  function removeAllAttachments() {
    const fileCount = selectedAttachments.length;
    selectedAttachments = [];

    // Update global reference
    if (dropZoneId === 'attachment-drop-zone') {
      window.selectedAttachments = selectedAttachments;
    } else {
      window.editSelectedAttachments = selectedAttachments;
    }

    fileInput.value = '';
    filesPreview.innerHTML = '';
    clearValidationError();

    // if (fileCount > 0) {
    //   showToastr.info(`Semua fail (${fileCount}) telah dibuang`);
    // }
  }

  // Make removeAllAttachments globally accessible with unique names
  if (dropZoneId === 'attachment-drop-zone') {
    window.removeAllAttachments = removeAllAttachments;
  } else {
    window.removeAllEditAttachments = removeAllAttachments;
  }

  function showError(message) {
    dropZone.classList.add('error');
    const feedback = dropZone.closest('.file-upload-wrapper').querySelector('.invalid-feedback');
    if (feedback) {
      feedback.textContent = message;
      feedback.style.display = 'block';
      feedback.classList.add('d-block');
    }

    // Also show toastr error
    showToastr.error(message);
  }

  function clearValidationError() {
    dropZone.classList.remove('error');
    const feedback = dropZone.closest('.file-upload-wrapper').querySelector('.invalid-feedback');
    if (feedback) {
      feedback.style.display = 'none';
      feedback.classList.remove('d-block');
      feedback.textContent = '';
    }
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  // Reset form when offcanvas is closed
  const offcanvas = document.getElementById(offcanvasId);
  if (offcanvas) {
    offcanvas.addEventListener('hidden.bs.offcanvas', function () {
      removeAllAttachments();
      dropZone.classList.remove('dragover', 'error');
      clearValidationError();

      // Reset form
      const form = offcanvas.querySelector('form');
      if (form) {
        form.reset();
      }
    });

    // Also handle offcanvas show event to ensure clean state
    offcanvas.addEventListener('show.bs.offcanvas', function () {
      removeAllAttachments();
      dropZone.classList.remove('dragover', 'error');
      clearValidationError();
    });
  }
}

// ====================================
// EXISTING ATTACHMENTS DISPLAY (for edit forms)
// ====================================

// Display existing attachments
function displayExistingAttachments(attachments) {
  if (attachments.length > 0) {
    var attachmentsList = '';
    attachments.forEach(function (attachment) {
      attachmentsList += '<div class="existing-attachment-item" data-id="' + attachment.id + '">';
      attachmentsList += '<div class="attachment-info">';
      attachmentsList += '<div class="attachment-name">' + attachment.doc_name + '</div>';
      attachmentsList += '<div class="attachment-size">' + formatFileSize(attachment.doc_size) + '</div>';
      attachmentsList += '</div>';
      attachmentsList += '<div class="attachment-actions">';
      attachmentsList += '<a href="' + attachment.doc_url + '?view=1" class="btn btn-sm btn-outline-info btn-attachment" target="_blank" title="Lihat">';
      attachmentsList += '<i class="bx bx-show"></i> Lihat';
      attachmentsList += '</a>';
      attachmentsList += '<a href="' + attachment.doc_url + '" class="btn btn-sm btn-outline-primary btn-attachment" target="_blank" title="Muat Turun">';
      attachmentsList += '<i class="bx bx-download"></i> Muat Turun';
      attachmentsList += '</a>';
      attachmentsList += '<button type="button" class="btn btn-sm btn-outline-danger btn-attachment delete-attachment" data-id="' + attachment.id + '">';
      attachmentsList += '<i class="bx bx-trash"></i> Padam';
      attachmentsList += '</button>';
      attachmentsList += '</div>';
      attachmentsList += '</div>';
    });

    $('#editExistingAttachmentsList').html(attachmentsList);
    $('#editExistingAttachments').show();
  } else {
    $('#editExistingAttachments').hide();
  }
}

// Format file size helper function
function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// ====================================
// ATTACHMENT DELETION
// ====================================

$(document).ready(function () {
  // Handle delete attachment
  $(document).on('click', '.delete-attachment', function () {
    var attachmentId = $(this).data('id');
    var attachmentItem = $(this).closest('.existing-attachment-item');
    var deleteBtn = $(this);

    // Debug logging
    logger.log('Delete attachment clicked:', {
      attachmentId: attachmentId,
      csrfToken: $('meta[name="csrf-token"]').attr('content')
    });

    if (!attachmentId) {
      toastr.error('ID lampiran tidak dijumpai');
      return;
    }

    Swal.fire({
      title: 'Adakah anda pasti?',
      text: "Lampiran ini akan dipadam secara kekal!",
      icon: 'warning',
      showCancelButton: true,
      showDenyButton: false,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, padam!',
      cancelButtonText: 'Tidak',
      showLoaderOnConfirm: true,
      preConfirm: () => {
        return new Promise((resolve) => {
          // Make AJAX call to delete attachment
          $.ajax({
            url: '/ticket-attachments/' + attachmentId,
            type: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            },
            beforeSend: function () {
              deleteBtn.prop('disabled', true);
              logger.log('Sending delete request to:', '/ticket-attachments/' + attachmentId);
            },
            success: function (response) {
              logger.log('Delete response:', response);
              if (response.success) {
                resolve(response);
              } else {
                Swal.showValidationMessage(response.message || 'Ralat semasa memadam lampiran');
              }
            },
            error: function (xhr) {
              console.error('Delete error:', xhr);
              let errorMessage = 'Ralat semasa memadam lampiran';

              if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
              } else if (xhr.status === 403) {
                errorMessage = 'Anda tidak mempunyai kebenaran untuk memadam lampiran ini';
              } else if (xhr.status === 404) {
                errorMessage = 'Lampiran tidak dijumpai';
              } else if (xhr.status === 419) {
                errorMessage = 'Token keselamatan telah tamat tempoh. Sila muat semula halaman';
              } else if (xhr.status === 422) {
                errorMessage = 'Data tidak sah';
              } else if (xhr.status === 500) {
                errorMessage = 'Ralat dalaman pelayan. Sila cuba lagi';
              }

              Swal.showValidationMessage(errorMessage);
            },
            complete: function () {
              deleteBtn.prop('disabled', false);
            }
          });
        });
      },
      allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
      if (result.isConfirmed && result.value && result.value.success) {
        // Remove from DOM only after successful deletion from server
        attachmentItem.remove();

        // Check if no more attachments
        if ($('#editExistingAttachmentsList .existing-attachment-item').length === 0) {
          $('#editExistingAttachments').hide();
        }

        toastr.success(result.value.message || 'Lampiran berjaya dipadam.');

        logger.log('Attachment successfully deleted and removed from DOM');
      }
    });
  });
});

// ====================================
// FILE VALIDATION UTILITIES
// ====================================

// Function to validate files immediately when selected (for forms without drag-drop)
function validateFileInput(fileInput) {
  var allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
  var maxFileSize = 2 * 1024 * 1024; // 2MB in bytes
  var wrapper = $(fileInput).closest('.form-field-wrapper');
  var errorContainer = wrapper.find('.error-text');

  // Clear previous errors
  errorContainer.text('').hide();
  $(fileInput).removeClass('is-invalid');
  wrapper.find('.invalid-feedback').remove(); // Remove any old Bootstrap feedback

  if (fileInput.files && fileInput.files.length > 0) {
    for (var i = 0; i < fileInput.files.length; i++) {
      var file = fileInput.files[i];
      var fileName = file.name.toLowerCase();
      var fileExtension = fileName.split('.').pop();

      // Check file extension
      if (!allowedExtensions.includes(fileExtension)) {
        errorContainer.text('Lampiran mestilah dalam format PDF, DOC, DOCX, JPG, JPEG, atau PNG.').show();
        $(fileInput).addClass('is-invalid');
        logger.log('File validation failed - invalid extension. Added is-invalid class.');
        return false;
      }

      // Check file size
      if (file.size > maxFileSize) {
        errorContainer.text('Saiz fail lampiran tidak boleh melebihi 2MB.').show();
        $(fileInput).addClass('is-invalid');
        logger.log('File validation failed - file too large. Added is-invalid class.');
        return false;
      }
    }
  }

  logger.log('File validation passed.');
  return true;
}

// Validate attachments from selectedAttachments array for form submission
function validateAttachments() {
  var errors = {};
  var isValid = true;

  if (window.selectedAttachments && window.selectedAttachments.length > 0) {
    var allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    var maxFileSize = 2 * 1024 * 1024; // 2MB in bytes

    for (var i = 0; i < window.selectedAttachments.length; i++) {
      var file = window.selectedAttachments[i];
      var fileName = file.name.toLowerCase();
      var fileExtension = fileName.split('.').pop();

      // Check file extension
      if (!allowedExtensions.includes(fileExtension)) {
        errors['attachments'] = ['Lampiran mestilah dalam format PDF, DOC, DOCX, JPG, JPEG, atau PNG.'];
        isValid = false;
        break;
      }

      // Check file size
      if (file.size > maxFileSize) {
        errors['attachments'] = ['Saiz fail lampiran tidak boleh melebihi 2MB.'];
        isValid = false;
        break;
      }
    }
  }

  return { isValid: isValid, errors: errors };
}

// Make functions globally available
window.displayExistingAttachments = displayExistingAttachments;
window.formatFileSize = formatFileSize;
window.validateFileInput = validateFileInput;
window.validateAttachments = validateAttachments;
