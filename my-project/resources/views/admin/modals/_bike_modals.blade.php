{{-- ADD BIKE MODAL --}}
<div class="modal-backdrop" id="add-bike-modal" onclick="closeModalOutside(event,'add-bike-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add New Bike</span>
      <button class="modal-close" onclick="closeModal('add-bike-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" action="{{ route('admin.bikes.store') }}" data-bike-code-preview-url="{{ route('admin.bikes.next-code') }}">
      @csrf
      <input type="hidden" name="current_tab" value="bikes">
      <div class="form-group"><label class="form-label" for="add-bike-id">Bike ID</label><input type="text" id="add-bike-id" class="form-input" value="{{ $nextBikeCode }}" readonly></div>
      <div class="form-group"><label class="form-label">Bike Name</label><input type="text" name="model" class="form-input" placeholder="e.g. City 300" required></div>
      <div class="form-group"><label class="form-label">Make</label><input type="text" name="make" class="form-input" placeholder="e.g. Trek" required></div>
      <div class="form-group">
        <label class="form-label">Bike Type</label>
        <select id="add-bike-type" name="bike_type" class="form-select" onchange="updateBikeCodePreview(this)" required>
          <option value="Mountain Bike">Mountain Bike</option><option value="City Bike">City Bike</option><option value="Lady's/Men's Bike">Lady's/Men's Bike</option><option value="E-Scooter">E-Scooter</option><option value="Road Bike">Road Bike</option><option value="Sidecar Bike">Sidecar Bike</option><option value="Children's Bike">Children's Bike</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Condition</label>
        <select name="condition" class="form-select">
          <option value="good">Good</option><option value="repair">Needs Repair</option><option value="missing">Missing</option>
        </select>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('add-bike-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Bike</button>
      </div>
    </form>
  </div>
</div>

{{-- DIRECT ADMIN RENTAL MODAL --}}
<div class="modal-backdrop" id="admin-rent-modal" onclick="closeAdminRentOutside(event)">
  <div class="modal admin-rent-modal" role="dialog" aria-modal="true" aria-labelledby="admin-rent-title">
    <div class="modal-header">
      <div>
        <span class="modal-title" id="admin-rent-title">Rent Bike</span>
        <p class="admin-rent-subtitle">Create a rental directly from inventory.</p>
      </div>
      <button type="button" class="modal-close" onclick="cancelAdminRent()" aria-label="Close">&times;</button>
    </div>
    <div class="admin-rent-bike-card">
      <div class="admin-rent-bike-icon" aria-hidden="true">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="m15 6 3.5 11.5M5.5 17.5 10 9h4l4.5 8.5M8 13h8M12 9l-2-3H7"/></svg>
      </div>
      <div class="admin-rent-bike-info">
        <div class="admin-rent-bike-name" id="admin-rent-bike-name">—</div>
        <div class="admin-rent-bike-type" id="admin-rent-bike-type"></div>
      </div>
      <span class="admin-rent-bike-code" id="admin-rent-bike-id">—</span>
    </div>
    <form id="admin-rent-form" class="admin-rent-form" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="admin-rent-fields">
        <div class="form-group">
          <label class="form-label" for="admin-rent-borrower-name">Borrower name</label>
          <input class="form-input" id="admin-rent-borrower-name" name="borrower_name" type="text" maxlength="150" placeholder="Enter full name" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="admin-rent-contact">Contact number</label>
          <input class="form-input" id="admin-rent-contact" name="contact_no" type="tel" maxlength="30" placeholder="09XXXXXXXXX" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="admin-rent-duration">Rental duration</label>
          <select class="form-select" id="admin-rent-duration" name="minutes" required onchange="updateAdminRentSummary()"></select>
        </div>
        <div class="form-group">
          <label class="form-label" for="admin-rent-time">Borrow time</label>
          <input class="form-input" id="admin-rent-time" name="borrow_time" type="time" required oninput="updateAdminRentSummary()">
        </div>
      </div>
      <div class="admin-rent-summary">
        <div class="admin-rent-return">
          <span class="admin-rent-summary-label">Expected return</span>
          <strong id="admin-rent-expected-return">—</strong>
        </div>
        <div class="admin-rent-total">
          <span class="admin-rent-summary-label">Total to pay</span>
          <strong id="admin-rent-total">₱0</strong>
        </div>
      </div>
      <div class="form-group admin-rent-upload-group">
        <label class="form-label" for="admin-rent-id">Borrower ID</label>
        <label class="admin-rent-upload" for="admin-rent-id">
          <span class="admin-rent-upload-icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5"/></svg>
          </span>
          <span class="admin-rent-upload-copy">
            <strong id="admin-rent-id-name">Choose a JPG, PNG, WEBP, or PDF</strong>
            <small>Maximum file size: 2 MB</small>
          </span>
          <input class="admin-rent-file-input" id="admin-rent-id" name="borrower_id" type="file" accept="image/jpeg,image/png,image/webp,application/pdf">
        </label>
      </div>
      <p id="admin-rent-error" class="admin-rent-error" role="alert" aria-live="polite" hidden></p>
      <div class="form-actions admin-rent-actions">
        <button type="button" class="btn btn-outline" onclick="cancelAdminRent()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="admin-rent-submit" disabled>Confirm Rental</button>
      </div>
    </form>
  </div>
</div>

{{-- EDIT BIKE MODAL --}}
<div class="modal-backdrop" id="edit-bike-modal" onclick="closeModalOutside(event,'edit-bike-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Bike</span>
      <button type="button" class="modal-close" onclick="closeModal('edit-bike-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form id="edit-bike-form" method="POST" action="" data-bike-code-preview-url="{{ route('admin.bikes.next-code') }}" data-rental-options-url="{{ route('admin.bikes.rental-options', ['bike' => '__BIKE__']) }}" data-rent-url="{{ route('admin.bikes.rent', ['bike' => '__BIKE__']) }}">
      @csrf
      @method('PATCH')
      <input type="hidden" name="current_tab" value="bikes">
      <div class="form-group"><label class="form-label">Bike ID</label><input type="text" id="edit-bike-id" name="qr_code" class="form-input" readonly></div>
      <div class="form-group"><label class="form-label">Bike Name</label><input type="text" id="edit-bike-name" name="model" class="form-input"></div>
      <div class="form-group"><label class="form-label">Make</label><input type="text" id="edit-bike-make" name="make" class="form-input"></div>
      <div class="form-group">
        <label class="form-label">Type</label>
        <select id="edit-bike-type" name="bike_type" class="form-select" onchange="updateBikeCodePreview(this)">
          <option value="Mountain Bike">Mountain Bike</option><option value="City Bike">City Bike</option><option value="Lady's/Men's Bike">Lady's/Men's Bike</option><option value="E-Scooter">E-Scooter</option><option value="Road Bike">Road Bike</option><option value="Sidecar Bike">Sidecar Bike</option><option value="Children's Bike">Children's Bike</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="edit-bike-status" name="status" class="form-select">
          <option value="available">Available</option><option value="rented">Rented</option><option value="repair">Maintenance</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Condition</label>
        <select id="edit-bike-condition" name="condition" class="form-select">
          <option value="good">Good</option>
        </select>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('edit-bike-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

{{-- BIKE PASSWORD VERIFICATION MODAL --}}
<div class="modal-backdrop" id="bike-password-modal" onclick="closeModalOutside(event,'bike-password-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Verify Your Password</span>
      <button type="button" class="modal-close" onclick="closeModal('bike-password-modal')" aria-label="Close">&times;</button>
    </div>
    <p id="bike-password-prompt">Enter your admin password to continue.</p>
    <form id="bike-password-form" data-verify-url="{{ route('admin.bikes.verify', ['bike' => '__BIKE_ID__']) }}">
      @csrf
      <div class="form-group">
        <label class="form-label" for="bike-action-password">Admin Password</label>
        <input type="password" id="bike-action-password" name="password" class="form-input" autocomplete="current-password" required>
      </div>
      <p id="bike-password-error" role="alert" style="color:#dc2626;font-size:13px" hidden></p>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('bike-password-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Verify</button>
      </div>
    </form>
  </div>
</div>

{{-- DELETE BIKE MODAL --}}
<div class="modal-backdrop" id="delete-bike-modal" onclick="closeModalOutside(event,'delete-bike-modal')">
  <div class="modal">
    <form id="delete-bike-form" method="POST" action="">
      @csrf
      @method('DELETE')
      <input type="hidden" name="current_tab" value="bikes">
      <div class="delete-warning">
        <div class="delete-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-triangle-alert-icon lucide-triangle-alert"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
        </div>
        <div class="delete-title">Delete Bike</div>
        <div class="delete-desc">Are you sure you want to delete <strong id="delete-bike-name-display"></strong>? This cannot be undone.</div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('delete-bike-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

{{-- QR SHEET MODAL --}}
<div class="modal-backdrop" id="qr-sheet-modal" onclick="closeModalOutside(event,'qr-sheet-modal')">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title">Generate QR Codes</span>
      <button class="modal-close" onclick="closeModal('qr-sheet-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div style="font-size:13px;color:#6b7280;margin-bottom:16px" id="qr-sheet-count"></div>
    <div class="qr-sheet-grid" id="qr-sheet-grid"></div>
    <div style="font-size:13px;color:#6b7280;text-align:center;margin-top:12px" id="qr-sheet-more"></div>
    <div class="form-actions" style="margin-top:20px">
      <button class="btn btn-outline" onclick="closeModal('qr-sheet-modal')">Cancel</button>
      <button class="btn btn-outline" id="qr-zip-btn" onclick="downloadQrZip()">Download ZIP</button>
      <button class="btn btn-primary" id="qr-print-btn" onclick="printQrSheet()">Print</button>
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
      <button class="btn btn-primary" onclick="downloadQR()">Download QR</button>
    </div>
  </div>
</div>