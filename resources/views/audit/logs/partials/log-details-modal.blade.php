<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logDetailsModalLabel">
                    <i class="bx bx-file-blank me-2"></i>Log Details - <span id="logFileName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="logContentWrapper">
                    <div class="text-center py-5" id="logLoading">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Memuat log content...</p>
                    </div>
                    <div id="logContent" style="display: none;">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="logSearch" placeholder="Cari dalam log...">
                        </div>
                        <pre id="logText" style="max-height: 500px; overflow-y: auto; background-color: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; font-size: 0.85rem;"></pre>
                    </div>
                    <div id="logError" style="display: none;" class="alert alert-danger">
                        <span id="logErrorMessage"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="downloadLogBtn" class="btn btn-success">Download</a>
            </div>
        </div>
    </div>
</div>
<script>
        // Define function immediately so onclick works
        window.logRoutes = {
            baseUrl: "{{ url('audit/logs') }}"
        };
        
        window.showLogDetails = function(date, filename) {
            console.log('showLogDetails called with:', date, filename);
            console.log('AJAX URL will be:', window.logRoutes.baseUrl + '/view/' + date);
            
            $('#logLoading').show();
            $('#logContent').hide();
            $('#logError').hide();
            $('#logFileName').text(filename);
            $('#downloadLogBtn').attr('href', window.logRoutes.baseUrl + '/download/' + date);
            
            var modalEl = document.getElementById('logDetailsModal');
            console.log('Modal element:', modalEl);
            
            if (typeof bootstrap === 'undefined') {
                console.error('Bootstrap is not loaded!');
                alert('Bootstrap is not loaded. Please refresh the page.');
                return;
            }
            
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
            
            $.ajax({
                url: window.logRoutes.baseUrl + '/view/' + date,
                method: 'GET',
                success: function(response) {
                    console.log('Log loaded successfully');
                    $('#logLoading').hide();
                    $('#logContent').show();
                    $('#logText').text(response);
                    
                    // Store original content in window scope
                    window.originalLogContent = response;
                    console.log('Original content stored, length:', response.length);
                    
                    // Setup search handler immediately after content loaded
                    setupLogSearch();
                },
                error: function(xhr, status, error) {
                    console.error('Error loading log:', xhr, status, error);
                    console.error('Status code:', xhr.status);
                    console.error('Response text:', xhr.responseText);
                    $('#logLoading').hide();
                    $('#logError').show();
                    
                    var errorMsg = 'Gagal memuat log: ';
                    if (xhr.status === 404) {
                        errorMsg += 'Log file tidak dijumpai';
                    } else if (xhr.status === 403) {
                        errorMsg += 'Akses tidak dibenarkan';
                    } else if (xhr.responseJSON?.message) {
                        errorMsg += xhr.responseJSON.message;
                    } else {
                        errorMsg += error + ' (Status: ' + xhr.status + ')';
                    }
                    
                    $('#logErrorMessage').text(errorMsg);
                }
            });
        };
        
        // Setup search functionality
        function setupLogSearch() {
            console.log('Setting up log search');
            
            // Remove any existing handlers first
            $('#logSearch').off('keyup');
            
            // Attach new handler
            $('#logSearch').on('keyup', function() {
                var searchTerm = $(this).val();
                console.log('Searching for:', searchTerm);
                
                if (!window.originalLogContent) {
                    console.error('Original content not available');
                    return;
                }
                
                if (searchTerm === '') {
                    // Reset to original
                    $('#logText').text(window.originalLogContent);
                    console.log('Reset to original');
                } else {
                    // Perform search with case-insensitive matching
                    try {
                        var regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                        var highlighted = window.originalLogContent.replace(regex, '<mark style="background-color: #ffeb3b; color: #000; padding: 2px 4px; font-weight: bold;">$1</mark>');
                        $('#logText').html(highlighted);
                        
                        // Count matches
                        var matches = window.originalLogContent.match(regex);
                        console.log('Found', matches ? matches.length : 0, 'matches');
                    } catch(e) {
                        console.error('Search error:', e);
                    }
                }
            });
            
            console.log('Search handler attached to #logSearch');
        }
        
        // Reset when modal closes
        // $('#logDetailsModal').on('hidden.bs.modal', function () {
        //     window.originalLogContent = '';
        //     $('#logSearch').val('').off('keyup');
        //     console.log('Modal closed, search reset');
        // });
    </script>