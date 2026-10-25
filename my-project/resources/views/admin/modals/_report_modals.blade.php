{{-- VIEW REPORT MODAL --}}
<div class="modal-backdrop" id="view-report-modal" onclick="closeModalOutside(event,'view-report-modal')">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title">Report Details</span>
      <button class="modal-close" onclick="closeModal('view-report-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div id="view-report-content"></div>
    <div class="form-group">
      <label class="form-label">Update Status</label>
      <select class="form-select" id="view-report-status">
        <option>Pending</option><option>In Progress</option><option>Resolved</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Admin Notes (Optional)</label>
      <textarea class="form-textarea" rows="3" placeholder="Add notes about actions taken..."></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-outline" onclick="closeModal('view-report-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="closeModal('view-report-modal')">Update Report</button>
    </div>
  </div>
</div>