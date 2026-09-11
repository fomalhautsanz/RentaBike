{{-- ADD STAFF MODAL --}}
<div class="modal-backdrop" id="add-staff-modal" onclick="closeModalOutside(event,'add-staff-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Staff Member</span>
      <button class="modal-close" onclick="closeModal('add-staff-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" action="{{ route('admin.staff.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-input" placeholder="e.g. Juan dela Cruz" required></div>
      <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-input" placeholder="staff@rentabike.com" required></div>
      <div class="form-group"><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-input" placeholder="+63 912 345 6789"></div>
      <div class="form-group"><label class="form-label">Profile Picture</label><input type="file" name="profile_picture" class="form-input" accept="image/jpeg,image/png,image/webp"></div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
          <option>Staff</option><option>Admin</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Privileges</label>
        <div class="permission-grid">
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="View Inventory"> <span>View Inventory</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Add Inventory"> <span>Add Inventory</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Edit Inventory"> <span>Edit Inventory</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Delete Inventory"> <span>Delete Inventory</span></label>
          <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Handle Maintenance"> <span>Handle Maintenance</span></label>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-input" placeholder="At least 9 characters" minlength="9" required></div>
      <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="password_confirmation" class="form-input" placeholder="Re-enter password" minlength="9" required></div>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('add-staff-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Staff</button>
      </div>
    </form>
  </div>
</div>

{{-- CONFIRM STAFF MODAL --}}
<div class="modal-backdrop" id="confirm-staff-modal" onclick="closeModalOutside(event,'confirm-staff-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Confirm Staff Account</span>
      <button class="modal-close" type="button" onclick="closeModal('confirm-staff-modal')">&times;</button>
    </div>
    <p>Please review the account details before creating it.</p>
    <div id="staff-confirmation-summary" class="confirmation-summary"></div>
    <div class="form-actions">
      <button type="button" class="btn btn-outline" onclick="closeModal('confirm-staff-modal')">Back</button>
      <button type="button" class="btn btn-primary" onclick="confirmStaffCreation()">Create Account</button>
    </div>
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
    <form method="POST" action="" enctype="multipart/form-data" id="edit-staff-form">
      @csrf
      @method('PATCH')
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" id="edit-staff-name" class="form-input" required></div>
      <div class="form-group"><label class="form-label">Profile Picture</label><input type="file" name="profile_picture" class="form-input" accept="image/jpeg,image/png,image/webp"></div>
    <div class="form-group">
      <label class="form-label">Role</label>
      <select name="role" id="edit-staff-role" class="form-select">
        <option>Staff</option><option>Admin</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Status</label>
      <select name="status" id="edit-staff-status" class="form-select">
        <option>Active</option><option>On Leave</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Privileges</label>
      <div class="permission-grid">
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="View Inventory"> <span>View Inventory</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Add Inventory"> <span>Add Inventory</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Edit Inventory"> <span>Edit Inventory</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Delete Inventory"> <span>Delete Inventory</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Manage Staff"> <span>Manage Staff</span></label>
        <label class="permission-option"><input type="checkbox" class="permission-checkbox" name="permissions[]" value="Handle Maintenance"> <span>Handle Maintenance</span></label>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-outline" onclick="closeModal('edit-staff-modal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
    </form>
  </div>
</div>

{{-- DELETE STAFF MODAL --}}
<div class="modal-backdrop" id="delete-staff-modal" onclick="closeModalOutside(event,'delete-staff-modal')">
  <div class="modal">
    <div class="delete-warning">
      <div class="delete-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-triangle-alert-icon lucide-triangle-alert"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
      </div>
      <div class="delete-title">Remove Staff Member</div>
      <div class="delete-desc">Are you sure you want to remove <strong id="delete-staff-name-display"></strong>? This action cannot be undone.</div>
    </div>
    <div class="form-actions">
      <button class="btn btn-outline" onclick="closeModal('delete-staff-modal')">Cancel</button>
      <button class="btn btn-primary btn-danger" onclick="confirmDeleteStaff()">Remove</button>
    </div>
  </div>
</div>

{{-- TOAST --}}
<div id="toast" class="toast toast-hidden">
  <svg class="toast-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
    <path d="M20 6 9 17l-5-5"/>
  </svg>
  <span id="toast-message"></span>
</div>

<style>
.toast {
  position: fixed;
  bottom: 28px;
  right: 28px;
  display: flex;
  align-items: center;
  gap: 12px;
  background: #16a34a;
  color: #fff;
  padding: 16px 22px;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 500;
  box-shadow: 0 8px 20px rgba(22,163,74,0.35);
  z-index: 999;
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.toast-icon {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  background: rgba(255,255,255,0.2);
  border-radius: 50%;
  padding: 4px;
  box-sizing: content-box;
}
.toast-hidden {
  opacity: 0;
  transform: translateY(10px);
  pointer-events: none;
}
.action-btn {
  transition: background-color 0.15s ease, color 0.15s ease;
  border-radius: 6px;
}
.action-btn:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}
.action-btn.delete-btn:hover {
  background-color: #fee2e2;
  color: #dc2626;
}
.warn-icon-green {
  width: 56px;
  height: 56px;
  margin: 0 auto 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #dcfce7;
  color: #16a34a;
}

/* Remove button (Delete confirmation modal) hover state */
.btn-danger {
  background: #ef4444;
  transition: background-color 0.15s ease;
}
.btn-danger:hover {
  background-color: #b91c1c; /* darker red on hover , for better visual feedback
  kay lain kaau way hover plain af */ 
}
</style>