{{-- ADD BIKE MODAL --}}
<div class="modal-backdrop" id="add-bike-modal" onclick="closeModalOutside(event,'add-bike-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add New Bike</span>
      <button class="modal-close" onclick="closeModal('add-bike-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" action="#">
      @csrf
      <div class="form-group"><label class="form-label">Bike Name</label><input type="text" name="name" class="form-input" placeholder="e.g. Mountain Pro X1" required></div>
      <div class="form-group">
        <label class="form-label">Type</label>
        <select name="type" class="form-select">
          <option>Mountain Bike</option><option>City Bike</option><option>Lady's/Men's Bike</option><option>E-Scooter</option><option>Kiddie Bikes</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Condition</label>
        <select name="condition" class="form-select">
          <option>Good</option><option>Needs Repair</option><option>Unusable</option>
        </select>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('add-bike-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Bike</button>
      </div>
    </form>
  </div>
</div>

{{-- EDIT BIKE MODAL --}}
<div class="modal-backdrop" id="edit-bike-modal" onclick="closeModalOutside(event,'edit-bike-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Bike</span>
      <button class="modal-close" onclick="closeModal('edit-bike-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="form-group"><label class="form-label">Bike ID</label><input type="text" id="edit-bike-id" class="form-input" readonly></div>
    <div class="form-group"><label class="form-label">Bike Name</label><input type="text" id="edit-bike-name" class="form-input"></div>
    <div class="form-group">
      <label class="form-label">Type</label>
      <select id="edit-bike-type" class="form-select">
        <option>Mountain Bike</option><option>City Bike</option><option>Lady's/Men's Bike</option><option>E-Scooter</option><option>Kiddie Bikes</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Status</label>
      <select id="edit-bike-status" class="form-select">
        <option>Available</option><option>Rented</option><option>Maintenance</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Condition</label>
      <select id="edit-bike-condition" class="form-select">
        <option>Good</option><option>Needs Repair</option><option>Unusable</option>
      </select>
    </div>
    <div class="form-actions">
      <button class="btn btn-outline" onclick="closeModal('edit-bike-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="closeModal('edit-bike-modal')">Save Changes</button>
    </div>
  </div>
</div>

{{-- DELETE BIKE MODAL --}}
<div class="modal-backdrop" id="delete-bike-modal" onclick="closeModalOutside(event,'delete-bike-modal')">
  <div class="modal">
    <div class="delete-warning">
      <div class="delete-icon">
        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
      </div>
      <div class="delete-title">Delete Bike</div>
      <div class="delete-desc">Are you sure you want to delete <strong id="delete-bike-name-display"></strong>? This cannot be undone.</div>
    </div>
    <div class="form-actions">
      <button class="btn btn-outline" onclick="closeModal('delete-bike-modal')">Cancel</button>
      <button class="btn btn-primary" style="background:#ef4444" onclick="closeModal('delete-bike-modal')">Delete</button>
    </div>
  </div>
</div>

{{-- QR MODAL --}}
<div class="modal-backdrop" id="qr-modal" onclick="closeModalOutside(event,'qr-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">QR Code</span>
      <button class="modal-close" onclick="closeModal('qr-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="qr-display">
      <div style="font-weight:600;font-size:16px;margin-bottom:4px" id="qr-bike-name"></div>
      <div style="font-size:13px;color:#6b7280;margin-bottom:16px" id="qr-bike-id"></div>
      <div class="qr-box" id="qr-box"></div>
      <div style="font-size:13px;color:#6b7280" id="qr-code-label"></div>
    </div>
    <div class="form-actions" style="margin-top:20px">
      <button class="btn btn-outline" onclick="closeModal('qr-modal')">Close</button>
      <button class="btn btn-primary">Download QR</button>
    </div>
  </div>
</div>