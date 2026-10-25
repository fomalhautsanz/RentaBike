{{-- VIEW REPORT MODAL --}}
{{-- gi modify ra nako --}}
<div class="modal-backdrop" id="view-report-modal" onclick="closeModalOutside(event,'view-report-modal')">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title">Report Details</span>
      <button class="modal-close" onclick="closeModal('view-report-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div id="view-report-content">
      <div class="vr-row"><span class="vr-label">Report ID</span><span id="vrReportId">-</span></div>
      <div class="vr-row"><span class="vr-label">Type</span><span id="vrType">-</span></div>
      <div class="vr-row"><span class="vr-label">Bike</span><span id="vrBike">-</span></div>
      <div class="vr-row"><span class="vr-label">Reported By</span><span id="vrReporter">-</span></div>
      <div class="vr-row"><span class="vr-label">Date</span><span id="vrDate">-</span></div>
      <div class="vr-row vr-desc-row">
        <span class="vr-label">Description</span>
        <p id="vrDescription">-</p>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Update Status</label>
      <select class="form-select" id="view-report-status">
        <option>Pending</option><option>In Progress</option><option>Resolved</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Admin Notes (Optional)</label>
      <textarea class="form-textarea" rows="3" id="view-report-notes" placeholder="Add notes about actions taken..."></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-outline" onclick="closeModal('view-report-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="updateReportStatus()">Update Report</button>
    </div>
  </div>
</div>

<style>
  .vr-row {
    display: flex; justify-content: space-between; padding: 8px 0;
    border-bottom: 1px solid #f3f4f6; font-size: 14px;
  }
  .vr-label { color: #6b7280; font-weight: 500; }
  .vr-desc-row { flex-direction: column; gap: 4px; }
  .vr-desc-row p { margin: 0; color: #374151; }
</style>

<script>
  // remember which row we're viewing/updating (temporary until we have real IDs from the backend)
  window._viewingReportCode = null;
  // temporary in-memory notes store, keyed by report code, until backend exists
  window._reportNotes = window._reportNotes || {};

  function openViewReport(reportCode) {
    const rows = document.querySelectorAll('#page-maintenance table tbody tr');
    let targetRow = null;
    rows.forEach(row => {
      const firstCell = row.querySelector('td');
      if (firstCell && firstCell.textContent.trim() === reportCode) {
        targetRow = row;
      }
    });

    if (!targetRow) return;

    const cells = targetRow.querySelectorAll('td');
    document.getElementById('vrReportId').textContent = cells[0].textContent.trim();
    document.getElementById('vrType').textContent = cells[1].textContent.trim();
    document.getElementById('vrBike').textContent = cells[2].textContent.trim().split('\n')[0].trim();
    document.getElementById('vrReporter').textContent = cells[3].textContent.trim();
    document.getElementById('vrDescription').textContent = cells[4].getAttribute('title') || cells[4].textContent.trim();
    document.getElementById('vrDate').textContent = cells[5].textContent.trim();
    document.getElementById('view-report-status').value = cells[6].textContent.trim();
    document.getElementById('view-report-notes').value = window._reportNotes[reportCode] || '';

    window._viewingReportCode = reportCode;

    openModal('view-report-modal');
  }

  function updateReportStatus() {
    const reportCode = window._viewingReportCode;
    if (!reportCode) return;

    const newStatus = document.getElementById('view-report-status').value;
    const notes = document.getElementById('view-report-notes').value.trim();

    // save the note in memory so it re-appears next time this report is opened
    window._reportNotes[reportCode] = notes;

    // TODO: once backend exists, replace the row-patching below with:
    // fetch(`/reports/${reportCode}`, { method: 'PUT', body: JSON.stringify({ status: newStatus, notes }), headers: {...} })

    const rows = document.querySelectorAll('#page-maintenance table tbody tr');
    rows.forEach(row => {
      const firstCell = row.querySelector('td');
      if (firstCell && firstCell.textContent.trim() === reportCode) {
        row.dataset.status = newStatus;
        const statusCell = row.querySelectorAll('td')[6];
        const badgeClass = newStatus === 'Pending' ? 'status-pending'
                          : newStatus === 'In Progress' ? 'status-inprogress'
                          : 'status-resolved';
        statusCell.innerHTML = `<span class="badge ${badgeClass}">${newStatus}</span>`;
      }
    });

    // keep the counters and any active filter in sync with the new status
    updateMaintenanceCounters();
    filterMaintenanceStatus(maintenanceStatusFilter);

    closeModal('view-report-modal');
    showToast(`${reportCode} status updated to ${newStatus}.`);
    window._viewingReportCode = null;
  }
</script>