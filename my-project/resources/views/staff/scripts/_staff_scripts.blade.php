<script>
// ── NAVIGATION ───────────────────────────────────────────────────────────────
function goTo(id) {
  const target = document.getElementById(id);
  if (!target) return;

  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  target.classList.add('active');
  window.scrollTo(0, 0);
}
function navActive(btn) {
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

// ── LOGIN ────────────────────────────────────────────────────────────────────
function doLogin() {
  const email = document.getElementById('loginEmail').value.trim();
  const pw    = document.getElementById('loginPw').value;
  const err   = document.getElementById('loginError');
  if (!email || !pw) { err.classList.add('show'); return false; }
  err.classList.remove('show');
  return true;
}
function toggleDashboardPassword() {
  const inp  = document.getElementById('loginPw');
  const icon = document.getElementById('pwEyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    icon.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>';
  }
}

// ── SCANNER ──────────────────────────────────────────────────────────────────
let pendingBikeCode = null;

function startScan(bikeCode) {
  pendingBikeCode = bikeCode;
  goTo('scanner');
}

function simulateScan() {
  const btn = document.querySelector('.scan-simulate-btn');
  btn.textContent = 'Scanning…';
  btn.disabled = true;
  setTimeout(() => {
    btn.textContent = 'Simulate QR Scan';
    btn.disabled = false;
    if (pendingBikeCode) {
      toggleBikeStatus(pendingBikeCode);
    } else {
      goTo('rental-form');
    }
  }, 1200);
}

function toggleBikeStatus(bikeCode) {
  const row = document.querySelector(`[data-bike-code="${bikeCode}"]`);

  fetch(`/staff/inventory/${encodeURIComponent(bikeCode)}/toggle-status`, {
    method: 'PATCH',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json',
    },
  })
    .then(response => response.json().then(data => ({ ok: response.ok, data })))
    .then(({ ok, data }) => {
      if (!ok) {
        showToast(data.message || 'This bike cannot be rented.');
        return;
      }

      if (row) {
        row.dataset.status = data.status;
        const badge = row.querySelector('[data-bike-status]');
        const status = data.status.charAt(0).toUpperCase() + data.status.slice(1);
        badge.textContent = status;
        badge.className = `badge ${data.status === 'available' ? 'badge-green' : 'badge-blue'}`;
      }

      const availableStat = document.getElementById('stat-available');
      const rentedStat = document.getElementById('stat-rented');
      if (availableStat && rentedStat) {
        const available = Number(availableStat.textContent);
        const rented = Number(rentedStat.textContent);
        availableStat.textContent = data.status === 'rented' ? available - 1 : available + 1;
        rentedStat.textContent = data.status === 'rented' ? rented + 1 : rented - 1;

        const total = Number(document.getElementById('stat-total')?.textContent || 0);
        if (total > 0) {
          document.querySelector('.stat-fill-green').style.width = `${(Number(availableStat.textContent) / total) * 100}%`;
          document.querySelector('.stat-fill-blue').style.width = `${(Number(rentedStat.textContent) / total) * 100}%`;
        }
      }

      pendingBikeCode = null;
      showToast(data.message);
      goTo(data.status === 'rented' ? 'rental-form' : 'inventory');
    })
    .catch(() => showToast('Could not update this bike.'));
}

// ── RENTAL ───────────────────────────────────────────────────────────────────
function submitRental() {
  goTo('success');
  startTimer();
}
function startTimer() {
  let h = 2, m = 0, s = 0;
  const el = document.getElementById('timer');
  const tick = setInterval(() => {
    if (s === 0) {
      if (m === 0) { if (h === 0) { clearInterval(tick); return; } h--; m = 59; s = 59; }
      else { m--; s = 59; }
    } else { s--; }
    el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
  }, 1000);
}
function pad(n) { return String(n).padStart(2, '0'); }

// ── MODALS ───────────────────────────────────────────────────────────────────
function openBikeAction(action, data = {}) {
  const content = document.getElementById('modalContent');
  const title = action === 'edit' ? `Edit ${data.id ?? 'Bike'}` : 'Delete Bike';
  const body = action === 'delete'
    ? `<p class="modal-confirmation-message">Are you sure you want to remove ${data.id ?? 'this bike'} from inventory?</p>
       <form method="POST" action="{{ url('/staff/inventory') }}/${encodeURIComponent(data.id ?? '')}">
         @csrf
         @method('DELETE')
         <div class="modal-actions"><button type="submit" class="primary-btn">Delete Bike</button><button type="button" class="primary-btn outline" onclick="closeModal()">Cancel</button></div>
       </form>`
    : `<form method="POST" action="{{ url('/staff/inventory') }}/${encodeURIComponent(data.id ?? '')}">
         @csrf
         @method('PATCH')
         <div class="form-group"><label class="form-label" for="edit-qr-code">QR Code</label><input id="edit-qr-code" name="qr_code" class="form-input" value="${data.qrCode ?? ''}" required></div>
         <div class="form-group"><label class="form-label" for="edit-model">Model</label><input id="edit-model" name="model" class="form-input" value="${data.model ?? ''}" required></div>
         <div class="form-group"><label class="form-label" for="edit-make">Make</label><input id="edit-make" name="make" class="form-input" value="${data.make ?? ''}" required></div>
         <div class="form-group"><label class="form-label" for="edit-bike-type">Bike Type</label><select id="edit-bike-type" name="bike_type" class="form-select" required>${['Mountain Bike', 'City Bike', "Lady's/Men's Bike", 'E-Scooter', 'Road Bike', 'Sidecar Bike', "Children's Bike"].map(type => `<option ${data.type === type ? 'selected' : ''}>${type}</option>`).join('')}</select></div>
         <div class="form-group"><label class="form-label" for="edit-condition">Condition</label><select id="edit-condition" name="condition" class="form-select" required><option ${data.condition === 'Good' ? 'selected' : ''}>Good</option><option ${data.condition === 'Needs Repair' ? 'selected' : ''}>Needs Repair</option><option ${data.condition === 'Missing' ? 'selected' : ''}>Missing</option></select></div>
         <div class="modal-actions"><button type="submit" class="primary-btn">Save Changes</button><button type="button" class="primary-btn outline" onclick="closeModal()">Cancel</button></div>
       </form>`;
  content.innerHTML = `<div class="modal-bike-title">${title}</div>${body}`;
  document.getElementById('modalBg').classList.add('open');
}

function openStaffAction(action, name = '') {
  const content = document.getElementById('modalContent');
  const title = action === 'add' ? 'Add Staff Account' : action === 'edit' ? `Edit ${name}` : `Delete ${name}`;
  const body = action === 'delete'
    ? `<p class="modal-confirmation-message">Are you sure you want to remove this staff account?</p>`
    : `<div class="form-group"><label class="form-label">Full Name</label><input class="form-input" value="${name}" placeholder="Enter full name"></div>
       <div class="form-group"><label class="form-label">Email Address</label><input class="form-input" type="email" placeholder="staff@rentabike.com"></div>
       <div class="form-group"><label class="form-label">Role</label><select class="form-select"><option>Staff</option><option>Admin</option></select></div>`;
  content.innerHTML = `<div class="modal-bike-title">${title}</div>${body}<div class="modal-actions"><button class="primary-btn" onclick="showToast('Staff account ${action} action submitted'); closeModal()">${action === 'delete' ? 'Delete Account' : 'Save Account'}</button><button class="primary-btn outline" onclick="closeModal()">Cancel</button></div>`;
  document.getElementById('modalBg').classList.add('open');
}

function openModal(type, data = {}) {
  const bikeIconHtml = (cls) => `
    <div class="bike-icon ${cls}" style="width:48px;height:48px">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/>
        <path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/>
        <path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/>
      </svg>
    </div>`;

  const content = document.getElementById('modalContent');

  if (type === 'available') {
    content.innerHTML = `
      <div class="modal-bike-header">
        ${bikeIconHtml('green')}
        <div>
          <div class="modal-bike-title">${data.id ?? 'BK-101'}</div>
          <span class="badge badge-green"><span class="badge-dot badge-dot-green"></span>Available</span>
        </div>
      </div>
      <div class="modal-detail-row"><span class="label">Condition</span><span class="value">${data.condition ?? 'Ready for Rental'}</span></div>
      <div class="modal-detail-row"><span class="label">Last Borrower</span><span class="value">${data.lastBorrower ?? '—'}</span></div>
      <div class="modal-detail-row"><span class="label">Last Returned</span><span class="value">${data.lastReturned ?? '—'}</span></div>
      <div class="modal-actions">
        <button class="primary-btn" onclick="closeModal(); startScan('${data.id ?? ''}')">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="5" height="5" x="3" y="3" rx=".5"/><rect width="5" height="5" x="16" y="3" rx=".5"/><rect width="5" height="5" x="3" y="16" rx=".5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/></svg>
          Scan to Rent
        </button>
        <button class="primary-btn outline" onclick="closeModal()">Close</button>
      </div>`;
  }

  if (type === 'rented') {
    content.innerHTML = `
      <div class="modal-bike-header">
        ${bikeIconHtml('blue')}
        <div>
          <div class="modal-bike-title">${data.id ?? 'BK-102'}</div>
          <span class="badge badge-blue"><span class="badge-dot badge-dot-blue"></span>Rented</span>
        </div>
      </div>
      <div class="modal-detail-row"><span class="label">Borrowed By</span><span class="value">${data.borrower ?? '—'}</span></div>
      <div class="modal-detail-row"><span class="label">Borrow Time</span><span class="value">${data.borrowTime ?? '—'}</span></div>
      <div class="modal-detail-row"><span class="label">Expected Return</span><span class="value">${data.returnTime ?? '—'}</span></div>
      <div class="modal-detail-row"><span class="label">Status</span><span class="value">Active Rental</span></div>
      <div class="modal-actions">
        <button class="primary-btn" onclick="closeModal(); startScan('${data.id ?? ''}')">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="5" height="5" x="3" y="3" rx=".5"/><rect width="5" height="5" x="16" y="3" rx=".5"/><rect width="5" height="5" x="3" y="16" rx=".5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/></svg>
          Scan to Return
        </button>
        <button class="primary-btn" onclick="toggleID()">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="14" height="18" x="5" y="3" rx="2"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>
          View Borrower ID
        </button>
        <div id="idContainer" style="display:none">
          <div class="id-preview-box">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect width="14" height="18" x="5" y="3" rx="2"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>
            <p>Borrower ID File Preview</p>
          </div>
        </div>
        <button class="primary-btn outline" onclick="closeModal()">Close</button>
      </div>`;
  }

  if (type === 'maintenance') {
    content.innerHTML = `
      <div class="modal-bike-header">
        ${bikeIconHtml('orange')}
        <div>
          <div class="modal-bike-title">${data.id ?? 'BK-103'}</div>
          <span class="badge badge-orange"><span class="badge-dot badge-dot-orange"></span>Repair</span>
        </div>
      </div>
      <div class="modal-detail-row"><span class="label">Issue</span><span class="value">${data.issue ?? '—'}</span></div>
      <div class="modal-detail-row"><span class="label">Updated By</span><span class="value">${data.updatedBy ?? '—'}</span></div>
      <div class="modal-detail-row"><span class="label">Date Flagged</span><span class="value">${data.date ?? '—'}</span></div>
      <div class="modal-actions">
        <button class="primary-btn" data-report-type="${data.reportType ?? 'damage'}" data-bike-id="${data.id ?? ''}" onclick="closeModal(); openReportForm(this.dataset.reportType, this.dataset.bikeId)">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
          ${data.reportType === 'missing' ? 'Report Missing Bike' : 'File Damage Report'}
        </button>
        <button class="primary-btn outline" onclick="closeModal()">Close</button>
      </div>`;
  }

  if (type === 'report-confirm') {
    content.innerHTML = `
      <div class="modal-bike-header">
        <div class="bike-icon orange" style="width:48px;height:48px">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
        </div>
        <div>
          <div class="modal-bike-title">Submit Report?</div>
        </div>
      </div>
      <p class="modal-confirmation-message">This report will be submitted to the Admin for review. Are you sure you want to continue?</p>
      <div class="modal-actions">
        <button class="primary-btn" onclick="confirmReport()">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          Confirm Submit
        </button>
        <button class="primary-btn outline" onclick="closeModal()">Cancel</button>
      </div>`;
  }

  document.getElementById('modalBg').classList.add('open');
}
function closeModal() { document.getElementById('modalBg').classList.remove('open'); }
function closeModalOutside(e) { if (e.target === document.getElementById('modalBg')) closeModal(); }
function toggleID() {
  const c = document.getElementById('idContainer');
  c.style.display = c.style.display === 'none' ? 'block' : 'none';
}

// ── REPORT ───────────────────────────────────────────────────────────────────
const reportTitles = { damage: 'Report Damage', missing: 'Report Missing Bike', other: 'Other Issue' };
function openReportForm(type, bikeId = '') {
  document.getElementById('reportFormTitle').textContent = reportTitles[type] ?? 'Report Issue';
  document.getElementById('reportBikeId').value = bikeId;
  document.getElementById('reportDesc').value   = '';
  goTo('report-form');
}
function submitReport() {
  const bikeId = document.getElementById('reportBikeId').value.trim();
  const desc   = document.getElementById('reportDesc').value.trim();
  if (!bikeId || !desc) { showToast('Please fill in all required fields.'); return; }
  openModal('report-confirm', { bikeId, description: desc });
}
function confirmReport() {
  closeModal();
  goTo('home');
  showToast('Report submitted successfully!');
}

// ── TOAST ────────────────────────────────────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

document.querySelectorAll('[data-fill-width]').forEach(fill => {
  fill.style.width = `${fill.dataset.fillWidth}%`;
});

if (document.getElementById('inventory-status')) {
  const inventoryStatus = document.getElementById('inventory-status');
  showToast(inventoryStatus.dataset.message);
}

// ── LIVE CLOCK ───────────────────────────────────────────────────────────────
function updateTime() {
  const now = new Date();
  let h = now.getHours();
  const m   = String(now.getMinutes()).padStart(2, '0');
  const suf = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  const timeElement = document.querySelector('.screen.active #liveTime');
  if (timeElement) timeElement.textContent = h + ':' + m + ' ' + suf;
}
setInterval(updateTime, 1000);
updateTime();
</script>