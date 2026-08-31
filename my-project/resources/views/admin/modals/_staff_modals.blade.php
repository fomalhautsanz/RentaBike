{{-- ADD STAFF MODAL --}}
<div class="modal-backdrop" id="add-staff-modal" onclick="closeModalOutside(event,'add-staff-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Staff Member</span>
      <button class="modal-close" onclick="closeModal('add-staff-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" action="#">
      @csrf
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-input" placeholder="e.g. Juan dela Cruz" required></div>
      <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-input" placeholder="staff@rentabike.com" required></div>
      <div class="form-group"><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-input" placeholder="+63 912 345 6789"></div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
          <option>Staff</option><option>Manager</option><option>Technician</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Privileges</label>
        <div class="permission-grid">
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="View Inventory"> <span>View Inventory</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Add Inventory"> <span>Add Inventory</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Edit Inventory"> <span>Edit Inventory</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Delete Inventory"> <span>Delete Inventory</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Process Rentals"> <span>Process Rentals</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="View Reports"> <span>View Reports</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Manage Staff"> <span>Manage Staff</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Handle Maintenance"> <span>Handle Maintenance</span></label>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-input" placeholder="Set initial password" required></div>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('add-staff-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Staff</button>
      </div>
    </form>
  </div>
</div>

{{-- EDIT STAFF MODAL --}}
<div class="modal-backdrop" id="edit-staff-modal" onclick="closeModalOutside(event,'edit-staff-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Staff Member</span>
      <button class="modal-close" onclick="closeModal('edit-staff-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="form-group"><label class="form-label">Full Name</label><input type="text" id="edit-staff-name" class="form-input"></div>
    <div class="form-group">
      <label class="form-label">Role</label>
      <select id="edit-staff-role" class="form-select">
        <option>Staff</option><option>Manager</option><option>Technician</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Status</label>
      <select id="edit-staff-status" class="form-select">
        <option>Active</option><option>On Leave</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Privileges</label>
      <div class="permission-grid">
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" value="View Inventory"> <span>View Inventory</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" value="Add Inventory"> <span>Add Inventory</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" value="Edit Inventory"> <span>Edit Inventory</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" value="Delete Inventory"> <span>Delete Inventory</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" value="Process Rentals"> <span>Process Rentals</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" value="View Reports"> <span>View Reports</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" value="Manage Staff"> <span>Manage Staff</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" value="Handle Maintenance"> <span>Handle Maintenance</span></label>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-outline" onclick="closeModal('edit-staff-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="closeModal('edit-staff-modal')">Save Changes</button>
    </div>
  </div>
</div>

{{-- DELETE STAFF MODAL --}}
<div class="modal-backdrop" id="delete-staff-modal" onclick="closeModalOutside(event,'delete-staff-modal')">
  <div class="modal">
    <div class="delete-warning">
      <div class="delete-icon">
        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
      </div>
      <div class="delete-title">Remove Staff Member</div>
      <div class="delete-desc">Are you sure you want to remove <strong id="delete-staff-name-display"></strong>? This action cannot be undone.</div>
    </div>
    <div class="form-actions">
      <button class="btn btn-outline" onclick="closeModal('delete-staff-modal')">Cancel</button>
      <button class="btn btn-primary" style="background:#ef4444" onclick="closeModal('delete-staff-modal')">Remove</button>
    </div>
  </div>
</div>