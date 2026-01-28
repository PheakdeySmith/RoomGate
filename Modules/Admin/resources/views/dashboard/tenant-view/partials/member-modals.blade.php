<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-primary rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-user-plus icon-md"></i>
          </span>
          <h4 class="mb-1">Add Tenant Member</h4>
          <p class="text-body-secondary mb-0">Invite a new user to this tenant.</p>
        </div>
        <form class="row g-3" method="POST" action="{{ route('admin.tenants.members.store', $tenant) }}">
          @csrf
          <div class="col-12">
            <label class="form-label" for="memberName">Full Name</label>
            <input type="text" id="memberName" name="name" class="form-control" placeholder="Jane Doe" />
          </div>
          <div class="col-12">
            <label class="form-label" for="memberEmail">Email</label>
            <input type="email" id="memberEmail" name="email" class="form-control" placeholder="jane@example.com" />
          </div>
          <div class="col-12">
            <label class="form-label" for="memberPassword">Password</label>
            <input type="password" id="memberPassword" name="password" class="form-control" placeholder="Minimum 8 characters" />
          </div>
          <div class="col-6">
            <label class="form-label" for="memberRole">Role</label>
            <select id="memberRole" name="role" class="form-select">
              <option value="owner">Owner</option>
              <option value="admin">Admin</option>
              <option value="staff">Staff</option>
              <option value="tenant" selected>Tenant</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label" for="memberStatus">Status</label>
            <select id="memberStatus" name="status" class="form-select">
              <option value="active">Active</option>
              <option value="invited">Invited</option>
              <option value="disabled">Disabled</option>
            </select>
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Add Member</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-warning rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-edit icon-md"></i>
          </span>
          <h4 class="mb-1">Edit Tenant Member</h4>
          <p class="text-body-secondary mb-0">Update member role and status.</p>
        </div>
        <form class="row g-3" method="POST" id="editMemberForm">
          @csrf
          @method('PATCH')
          <div class="col-12">
            <label class="form-label" for="editMemberName">Full Name</label>
            <input type="text" id="editMemberName" name="name" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editMemberEmail">Email</label>
            <input type="email" id="editMemberEmail" name="email" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editMemberPassword">Password</label>
            <input type="password" id="editMemberPassword" name="password" class="form-control" placeholder="Leave blank to keep" />
          </div>
          <div class="col-6">
            <label class="form-label" for="editMemberRole">Role</label>
            <select id="editMemberRole" name="role" class="form-select">
              <option value="owner">Owner</option>
              <option value="admin">Admin</option>
              <option value="staff">Staff</option>
              <option value="tenant">Tenant</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label" for="editMemberStatus">Status</label>
            <select id="editMemberStatus" name="status" class="form-select">
              <option value="active">Active</option>
              <option value="invited">Invited</option>
              <option value="disabled">Disabled</option>
            </select>
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Update Member</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
