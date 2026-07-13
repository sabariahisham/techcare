/**
 * Senarai Aduan Selesai DataTable
 */

'use strict';

import Quill from 'quill';
import 'quill/dist/quill.snow.css';

// ─── Utility Functions ──────────────────────────────────────────────────────
function stripHtmlTags(html) {
  if (!html) return '';
  const div = document.createElement('div');
  div.innerHTML = html;
  return div.textContent || div.innerText || '';
}

// ─── Quill Editor State ─────────────────────────────────────────────────────
let fromAduanQuillEditor = null;

// ─── Add From Aduan: State ──────────────────────────────────────────────────
let fromAduanCatatanPlainText = [];

// ─── Add From Aduan: File Upload State ────────────────────────────────────────
const FROM_ADUAN_MAX_FILES = 5;
const FROM_ADUAN_MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB
const fromAduanSelectedFiles = [];

function fromAduanFormatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024,
    sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function fromAduanUpdatePreview() {
  const filesPreview = document.getElementById('from-aduan-files-preview');
  if (!filesPreview) return;
  filesPreview.innerHTML = '';
  if (fromAduanSelectedFiles.length === 0) return;

  const header = document.createElement('div');
  header.className = 'mb-2 d-flex justify-content-between align-items-center';
  header.innerHTML = `
    <small class="text-muted fw-semibold">Fail dipilih (${fromAduanSelectedFiles.length}/${FROM_ADUAN_MAX_FILES})</small>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="from-aduan-remove-all-files">
      <i class="bx bx-x me-1"></i>Buang Semua
    </button>`;
  filesPreview.appendChild(header);
  header.querySelector('#from-aduan-remove-all-files').addEventListener('click', fromAduanRemoveAllFiles);

  fromAduanSelectedFiles.forEach((file, index) => {
    const item = document.createElement('div');
    item.className = 'file-item';
    item.innerHTML = `
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <i class="bx bxs-file-pdf text-danger me-3 fs-4"></i>
          <div>
            <div class="file-name fw-semibold">${file.name}</div>
            <small class="file-size text-muted">${fromAduanFormatFileSize(file.size)}</small>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger remove-from-aduan-file" data-idx="${index}">
          <i class="bx bx-x"></i>
        </button>
      </div>`;
    item.querySelector('.remove-from-aduan-file').addEventListener('click', function () {
      const removedName = fromAduanSelectedFiles[parseInt(this.dataset.idx)].name;
      fromAduanSelectedFiles.splice(parseInt(this.dataset.idx), 1);
      fromAduanUpdatePreview();
      toastr.info(`Fail "${removedName}" telah dibuang`);
    });
    filesPreview.appendChild(item);
  });
}

function fromAduanRemoveAllFiles() {
  const fileInput = document.getElementById('from-aduan-kb-file');
  const filesPreview = document.getElementById('from-aduan-files-preview');
  const dropZone = document.getElementById('from-aduan-pdf-drop-zone');
  const count = fromAduanSelectedFiles.length;
  fromAduanSelectedFiles.length = 0;
  if (fileInput) fileInput.value = '';
  if (filesPreview) filesPreview.innerHTML = '';
  if (dropZone) dropZone.classList.remove('dragover', 'error');
  if (count > 0) toastr.info(`Semua fail (${count}) telah dibuang`);
}

function initFromAduanFileUpload() {
  const dropZone = document.getElementById('from-aduan-pdf-drop-zone');
  const fileInput = document.getElementById('from-aduan-kb-file');
  const filesPreview = document.getElementById('from-aduan-files-preview');
  if (!dropZone || !fileInput || !filesPreview) return;

  // Reset state every open
  fromAduanSelectedFiles.length = 0;
  filesPreview.innerHTML = '';
  fileInput.value = '';
  dropZone.classList.remove('dragover', 'error');

  if (dropZone._fromAduanInitialized) return;
  dropZone._fromAduanInitialized = true;

  function handleFiles(fileList) {
    if (!fileList || fileList.length === 0) return;
    const files = Array.from(fileList);
    dropZone.classList.remove('error');

    if (fromAduanSelectedFiles.length + files.length > FROM_ADUAN_MAX_FILES) {
      toastr.error(`Maksimum ${FROM_ADUAN_MAX_FILES} fail dibenarkan. Anda sudah mempunyai ${fromAduanSelectedFiles.length} fail.`);
      return;
    }
    const toAdd = [];
    files.forEach(file => {
      if (fromAduanSelectedFiles.some(f => f.name === file.name && f.size === file.size)) {
        toastr.warning(`Fail "${file.name}" sudah dipilih.`);
        return;
      }
      if (!(file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'))) {
        toastr.error(`"${file.name}" bukan fail PDF.`);
        return;
      }
      if (file.size > FROM_ADUAN_MAX_FILE_SIZE) {
        toastr.error(`"${file.name}" melebihi saiz maksimum 2MB.`);
        return;
      }
      toAdd.push(file);
    });
    if (toAdd.length > 0) {
      fromAduanSelectedFiles.push(...toAdd);
      fromAduanUpdatePreview();
      toastr.success(toAdd.length === 1 ? `1 fail berjaya ditambah` : `${toAdd.length} fail berjaya ditambah`);
    }
    fileInput.value = '';
  }

  dropZone.addEventListener('click', function (e) {
    if (e.target !== fileInput) fileInput.click();
  });
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt =>
    dropZone.addEventListener(evt, e => {
      e.preventDefault();
      e.stopPropagation();
    }, false)
  );
  ['dragenter', 'dragover'].forEach(evt =>
    dropZone.addEventListener(evt, () => dropZone.classList.add('dragover'), false)
  );
  ['dragleave', 'drop'].forEach(evt =>
    dropZone.addEventListener(evt, () => dropZone.classList.remove('dragover', 'error'), false)
  );
  dropZone.addEventListener('drop', e => handleFiles(e.dataTransfer.files), false);
  fileInput.addEventListener('change', function () {
    if (this.files && this.files.length > 0) handleFiles(this.files);
  });
}

// Handle catatan tindakan checkbox → copy to Deskripsi (Quill editor)
function updateFromAduanDesc() {
  if (!$('#checkbox-salin-catatan').is(':checked')) {
    // Clear Quill editor
    if (fromAduanQuillEditor) {
      fromAduanQuillEditor.setContents([{
        insert: '\n'
      }]);
    }
    return;
  }

  const selectedLines = [];
  $('.catatan-entry-check:checked').each(function () {
    const idx = parseInt($(this).data('index'));
    if (Array.isArray(fromAduanCatatanPlainText) && fromAduanCatatanPlainText[idx] !== undefined) {
      selectedLines.push(fromAduanCatatanPlainText[idx]);
    }
  });

  if (selectedLines.length === 0) {
    if (fromAduanQuillEditor) {
      fromAduanQuillEditor.setContents([{
        insert: '\n'
      }]);
    }
    return;
  }

  // Format selected catatan as HTML for Quill
  const plainText = selectedLines.join('\n\n');
  const htmlContent = plainText
    .split('\n\n')
    .map(para => `<p>${para.replace(/\n/g, '<br>')}</p>`)
    .join('');

  if (fromAduanQuillEditor) {
    // Set HTML content in Quill
    fromAduanQuillEditor.root.innerHTML = htmlContent;
  }
}

$(document).on('change', '#checkbox-salin-catatan', function () {
  updateFromAduanDesc();
});

$(document).on('change', '#checkbox-pilih-semua', function () {
  const isChecked = $(this).is(':checked');
  $('.catatan-entry-check').prop('checked', isChecked);
  updateFromAduanDesc();
});

$(document).on('change', '.catatan-entry-check', function () {
  const total = $('.catatan-entry-check').length;
  const checked = $('.catatan-entry-check:checked').length;
  const pilihSemua = document.getElementById('checkbox-pilih-semua');
  if (pilihSemua) {
    pilihSemua.indeterminate = checked > 0 && checked < total;
    pilihSemua.checked = checked === total;
  }
  updateFromAduanDesc();
});

// Initialize Quill editor for from-aduan modal
function initializeFromAduanQuillEditor() {
  if (fromAduanQuillEditor) {
    fromAduanQuillEditor.enable();
    fromAduanQuillEditor.setContents([{
      insert: '\n'
    }]);
    return;
  }

  const editorElement = document.getElementById('from-aduan-editor');
  if (!editorElement) return;

  fromAduanQuillEditor = new Quill('#from-aduan-editor', {
    theme: 'snow',
    placeholder: 'Masukkan deskripsi artikel yang terperinci',
    modules: {
      toolbar: [
        [{
          header: [1, 2, 3, false]
        }],
        ['bold', 'italic', 'underline', 'strike'],
        [{
          list: 'ordered'
        }, {
          list: 'bullet'
        }],
        [{
          color: []
        }, {
          background: []
        }],
        ['blockquote', 'code-block'],
        ['link'],
        ['clean'],
      ],
    },
  });
}

// Handle modal shown event to initialize Quill
$(document).on('shown.bs.modal', '#addFromAduanModal', function () {
  setTimeout(() => {
    initializeFromAduanQuillEditor();
  }, 100);
});

// Handle "Tambah ke Knowledge Base" button in datatable
$(document).on('click', '.btn-add-to-kb', function () {
  const uuid = $(this).data('uuid');
  if (!uuid) return;

  toastr.info('Memuatkan maklumat tiket...', '', {
    timeOut: 1500
  });

  $.ajax({
    url: `/knowledgebase/aduan-selesai/${uuid}/ticket-details`,
    type: 'GET',
    success: function (response) {
      if (!response.success) {
        toastr.error('Gagal memuatkan maklumat tiket.');
        return;
      }
      const d = response.data;

      // Populate reference info
      $('#ref-no-log-tiket').text(d.no_log_tiket);
      $('#ref-nama-pengadu').text(d.nama_pengadu);
      $('#ref-tarikh-aduan').text(d.tarikh_aduan);

      // Populate hidden ticket UUID
      $('#from-aduan-ticket-uuid').val(d.uuid);

      // Clear form fields — user fills in manually
      $('#from-aduan-kb-title').val('');
      $('#from-aduan-kb-desc').val('');
      $('#from-aduan-kb-tag').val('');
      $('#from-aduan-kb-status').val('aktif');

      // Set locked category and subcategory
      $('#from-aduan-kb-category-display').text(d.cat_name || '-');
      $('#from-aduan-hidden-cat-id').val(d.cat_id);
      $('#from-aduan-kb-subcategory-display').text(d.subcat_name || '-');
      $('#from-aduan-hidden-subcat-id').val(d.subcat_id);

      // Show catatan tindakan if available
      const comments = Array.isArray(d.catatan_tindakan) ? d.catatan_tindakan : [];
      if (comments.length > 0) {
        let html = '';
        const plainLines = [];
        comments.forEach(function (c, i) {
          const rolesBadges = c.roles && c.roles.length ?
            c.roles.map(r => `<span class="badge bg-label-primary me-1" style="font-size:0.7rem;">${r}</span>`).join('') :
            '<span class="badge bg-label-secondary" style="font-size:0.7rem;">Tiada Peranan</span>';
          html += `
            <div class="${i > 0 ? 'border-top pt-2 mt-2' : ''}">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <div class="d-flex align-items-center gap-2">
                  <input class="form-check-input catatan-entry-check" type="checkbox" id="catatan-check-${i}" data-index="${i}">
                  <small class="text-muted">Catatan Tindakan</small>
                </div>
                <div class="d-flex align-items-center gap-1 flex-wrap justify-content-end">
                  <span class="fw-semibold small">${c.nama}</span>
                  <span>${rolesBadges}</span>
                  <small class="text-muted text-nowrap ms-1">${c.tarikh} ${c.masa}</small>
                </div>
              </div>
              <div class="border rounded p-2 bg-white" style="max-height: 80px; overflow-y: auto; white-space: pre-wrap; font-size: 0.875rem;">${c.teks}</div>
            </div>`;
          plainLines.push(`Pegawai      : ${c.nama}\nPeranan      : ${c.roles && c.roles.length ? c.roles.join(', ') : 'Tiada Peranan'}\nTarikh & Masa: ${c.tarikh} ${c.masa}\nCatatan      :\n${c.teks}`);
        });
        $('#ref-catatan-tindakan').html(html);
        fromAduanCatatanPlainText = plainLines;
        $('#ref-catatan-tindakan-wrapper').show();
      } else {
        $('#ref-catatan-tindakan-wrapper').hide();
      }
      $('#checkbox-salin-catatan').prop('checked', false);
      $('#checkbox-pilih-semua').prop('checked', false).prop('indeterminate', false);

      // Init file upload
      initFromAduanFileUpload();

      // Clear validation states
      $('#addFromAduanForm').find('.is-invalid').removeClass('is-invalid');
      $('#addFromAduanForm').find('.invalid-feedback').hide();

      // Show modal
      bootstrap.Modal.getOrCreateInstance(document.getElementById('addFromAduanModal')).show();
    },
    error: function () {
      toastr.error('Gagal memuatkan maklumat tiket. Sila cuba lagi.');
    }
  });
});

// Handle submit button in the from-aduan modal
$(document).on('click', '#btnAddFromAduan', function () {
  const form = document.getElementById('addFromAduanForm');
  if (!form) return;

  // Capture Quill content to hidden field
  if (fromAduanQuillEditor) {
    const quillContent = fromAduanQuillEditor.root.innerHTML;
    const plainText = fromAduanQuillEditor.getText().trim();

    // Check if editor has content (not just empty or whitespace)
    if (!plainText) {
      $('#from-aduan-kb-desc').val('');
      $('#from-aduan-kb-desc').addClass('is-invalid');
      $('#from-aduan-kb-desc').siblings('.invalid-feedback').text('Medan ini diperlukan.').show();
      toastr.error('Sila masukkan deskripsi artikel.', 'Ralat!');
      return;
    }

    $('#from-aduan-kb-desc').val(quillContent);
  }

  // Basic validation
  let isValid = true;
  $(form).find('input[required], select[required]').each(function () {
    if (!$(this).val()) {
      $(this).addClass('is-invalid');
      $(this).siblings('.invalid-feedback').text('Medan ini diperlukan.').show();
      isValid = false;
    } else {
      $(this).removeClass('is-invalid');
      $(this).siblings('.invalid-feedback').hide();
    }
  });
  // Validate locked hidden fields
  if (!$('#from-aduan-hidden-cat-id').val()) {
    toastr.error('Kategori tidak ditemui. Sila buka semula tiket.', 'Ralat!');
    isValid = false;
  }
  if (!$('#from-aduan-hidden-subcat-id').val()) {
    toastr.error('Sub Kategori tidak ditemui. Sila buka semula tiket.', 'Ralat!');
    isValid = false;
  }

  if (!isValid) {
    toastr.error('Sila perbaiki ralat dalam borang sebelum menyimpan.', 'Ralat Validasi!');
    return;
  }

  const submitBtn = $(this);
  const originalText = submitBtn.html();
  submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Menyimpan...');

  const formData = new FormData(form);
  fromAduanSelectedFiles.forEach(file => formData.append('pdf_files[]', file));

  $.ajax({
    url: '/knowledgebase',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    success: function (response) {
      if (response.success) {
        toastr.success('Artikel Knowledge Base berjaya ditambah.', 'Berjaya!', {
          closeButton: true,
          timeOut: 3000,
          progressBar: true,
          positionClass: 'toast-top-right'
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addFromAduanModal')).hide();
        form.reset();
        // Clear Quill editor
        if (fromAduanQuillEditor) {
          fromAduanQuillEditor.setContents([{
            insert: '\n'
          }]);
        }
        fromAduanRemoveAllFiles();
        $('#from-aduan-kb-category-display').text('-');
        $('#from-aduan-kb-subcategory-display').text('-');
        fromAduanCatatanPlainText = [];
        $('#ref-catatan-tindakan-wrapper').hide();
        $('#checkbox-salin-catatan').prop('checked', false);
        $('#checkbox-pilih-semua').prop('checked', false).prop('indeterminate', false);
        setTimeout(function () {
          if (typeof window.dt_knowledgebase !== 'undefined') {
            window.dt_knowledgebase.ajax.reload(null, false);
          } else if ($.fn.DataTable.isDataTable('.datatables-knowledgebase')) {
            $('.datatables-knowledgebase').DataTable().ajax.reload(null, false);
          }
        }, 500);
      } else {
        toastr.error(response.message || 'Gagal menambah artikel.', 'Ralat!');
      }
    },
    error: function (xhr) {
      var responseJson = xhr.responseJSON || {};
      var errors = responseJson.errors;
      var generalMessage = responseJson.message;
      if (errors) {
        if (generalMessage) toastr.error(generalMessage, 'Ralat!');
        Object.keys(errors).forEach(function (key) {
          const fieldEl = $(`#addFromAduanForm [name="${key}"]`);
          if (fieldEl.length) {
            fieldEl.addClass('is-invalid');
            fieldEl.siblings('.invalid-feedback').text(errors[key][0]).show();
          }
        });
      } else {
        toastr.error(generalMessage || 'Ralat berlaku semasa menyimpan artikel.', 'Ralat!');
      }
    },
    complete: function () {
      submitBtn.prop('disabled', false).html(originalText);
    }
  });
});

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
          data: 'tajuk_aduan',
          name: 'tajuk_aduan'
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
          data: 'pegawai_tugasan',
          name: 'pegawai_tugasan'
        },
        {
          data: 'keterangan_aduan',
          name: 'keterangan_aduan'
        },
        {
          data: 'catatan_tindakan',
          name: 'catatan_tindakan',
          orderable: false
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
          orderable: false,
          searchable: false
        },
        {
          targets: 2, // No. Log Tiket
          className: 'td-wrap-compact'
        },
        {
          targets: 3, // Tajuk Aduan
          className: 'td-wrap-compact',
          render: function (data, type, row) {
            if (!data) return '-';
            const plainText = stripHtmlTags(data);
            return plainText ? '<div class="wrapped-cell">' + plainText + '</div>' : '-';
          }
        },
        {
          targets: 4, // Tarikh Aduan
          className: 'td-wrap-compact'
        },
        {
          targets: 5, // Nama Pengadu
          className: 'td-wrap-compact'
        },
        {
          targets: 6, // Kategori
          className: 'td-wrap-compact'
        },
        {
          targets: 7, // Sub Kategori
          className: 'td-wrap-compact'
        },
        {
          targets: 8, // Pegawai Tugasan
          className: 'td-wrap-compact'
        },
        {
          targets: 9, // Keterangan Aduan
          className: 'td-wrap-compact',
          visible: false,
          render: function (data, type, row) {
            if (!data) return '-';
            const plainText = stripHtmlTags(data);
            return plainText ? '<div class="wrapped-cell">' + plainText + '</div>' : '-';
          }
        },
        {
          targets: 10, // Catatan Tindakan
          className: 'td-wrap-compact',
          visible: false,
          render: function (data, type, row) {
            if (!data) return '-';
            const plainText = stripHtmlTags(data);
            return plainText ? '<div class="wrapped-cell">' + plainText + '</div>' : '-';
          }
        },
        {
          targets: 11,
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            return '<div class="d-flex gap-2">' +
              '<a href="/tickets/' + row.uuid + '" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat Tiket">' +
              '<i class="bx bx-show"></i>' +
              '</a>' +
              '<button type="button" class="btn btn-sm btn-icon btn-outline-primary btn-add-to-kb" data-uuid="' + row.uuid + '" data-no-log="' + row.no_log_tiket + '" data-bs-toggle="tooltip" data-bs-placement="top" title="Tambah ke Knowledge Base">' +
              '<i class="bx bx-plus"></i>' +
              '</button>' +
              '</div>';
          }
        }
      ],
      autoWidth: true,
      scrollX: true,
      pagingType: 'full_numbers',
      language: {
        paginate: {
          first: 'Pertama',
          last: 'Terakhir',
          next: 'Seterusnya',
          previous: 'Sebelumnya'
        },
        lengthMenu: 'Papar _MENU_ baris',
        info: 'Memaparkan _START_ hingga _END_ daripada _TOTAL_ entri',
        infoEmpty: 'Tiada data tersedia',
        search: 'Carian:'
      },
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
      responsive: false,
      drawCallback: function () {
        // Re-initialize tooltips after each draw
        $('[data-bs-toggle="tooltip"]').each(function () {
          bootstrap.Tooltip.getOrCreateInstance(this);
        });
      }
    });
  }
});
