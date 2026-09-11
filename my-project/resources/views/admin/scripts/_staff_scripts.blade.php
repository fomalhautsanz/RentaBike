<script>
function filterStaff(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#staff-tbody tr').forEach(r => {
    r.style.display = r.dataset.name.toLowerCase().includes(q) ? '' : 'none';
  });
}
let staffRoleFilter = '', staffStatusFilter = '';
function filterStaffRole(v) { staffRoleFilter = v; applyStaffFilters(); }
function filterStaffStatus(v) { staffStatusFilter = v; applyStaffFilters(); }
function applyStaffFilters() {
  document.querySelectorAll('#staff-tbody tr').forEach(r => {
    const roleOk = !staffRoleFilter || r.dataset.role === staffRoleFilter;
    const statusOk = !staffStatusFilter || r.dataset.status === staffStatusFilter;
    r.style.display = roleOk && statusOk ? '' : 'none';
  });
}
function openEditStaff(staffId) {
  const row = document.querySelector(`#staff-tbody tr[data-id="${staffId}"]`);
  if (!row) return;

  const permissions = row.dataset.permissions || '';
  const name = row.dataset.name;
  const role = row.dataset.role;
  const status = row.dataset.status;
  document.getElementById('edit-staff-name').value = name;
  document.getElementById('edit-staff-role').value = role;
  document.getElementById('edit-staff-status').value = status;

  const selectedPermissions = Array.isArray(permissions)
    ? permissions
    : (typeof permissions === 'string' ? permissions.split(',').map(p => p.trim()).filter(Boolean) : []);

  document.querySelectorAll('#edit-staff-modal .permission-checkbox').forEach(cb => {
    cb.checked = selectedPermissions.includes(cb.value);
  });

  document.getElementById('edit-staff-form').action = `/admin/staff/${staffId}`;
  openModal('edit-staff-modal');
}
function openDeleteStaff(name) {
  document.getElementById('delete-staff-name-display').textContent = name;
  // remember which record we're editing 
  //temporary until we have IDs from the backend 
  window._deletingStaffName = name; 
  openModal('delete-staff-modal');
}
// basta teh same ranis bike mga func sa filter 
// tas modals function 

// Temporary data storing wa pamay db
let staffData = [];
let nextStaffId = 1;

// notes para d ko makalimot atay
// Renders staffData into the SAME table your Blade foreach loop builds,
// using the same data-name / data-role / data-status attributes
// so filterStaff(), filterStaffRole(), filterStaffStatus() keep working untouched.
function renderStaffTable() {
  const tbody = document.getElementById('staff-tbody');
  tbody.innerHTML = '';

  staffData.forEach(member => {
    const initials = member.name.substring(0, 2).toUpperCase();
    const status = member.statusValue || 'Active';
    const roleBadge = member.role === 'Manager' ? 'badge-purple'
                      : member.role === 'Technician' ? 'badge-blue'
                      : 'badge-gray';
    const statusBadge = status === 'Active' ? 'badge-green' : 'badge-yellow';

    const row = document.createElement('tr');
    row.dataset.name = member.name;
    row.dataset.role = member.role;
    row.dataset.status = status;

    row.innerHTML = `
      <td>
        <div style="display:flex;align-items:center;gap:12px">
          <div class="avatar" style="flex-shrink:0">${initials}</div>
          <div>
            <div style="font-weight:500;color:#111827">${member.name}</div>
            <div style="font-size:12px;color:#9ca3af">ID: #${String(member.id).padStart(4,'0')}</div>
          </div>
        </div>
      </td>
      <td>
        <div class="contact-cell">
          <div class="contact-line">${member.email}</div>
          <div class="contact-line">${member.phone || 'N/A'}</div>
        </div>
      </td>
      <td><span class="badge ${roleBadge}">${member.role}</span></td>
      <td><span class="badge ${statusBadge}">${status}</span></td>
      <td>
        <button class="action-btn" onclick="openEditStaff('${member.name}','${member.role}','${status}')">
          <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>
        <button class="action-btn delete-btn" onclick="openDeleteStaff('${member.name}')">
          <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </button>
      </td>
    `;
    tbody.appendChild(row);
  });

  document.querySelector('.table-footer p').textContent = `Showing ${staffData.length} staff members`;
}

const addStaffForm = document.querySelector('#add-staff-modal form');
addStaffForm.addEventListener('submit', function (event) {
  if (addStaffForm.dataset.confirmed === 'true') return;

  event.preventDefault();
  if (!addStaffForm.reportValidity()) return;
  if (addStaffForm.password.value !== addStaffForm.password_confirmation.value) {
    addStaffForm.password_confirmation.setCustomValidity('Passwords do not match.');
    addStaffForm.password_confirmation.reportValidity();
    addStaffForm.password_confirmation.setCustomValidity('');
    return;
  }

  const image = addStaffForm.profile_picture.files[0];
  const permissions = Array.from(addStaffForm.querySelectorAll('input[name="permissions[]"]:checked'))
    .map(input => input.value);
  document.getElementById('staff-confirmation-summary').innerHTML = `
    <p><strong>Name:</strong> ${escapeHtml(addStaffForm.name.value.trim())}</p>
    <p><strong>Email:</strong> ${escapeHtml(addStaffForm.email.value.trim())}</p>
    <p><strong>Phone:</strong> ${escapeHtml(addStaffForm.phone.value.trim() || 'N/A')}</p>
    <p><strong>Role:</strong> ${escapeHtml(addStaffForm.role.value)}</p>
    <p><strong>Privileges:</strong> ${escapeHtml(permissions.join(', ') || 'None')}</p>
    <p><strong>Profile picture:</strong> ${image ? escapeHtml(image.name) : 'None'}</p>`;
  closeModal('add-staff-modal');
  openModal('confirm-staff-modal');
});

function confirmStaffCreation() {
  addStaffForm.dataset.confirmed = 'true';
  addStaffForm.requestSubmit();
}

function escapeHtml(value) {
  return value.replace(/[&<>'"]/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
  }[character]));
}

// pretty self-explanatory
function saveEditStaff() {
  const originalName = window._editingStaffOriginalName;
  const member = staffData.find(m => m.name === originalName);
  if (!member) return;

  member.name = document.getElementById('edit-staff-name').value.trim();
  member.role = document.getElementById('edit-staff-role').value;
  member.statusValue = document.getElementById('edit-staff-status').value;

  // TODO: once backend exists, replace the 3 lines above with:
  // fetch(`/staff/${member.id}`, { method: 'PUT', body: JSON.stringify(member), headers: {...} })
  // ^ oo na
  renderStaffTable();
  closeModal('edit-staff-modal');
  showToast(`${member.name} was updated successfully.`);
}

// self-explanatory 
function confirmDeleteStaff() {
  const name = window._deletingStaffName;
  if (!name) return;

  // TODO: once backend exists, replace the line below with:
  // fetch(`/staff/${id}`, { method: 'DELETE' })

  staffData = staffData.filter(m => m.name !== name);

  renderStaffTable();
  closeModal('delete-staff-modal');
  showToast(`${name} was removed.`);
  window._deletingStaffName = null;
}

// toast
function showToast(message) {
  const toast = document.getElementById('toast');
  document.getElementById('toast-message').textContent = message;
  toast.classList.remove('toast-hidden');
  setTimeout(() => toast.classList.add('toast-hidden'), 3000);
}
</script>