document.addEventListener('DOMContentLoaded', function () {
  const dropZone = document.getElementById('attachment-drop-zone');
  const fileInput = document.getElementById('ticket-attachments');
  const filesPreview = document.getElementById('attachments-preview');

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
  window.selectedAttachments = selectedAttachments;

  // Check if toastr is available, if not provide console fallback
  const showToastr = {
    success: function (message) {
      if (typeof toastr !== 'undefined') {
        toastr.success(message);
      } else {
        console.log('SUCCESS:', message);
      }
    },
    error: function (message) {
      if (typeof toastr !== 'undefined') {
        toastr.error(message);
      } else {
        console.log('ERROR:', message);
      }
    },
    warning: function (message) {
      if (typeof toastr !== 'undefined') {
        toastr.warning(message);
      } else {
        console.log('WARNING:', message);
      }
    },
    info: function (message) {
      if (typeof toastr !== 'undefined') {
        toastr.info(message);
      } else {
        console.log('INFO:', message);
      }
    }
  };

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
    if (this.files && this.files.length > 0) {
      handleFiles(this.files);
    }
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
    handleFiles(files);
  }

  function handleFiles(fileList) {
    if (!fileList || fileList.length === 0) return;

    // Convert FileList to Array for easier manipulation
    const files = Array.from(fileList);

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
      showToastr.error(message);
      return;
    }

    // Validate each file
    files.forEach(file => {
      // Check if file is already selected
      const isDuplicate = selectedAttachments.some(selectedFile =>
        selectedFile.name === file.name &&
        selectedFile.size === file.size &&
        selectedFile.lastModified === file.lastModified
      );

      if (isDuplicate) {
        errors.push(`Fail "${file.name}" sudah dipilih`);
        showToastr.warning(`Fail "${file.name}" sudah dipilih`);
        hasErrors = true;
        return;
      }

      // Validate file type
      const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
      const isValidType = Object.values(allowedTypes).some(extensions =>
        extensions.includes(fileExtension)
      ) || Object.keys(allowedTypes).includes(file.type);

      if (!isValidType) {
        errors.push(`"${file.name}" format tidak dibenarkan`);
        showToastr.error(
          `"${file.name}" format tidak dibenarkan. Hanya PDF, DOC, DOCX, JPG, JPEG, PNG dibenarkan.`
        );
        hasErrors = true;
        return;
      }

      // Validate file size (2MB = 2 * 1024 * 1024 bytes)
      if (file.size > maxFileSize) {
        const fileSize = formatFileSize(file.size);
        errors.push(`"${file.name}" (${fileSize}) melebihi saiz maksimum 2MB`);
        showToastr.error(
          `"${file.name}" (${fileSize}) melebihi saiz maksimum 2MB. Sila pilih fail yang lebih kecil.`
        );
        hasErrors = true;
        return;
      }

      filesToAdd.push(file);
    });

    // Show consolidated error if any
    if (hasErrors && errors.length > 0) {
      showError(errors.join(', '));
    }

    // Add valid files
    if (filesToAdd.length > 0) {
      // Check if we would exceed the limit with valid files
      if (selectedAttachments.length + filesToAdd.length > maxFiles) {
        const canAdd = maxFiles - selectedAttachments.length;
        const filesToAddLimited = filesToAdd.slice(0, canAdd);
        const filesRejected = filesToAdd.slice(canAdd);

        // Add only the files that fit within the limit
        filesToAddLimited.forEach(file => {
          selectedAttachments.push(file);
        });
        window.selectedAttachments = selectedAttachments;

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
        window.selectedAttachments = selectedAttachments;

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
    } else if (!hasErrors) {
      // If no files were added and there were no validation errors, show info message
      showToastr.info('Tiada fail yang valid untuk ditambah');
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
      headerDiv.innerHTML = `
            <small class="text-muted fw-semibold">Fail dipilih (${selectedAttachments.length}/${maxFiles})</small>
            <button type="button" class="btn btn-sm btn-outline-secondary remove-all-attachments" onclick="removeAllAttachments()">
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
    window.selectedAttachments = selectedAttachments;
    updateFileInput();
    updateAttachmentsPreview();
    clearValidationError();

    showToastr.info(`Fail "${fileName}" telah dibuang`);
  }

  function removeAllAttachments() {
    const fileCount = selectedAttachments.length;
    selectedAttachments = [];
    window.selectedAttachments = selectedAttachments;
    fileInput.value = '';
    filesPreview.innerHTML = '';
    clearValidationError();

    if (fileCount > 0) {
      showToastr.info(`Semua fail (${fileCount}) telah dibuang`);
    }
  }

  // Make removeAllAttachments globally accessible
  window.removeAllAttachments = removeAllAttachments;

  function showError(message) {
    dropZone.classList.add('error');
    const feedback = dropZone.closest('.file-upload-wrapper').querySelector('.invalid-feedback');
    feedback.textContent = message;
    feedback.style.display = 'block';
  }

  function clearValidationError() {
    const feedback = dropZone.closest('.file-upload-wrapper').querySelector('.invalid-feedback');
    feedback.style.display = 'none';
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  // Reset form when offcanvas is closed
  const offcanvas = document.getElementById('offcanvasAddTicket');
  if (offcanvas) {
    offcanvas.addEventListener('hidden.bs.offcanvas', function () {
      removeAllAttachments();
      dropZone.classList.remove('dragover', 'error');
      clearValidationError();

      // Reset form
      const form = document.getElementById('addTicketForm');
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
});
