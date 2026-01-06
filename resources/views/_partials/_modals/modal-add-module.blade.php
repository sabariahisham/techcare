<!-- Add Permission Modal -->
<div class="modal fade" id="addModuleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <h4 class="mb-2">Add New Module</h4>
          <p>Modules you may use and assign to any permission.</p>
        </div>
        <form id="addModuleForm" class="row" onsubmit="return false">
          <div class="col-12 mb-4">
            <label class="form-label" for="modalModuleName">Module Name</label>
            <input type="text" id="modalModuleName" name="modalModuleName" class="form-control" placeholder="Module Name" autofocus />
          </div>
          <!-- <div class="col-12 mb-2">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="corePermission" />
              <label class="form-check-label" for="corePermission">
                Set as core permission
              </label>
            </div>
          </div> -->
          <div class="col-12 text-center demo-vertical-spacing">
            <button type="submit" class="btn btn-primary me-4">Create Module</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Discard</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<!--/ Add Permission Modal -->
