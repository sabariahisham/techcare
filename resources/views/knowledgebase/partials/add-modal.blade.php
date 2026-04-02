<!-- Modal to add new knowledge base -->
<div class="modal fade" id="knowledgeBaseModal" tabindex="-1" aria-labelledby="knowledgeBaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="knowledgeBaseModalLabel">Tambah Artikel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form class="add-new-knowledgebase" id="addNewKnowledgeBaseForm" onsubmit="return false" novalidate>
          <!-- Hidden field for kb_jenis -->
          <input type="hidden" name="kb_jenis" value="doc_kb">

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="add-kb-title">Tajuk <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="add-kb-title" placeholder="Masukkan tajuk artikel" name="kb_title" aria-label="Article Title" required />
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label" for="add-kb-category">Kategori <span class="text-danger">*</span></label>
              <select id="add-kb-category" class="form-select" name="cat_id" required>
                <option value="">Pilih Kategori</option>
                @foreach(\App\Models\RefCategory::all() as $category)
                  <option value="{{ $category->id }}">{{ $category->cat_name }}</option>
                @endforeach
              </select>
              <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label" for="add-kb-subcategory">Sub Kategori <span class="text-danger">*</span></label>
              <select id="add-kb-subcategory" class="form-select" name="subcat_id" required disabled>
                <option value="">Pilih Sub Kategori</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="add-kb-desc">Deskripsi <span class="text-danger">*</span></label>
              <textarea class="form-control" id="add-kb-desc" rows="4" placeholder="Masukkan deskripsi artikel yang terperinci" name="kb_desc" required></textarea>
              <div class="invalid-feedback"></div>
            </div>
          </div>

          <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label" for="add-kb-file">Muat Naik Fail PDF</label>
              <div class="file-upload-wrapper">
                <div id="pdf-drop-zone" class="file-drop-zone border-2 border-dashed border-secondary rounded-3 p-4 text-center position-relative">
                  <input type="file" id="add-kb-file" name="pdf_files[]" accept=".pdf" multiple class="file-input position-absolute w-100 h-100 opacity-0" style="pointer-events: none; z-index: -1;">
                  <div class="drop-zone-content">
                    <i class="bx bx-file-find display-4 text-muted mb-3"></i>
                    <h6 class="text-muted mb-2">Seret & Letak fail PDF di sini</h6>
                    <p class="text-muted mb-2">atau <span class="text-primary fw-semibold" style="cursor: pointer;">telusuri (browse) fail</span></p>
                    <small class="text-muted">Hanya fail PDF dibenarkan (Maks: 2MB setiap fail, maksimum 5 fail)</small>
                  </div>
                </div>
                <div id="files-preview" class="files-preview mt-3"></div>
                <div class="invalid-feedback"></div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label" for="add-kb-tag">Tags</label>
              <input type="text" class="form-control" id="add-kb-tag" placeholder="Enter tags separated by commas" name="kb_tag" />
              <small class="text-muted">Guna koma(,) untuk memisahkan pelbagai tag (contoh: tag1, tag2, tag3)</small>
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label" for="add-kb-status">Status <span class="text-danger">*</span></label>
              <select id="add-kb-status" class="form-select" name="kb_status" required>
                <option value="aktif">Aktif</option>
                <option value="tidak aktif">Tidak Aktif</option>
              </select>
              <div class="invalid-feedback"></div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">BATAL</button>
        <button type="button" id="btnAddKnowledgeBase" class="btn btn-primary">
          <i class="bx bx-plus me-2"></i>TAMBAH ARTIKEL
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.file-drop-zone {
  transition: all 0.3s ease;
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.file-drop-zone.dragover {
  border-color: #007bff !important;
  background-color: rgba(0, 123, 255, 0.1);
}

.file-drop-zone.error {
  border-color: #dc3545 !important;
  background-color: rgba(220, 53, 69, 0.1);
}

.file-input {
  cursor: pointer !important;
  pointer-events: none;
}

.files-preview {
  max-height: 300px;
  overflow-y: auto;
}

.file-item {
  margin-bottom: 10px;
  padding: 12px;
  background-color: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.file-item:last-child {
  margin-bottom: 0;
}

.drop-zone-content {
  pointer-events: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const dropZone = document.getElementById('pdf-drop-zone');
  const fileInput = document.getElementById('add-kb-file');
  const filesPreview = document.getElementById('files-preview');
  const dropZoneContent = dropZone.querySelector('.drop-zone-content');

  // Maximum file size (2MB) and maximum number of files (5)
  const maxFileSize = 2 * 1024 * 1024;
  const maxFiles = 5;
  let selectedFiles = [];

  // Make selectedFiles available globally for form submission
  window.selectedFiles = selectedFiles;

  // Check if toastr is available, if not provide console fallback
  const showToastr = {
    success: function(message) {
      if (typeof toastr !== 'undefined') {
        toastr.success(message);
      } else {
        console.log('SUCCESS:', message);
      }
    },
    error: function(message) {
      if (typeof toastr !== 'undefined') {
        toastr.error(message);
      } else {
        console.log('ERROR:', message);
      }
    },
    warning: function(message) {
      if (typeof toastr !== 'undefined') {
        toastr.warning(message);
      } else {
        console.log('WARNING:', message);
      }
    },
    info: function(message) {
      if (typeof toastr !== 'undefined') {
        toastr.info(message);
      } else {
        console.log('INFO:', message);
      }
    }
  };

  // Click on drop zone to open file browser (file input is pointer-events:none)
  dropZone.addEventListener('click', function() {
    fileInput.click();
  });

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
  fileInput.addEventListener('change', function(e) {
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
    if (selectedFiles.length + files.length > maxFiles) {
      const message = `Maksimum ${maxFiles} fail dibenarkan. Anda sudah mempunyai ${selectedFiles.length} fail dipilih.`;
      showError(message);
      showToastr.error(message);
      return;
    }

    // Validate each file
    files.forEach(file => {
      // Check if file is already selected
      const isDuplicate = selectedFiles.some(selectedFile =>
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

      // Validate file type (check both MIME type and file extension)
      const isValidPDF = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
      if (!isValidPDF) {
        errors.push(`"${file.name}" bukan fail PDF`);
        showToastr.error(`"${file.name}" bukan fail PDF. Hanya fail PDF dibenarkan.`);
        hasErrors = true;
        return;
      }

      // Validate file size (2MB = 2 * 1024 * 1024 bytes)
      if (file.size > maxFileSize) {
        const fileSize = formatFileSize(file.size);
        errors.push(`"${file.name}" (${fileSize}) melebihi saiz maksimum 2MB`);
        showToastr.error(`"${file.name}" (${fileSize}) melebihi saiz maksimum 2MB. Sila pilih fail yang lebih kecil.`);
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
      if (selectedFiles.length + filesToAdd.length > maxFiles) {
        const canAdd = maxFiles - selectedFiles.length;
        const filesToAddLimited = filesToAdd.slice(0, canAdd);
        const filesRejected = filesToAdd.slice(canAdd);

        // Add only the files that fit within the limit
        filesToAddLimited.forEach(file => {
          selectedFiles.push(file);
        });
        window.selectedFiles = selectedFiles;

        // Show message for added files
        if (filesToAddLimited.length === 1) {
          showToastr.success(`Fail "${filesToAddLimited[0].name}" berjaya ditambah`);
        } else {
          showToastr.success(`${filesToAddLimited.length} fail berjaya ditambah`);
        }

        // Show warning for rejected files
        if (filesRejected.length > 0) {
          showToastr.warning(`${filesRejected.length} fail tidak ditambah kerana melebihi had maksimum ${maxFiles} fail`);
        }
      } else {
        // Add all files as they don't exceed the limit
      filesToAdd.forEach(file => {
        selectedFiles.push(file);
      });
      window.selectedFiles = selectedFiles;        // Show success message
        if (filesToAdd.length === 1) {
          showToastr.success(`Fail "${filesToAdd[0].name}" berjaya ditambah`);
        } else {
          showToastr.success(`${filesToAdd.length} fail berjaya ditambah`);
        }
      }

      // Update file input and preview
      updateFileInput();
      updateFilesPreview();
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
    selectedFiles.forEach(file => {
      dt.items.add(file);
    });
    fileInput.files = dt.files;
  }

  function updateFilesPreview() {
    filesPreview.innerHTML = '';

    if (selectedFiles.length === 0) {
      return;
    }

    // Add header showing file count
    if (selectedFiles.length > 0) {
      const headerDiv = document.createElement('div');
      headerDiv.className = 'mb-2 d-flex justify-content-between align-items-center';
      headerDiv.innerHTML = `
        <small class="text-muted fw-semibold">Fail dipilih (${selectedFiles.length}/${maxFiles})</small>
        <button type="button" class="btn btn-sm btn-outline-secondary remove-all-files" onclick="removeAllFiles()">
          <i class="bx bx-x me-1"></i>Buang Semua
        </button>
      `;
      filesPreview.appendChild(headerDiv);
    }

    selectedFiles.forEach((file, index) => {
      const fileItem = createFilePreviewItem(file, index);
      filesPreview.appendChild(fileItem);
    });
  }

  function createFilePreviewItem(file, index) {
    const fileItem = document.createElement('div');
    fileItem.className = 'file-item';

    fileItem.innerHTML = `
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <i class="bx bxs-file-pdf text-danger me-3 fs-4"></i>
          <div>
            <div class="file-name fw-semibold">${file.name}</div>
            <small class="file-size text-muted">${formatFileSize(file.size)}</small>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger remove-file" data-index="${index}">
          <i class="bx bx-x"></i>
        </button>
      </div>
    `;

    // Add remove file functionality
    const removeBtn = fileItem.querySelector('.remove-file');
    removeBtn.addEventListener('click', function() {
      removeFile(index);
    });

    return fileItem;
  }

  function removeFile(index) {
    const fileName = selectedFiles[index].name;
    selectedFiles.splice(index, 1);
    window.selectedFiles = selectedFiles;
    updateFileInput();
    updateFilesPreview();
    clearValidationError();

    showToastr.info(`Fail "${fileName}" telah dibuang`);
  }

  function removeAllFiles() {
    const fileCount = selectedFiles.length;
    selectedFiles = [];
    window.selectedFiles = selectedFiles;
    fileInput.value = '';
    filesPreview.innerHTML = '';
    clearValidationError();

    if (fileCount > 0) {
      showToastr.info(`Semua fail (${fileCount}) telah dibuang`);
    }
  }

  // Make removeAllFiles globally accessible
  window.removeAllFiles = removeAllFiles;

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

  // Reset modal when it's closed
  document.getElementById('knowledgeBaseModal').addEventListener('hidden.bs.modal', function() {
    removeAllFiles();
    dropZone.classList.remove('dragover', 'error');
    clearValidationError();

    // Reset form
    document.getElementById('addNewKnowledgeBaseForm').reset();
  });

  // Also handle modal show event to ensure clean state
  document.getElementById('knowledgeBaseModal').addEventListener('show.bs.modal', function() {
    removeAllFiles();
    dropZone.classList.remove('dragover', 'error');
    clearValidationError();
  });
});
</script>
