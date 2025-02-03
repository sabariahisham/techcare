<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-simple modal-dialog-centered modal-add-new-role">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <h4 class="role-title mb-2">Add New Role</h4>
          <p>Set role permissions</p>
        </div>
        <!-- Add role form -->
        <form id="addRoleForm" class="row g-6">
          @csrf
          <div class="col-12">
            <label class="form-label" for="modalRoleName">Role Name</label>
            <input type="text" id="modalRoleName" name="modalRoleName" class="form-control" placeholder="Enter a role name" tabindex="-1" />
          </div>
          <div class="col-12">
            <h5 class="mb-6">Role Permissions</h5>
            <!-- Permission table -->
            <!-- Replace the existing permission table with: -->
            <div class="table-responsive">
                <table class="table table-flush-spacing mb-0">
                    <tbody>
                      <tr>
                        <td class="text-nowrap fw-medium text-heading">
                          Administrator Access <i class="bx bx-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" title="Allows a full access to the system"></i>
                        </td>

                        <td>
                          <div class="d-flex justify-content">

                            <div class="form-check mb-0 me-4 me-lg-12">
                              <!-- <input class="form-check-input" type="checkbox" id="selectAll" name="permissions[]"/> -->
                              <input class="form-check-input" type="checkbox" id="selectAll"/>

                              <label class="form-check-label" for="selectAll">
                                Select all
                              </label>
                            </div>
                          </div>

                        </td>
                      </tr>
                        @foreach($permissions as $module => $modulePermissions)
                        <tr>
                            <td class="text-nowrap fw-medium text-heading">
                                {{ ucfirst($module) }}
                            </td>
                            <td>
                                <div class="d-flex justify-content-end">

                                    @foreach($modulePermissions as $permission)
                                    <div class="form-check mb-0 me-4 me-lg-12">
                                        <input class="form-check-input" type="checkbox"
                                            id="perm_{{ $permission->name }}"
                                            name="permissions[]"
                                            value="{{ $permission->name }}"/>
                                        <label class="form-check-label" for="perm_{{ $permission->name }}">
                                            {{ ucfirst(substr(strrchr($permission->name, "."), 1)) }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Permission table -->
          </div>
          <div class="col-12 text-center">
            <button type="submit" class="btn btn-primary me-3">Submit</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
          </div>
        </form>
        <!--/ Add role form -->
      </div>
    </div>
  </div>
</div>
<!--/ Add Role Modal -->
