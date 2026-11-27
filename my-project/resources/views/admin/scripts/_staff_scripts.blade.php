<script>
const canManageSuperAdmins = @json(\App\Support\AdminAccess::canManageSuperAdmins());
const fullAccessPermissions = [
  'View Inventory',
  'Add Inventory',
  'Edit Inventory',
  'Delete Inventory',
  'Manage Staff',
  'Handle Maintenance',
  'Manage Pricing'
];

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
function toggleManageStaffPermission(roleSelect, modalId) {
  const isAdmin = ['Admin', 'Super Admin'].includes(roleSelect.value);
  document.querySelectorAll(`#${modalId} [data-admin-only-permission]`).forEach(option => {
    option.style.display = isAdmin ? '' : 'none';
    if (!isAdmin) option.querySelector('input').checked = false;
  });
}

let pendingSuperAdminChange = null;

function showSuperAdminConfirmation(action, accountName, formModalId, onConfirm, onCancel = null) {
  pendingSuperAdminChange = { action, formModalId, onConfirm, onCancel };
  closeModal(formModalId);

  const isGrant = action === 'grant';
  document.getElementById('super-admin-confirm-title').textContent = isGrant
    ? 'Grant Super Admin Access?'
    : 'Demote Super Admin?';
  document.getElementById('super-admin-confirm-message').textContent = isGrant
    ? `You are about to grant ${accountName} full access to the admin portal, including staff, inventory, and maintenance.`
    : `This will remove Super Admin status from ${accountName}. They will keep only the permissions selected in the form.`;
  document.getElementById('super-admin-confirm-button').textContent = isGrant
    ? 'Grant Full Access'
    : 'Demote and Save';

  openModal('super-admin-confirm-modal');
}

function showSuperAdminPermissionDenied(formModalId) {
  pendingSuperAdminChange = {
    formModalId,
    onConfirm: () => openModal(formModalId)
  };
  closeModal(formModalId);
  document.getElementById('super-admin-confirm-title').textContent = 'Permission Denied';
  document.getElementById('super-admin-confirm-message').textContent =
    'You do not have permission to grant Super Admin access. Uncheck one or more permissions or ask a Super Admin.';
  document.getElementById('super-admin-confirm-button').textContent = 'Return to Form';
  openModal('super-admin-confirm-modal');
}

function cancelSuperAdminChange() {
  if (!pendingSuperAdminChange) return;

  const { formModalId, onCancel } = pendingSuperAdminChange;
  pendingSuperAdminChange = null;
  closeModal('super-admin-confirm-modal');
  if (onCancel) onCancel();
  openModal(formModalId);
}

function confirmSuperAdminChange() {
  if (!pendingSuperAdminChange) return;

  const { onConfirm } = pendingSuperAdminChange;
  pendingSuperAdminChange = null;
  closeModal('super-admin-confirm-modal');
  onConfirm();
}

function hasFullAccessPermissions(form) {
  const selected = new Set(getSelectedPermissions(form));
  return fullAccessPermissions.every(permission => selected.has(permission));
}

function getSelectedPermissions(form) {
  return Array.from(form.querySelectorAll('input[name="permissions[]"]:checked'))
    .map(input => input.value);
}

function setStaffPermissions(form, roleSelect, modalId, role, permissions) {
  const selected = new Set(permissions);
  roleSelect.value = role;
  form.querySelectorAll('input[name="permissions[]"]').forEach(input => {
    input.checked = selected.has(input.value);
  });
  syncInventoryPermissionOptions(modalId);
  toggleManageStaffPermission(roleSelect, modalId);
}

function selectSuperAdminPermissions(form, roleSelect, modalId, role = 'Super Admin') {
  setStaffPermissions(form, roleSelect, modalId, role, fullAccessPermissions);
}

function makeAddStaffSuperAdmin() {
  const roleSelect = document.getElementById('add-staff-role');
  addStaffForm.dataset.superAdminPreviousState = JSON.stringify({
    role: roleSelect.value,
    permissions: getSelectedPermissions(addStaffForm)
  });
  selectSuperAdminPermissions(addStaffForm, roleSelect, 'add-staff-modal', 'Admin');
  addStaffForm.requestSubmit();
}

function makeEditStaffSuperAdmin() {
  if (!canManageSuperAdmins) return;

  selectSuperAdminPermissions(editStaffForm, document.getElementById('edit-staff-role'), 'edit-staff-modal');
  editStaffForm.requestSubmit();
}

function toggleInventoryPermissionOptions(viewCheckbox, modalId) {
  const options = document.querySelector(`#${modalId} [data-inventory-options]`);
  if (!options) return;

  if (!viewCheckbox.checked) {
    options.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      checkbox.checked = false;
    });
  }

  options.hidden = !viewCheckbox.checked;
}

function syncInventoryPermissionOptions(modalId) {
  const modal = document.getElementById(modalId);
  const viewCheckbox = modal.querySelector('input[value="View Inventory"]');
  const options = modal.querySelector('[data-inventory-options]');
  if (!viewCheckbox || !options) return;

  const hasInventoryAction = Array.from(options.querySelectorAll('input[type="checkbox"]'))
    .some(checkbox => checkbox.checked);
  if (hasInventoryAction) viewCheckbox.checked = true;
  options.hidden = !viewCheckbox.checked;
}

function populateEditStaff(staffId) {
  const row = document.querySelector(`#staff-tbody tr[data-id="${staffId}"]`);
  if (!row) return;

  const permissions = row.dataset.permissions || '';
  const firstName = row.dataset.firstName;
  const lastName = row.dataset.lastName;
  const email = row.dataset.email;
  const phone = row.dataset.phone;
  const role = row.dataset.role;
  const status = row.dataset.status;
  const form = document.getElementById('edit-staff-form');
  form.dataset.originalRole = role;
  form.dataset.superAdminConfirmed = '';
  const promoteButton = document.getElementById('edit-super-admin-button');
  if (promoteButton) promoteButton.hidden = role === 'Super Admin';
  document.getElementById('edit-staff-first-name').value = firstName;
  document.getElementById('edit-staff-last-name').value = lastName;
  document.getElementById('edit-staff-email').value = email;
  document.getElementById('edit-staff-phone').value = phone;
  document.getElementById('edit-staff-role').value = role;
  document.getElementById('edit-staff-status').value = status;

  const selectedPermissions = role === 'Super Admin'
    ? fullAccessPermissions
    : (Array.isArray(permissions)
      ? permissions
      : (typeof permissions === 'string' ? permissions.split(',').map(p => p.trim()).filter(Boolean) : []));
  form.dataset.originalPermissions = JSON.stringify(selectedPermissions);

  document.querySelectorAll('#edit-staff-modal .permission-checkbox').forEach(cb => {
    cb.checked = selectedPermissions.includes(cb.value);
  });
  syncInventoryPermissionOptions('edit-staff-modal');
  toggleManageStaffPermission(document.getElementById('edit-staff-role'), 'edit-staff-modal');

  form.action = `/admin/staff/${staffId}`;
}

const editStaffForm = document.getElementById('edit-staff-form');
editStaffForm.addEventListener('submit', function (event) {
  if (editStaffForm.dataset.superAdminConfirmed === 'true') {
    delete editStaffForm.dataset.superAdminConfirmed;
    return;
  }
  const roleSelect = document.getElementById('edit-staff-role');
  const selectedRole = roleSelect.value;
  const originalRole = editStaffForm.dataset.originalRole;
  const hasFullAccess = hasFullAccessPermissions(editStaffForm);
  if (!canManageSuperAdmins) {
    if (hasFullAccess && selectedRole === 'Admin') {
      event.preventDefault();
      showSuperAdminPermissionDenied('edit-staff-modal');
    }
    return;
  }

  const isDemotion = originalRole === 'Super Admin'
    && (selectedRole !== 'Super Admin' || !hasFullAccess);
  const isPromotion = !isDemotion
    && hasFullAccess
    && ['Admin', 'Super Admin'].includes(selectedRole)
    && originalRole !== 'Super Admin';

  if (!isDemotion && !isPromotion) return;
  event.preventDefault();

  const accountName = `${document.getElementById('edit-staff-first-name').value.trim()} ${document.getElementById('edit-staff-last-name').value.trim()}`.trim();
  showSuperAdminConfirmation(isPromotion ? 'grant' : 'demote', accountName, 'edit-staff-modal', function () {
    if (isPromotion) {
      roleSelect.value = 'Super Admin';
    } else if (roleSelect.value === 'Super Admin') {
      roleSelect.value = 'Admin';
    }

    editStaffForm.dataset.superAdminConfirmed = 'true';
    toggleManageStaffPermission(roleSelect, 'edit-staff-modal');
    openModal('edit-staff-modal');
    editStaffForm.requestSubmit();
  }, function () {
    if (isPromotion) {
      setStaffPermissions(
        editStaffForm,
        roleSelect,
        'edit-staff-modal',
        originalRole,
        JSON.parse(editStaffForm.dataset.originalPermissions || '[]')
      );
    } else {
      selectSuperAdminPermissions(editStaffForm, roleSelect, 'edit-staff-modal');
    }
  });
});

let pendingStaffAction = null;

function requestStaffAction(staffId, action, name = '') {
  pendingStaffAction = { staffId, action, name };
  const prompt = action === 'edit'
    ? 'Enter your admin password before editing this account.'
    : `Enter your admin password before removing ${name}.`;
  document.getElementById('staff-password-prompt').textContent = prompt;
  document.getElementById('staff-password-error').hidden = true;
  document.getElementById('staff-password-form').reset();
  openModal('staff-password-modal');
  document.getElementById('staff-action-password').focus();
}

function openEditStaff(staffId) {
  requestStaffAction(staffId, 'edit');
}

function openDeleteStaff(staffId) {
  const row = document.querySelector(`#staff-tbody tr[data-id="${staffId}"]`);
  if (row) requestStaffAction(staffId, 'delete', row.dataset.name);
}

document.getElementById('staff-password-form').addEventListener('submit', async function (event) {
  event.preventDefault();
  if (!pendingStaffAction) return;

  const form = event.currentTarget;
  const error = document.getElementById('staff-password-error');
  const submit = form.querySelector('[type="submit"]');
  const verifyUrl = form.dataset.verifyUrl.replace('__STAFF_ID__', pendingStaffAction.staffId);
  const body = new URLSearchParams(new FormData(form));
  body.set('action', pendingStaffAction.action);
  submit.disabled = true;
  error.hidden = true;

  try {
    const response = await fetch(verifyUrl, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body
    });
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
      throw new Error(response.redirected
        ? 'Your admin session has expired. Please log in again.'
        : 'The server returned an unexpected response. Please refresh and try again.');
    }
    const result = await response.json();
    if (!response.ok) throw new Error(result.message || 'Password verification failed.');

    const action = pendingStaffAction;
    pendingStaffAction = null;
    closeModal('staff-password-modal');
    if (action.action === 'edit') {
      populateEditStaff(action.staffId);
      openModal('edit-staff-modal');
    } else {
      document.getElementById('delete-staff-name-display').textContent = action.name;
      window._deletingStaffId = action.staffId;
      openModal('delete-staff-modal');
    }
  } catch (verificationError) {
    error.textContent = verificationError.message;
    error.hidden = false;
  } finally {
    submit.disabled = false;
    document.getElementById('staff-action-password').value = '';
  }
});
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

function resetAddStaffForm() {
  if (!addStaffForm) return;

  addStaffForm.reset();
  addStaffForm.dataset.confirmed = 'false';
  delete addStaffForm.dataset.superAdminConfirmed;
  delete addStaffForm.dataset.superAdminPreviousState;
  addStaffForm.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
  toggleInventoryPermissionOptions(
    addStaffForm.querySelector('input[value="View Inventory"]'),
    'add-staff-modal'
  );
  toggleManageStaffPermission(document.getElementById('add-staff-role'), 'add-staff-modal');
}

function showDuplicateStaffModal(message = 'A staff member with this email is already registered.') {
  const duplicateMessage = document.getElementById('duplicate-staff-message');
  if (duplicateMessage) {
    duplicateMessage.textContent = message;
  }
  resetAddStaffForm();
  openModal('duplicate-staff-modal');
}

function showAddStaffConfirmation() {
  const image = addStaffForm.profile_picture.files[0];
  const permissions = Array.from(addStaffForm.querySelectorAll('input[name="permissions[]"]:checked'))
    .map(input => input.value);
  document.getElementById('staff-confirmation-summary').innerHTML = `
    <p><strong>Name:</strong> ${escapeHtml(`${addStaffForm.first_name.value.trim()} ${addStaffForm.last_name.value.trim()}`.trim())}</p>
    <p><strong>Email:</strong> ${escapeHtml(addStaffForm.email.value.trim())}</p>
    <p><strong>Phone:</strong> ${escapeHtml(addStaffForm.phone.value.trim() || 'N/A')}</p>
    <p><strong>Role:</strong> ${escapeHtml(addStaffForm.role.value)}</p>
    <p><strong>Privileges:</strong> ${escapeHtml(permissions.join(', ') || 'None')}</p>
    <p><strong>Profile picture:</strong> ${image ? escapeHtml(image.name) : 'None'}</p>`;
  closeModal('add-staff-modal');
  openModal('confirm-staff-modal');
}

function getAddStaffName() {
  return `${addStaffForm.first_name.value.trim()} ${addStaffForm.last_name.value.trim()}`.trim();
}

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

  const roleSelect = document.getElementById('add-staff-role');
  const hasFullAccess = hasFullAccessPermissions(addStaffForm);
  const accountName = getAddStaffName() || 'this account';

  if (!canManageSuperAdmins && hasFullAccess && roleSelect.value === 'Admin') {
    event.preventDefault();
    showSuperAdminPermissionDenied('add-staff-modal');
    return;
  }

  if (roleSelect.value === 'Super Admin' && !hasFullAccess) {
    showSuperAdminConfirmation('demote', accountName, 'add-staff-modal', function () {
      roleSelect.value = 'Admin';
      addStaffForm.dataset.superAdminConfirmed = 'demote';
      toggleManageStaffPermission(roleSelect, 'add-staff-modal');
      showAddStaffConfirmation();
    }, function () {
      selectSuperAdminPermissions(addStaffForm, roleSelect, 'add-staff-modal');
    });
    return;
  }

  if (canManageSuperAdmins && hasFullAccess && ['Admin', 'Super Admin'].includes(roleSelect.value)
      && (roleSelect.value !== 'Super Admin' || addStaffForm.dataset.superAdminConfirmed !== 'grant')) {
    const previousState = addStaffForm.dataset.superAdminPreviousState
      ? JSON.parse(addStaffForm.dataset.superAdminPreviousState)
      : { role: roleSelect.value, permissions: getSelectedPermissions(addStaffForm) };
    showSuperAdminConfirmation('grant', accountName, 'add-staff-modal', function () {
      roleSelect.value = 'Super Admin';
      addStaffForm.dataset.superAdminConfirmed = 'grant';
      delete addStaffForm.dataset.superAdminPreviousState;
      toggleManageStaffPermission(roleSelect, 'add-staff-modal');
      showAddStaffConfirmation();
    }, function () {
      setStaffPermissions(
        addStaffForm,
        roleSelect,
        'add-staff-modal',
        previousState.role,
        previousState.permissions
      );
      delete addStaffForm.dataset.superAdminPreviousState;
    });
    return;
  }

  showAddStaffConfirmation();
});

function confirmStaffCreation() {
  addStaffForm.dataset.confirmed = 'true';
  addStaffForm.requestSubmit();
}

window.addEventListener('DOMContentLoaded', function () {
  const duplicateMessage = @json($errors->first('email') ?? '');
  if (duplicateMessage) {
    showDuplicateStaffModal(duplicateMessage);
  }
});

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

  member.name = `${document.getElementById('edit-staff-first-name').value.trim()} ${document.getElementById('edit-staff-last-name').value.trim()}`.trim();
  member.role = document.getElementById('edit-staff-role').value;
  member.statusValue = document.getElementById('edit-staff-status').value;

  // TODO: once backend exists, replace the 3 lines above with:
  // fetch(`/staff/${member.id}`, { method: 'PUT', body: JSON.stringify(member), headers: {...} })
  // ^ oo na
  renderStaffTable();
  closeModal('edit-staff-modal');
  showToast(`${member.name} was updated successfully.`);
}

async function confirmDeleteStaff() {
  const staffId = window._deletingStaffId;
  if (!staffId) return;

  const token = document.querySelector('#staff-password-form [name="_token"]').value;
  const button = document.querySelector('#delete-staff-modal .btn-danger');
  button.disabled = true;

  try {
    const response = await fetch(`/admin/staff/${staffId}`, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.message || 'Unable to remove this account.');

    document.querySelector(`#staff-tbody tr[data-id="${staffId}"]`)?.remove();
    document.querySelector('.table-footer p').textContent = `Showing ${document.querySelectorAll('#staff-tbody tr').length} staff members`;
    closeModal('delete-staff-modal');
    showToast(result.message);
    window._deletingStaffId = null;
  } catch (deleteError) {
    showToast(deleteError.message);
  } finally {
    button.disabled = false;
  }
}

// toast
function showToast(message) {
  const toast = document.getElementById('toast');
  document.getElementById('toast-message').textContent = message;
  toast.classList.remove('toast-hidden');
  setTimeout(() => toast.classList.add('toast-hidden'), 3000);
}
</script>