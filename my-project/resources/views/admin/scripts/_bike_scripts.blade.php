<script>
let bikeSearchFilter = '', bikeTypeFilter = '', bikeStatusFilter = '';

function filterBikes(q) {
  bikeSearchFilter = q.toLowerCase();
  applyBikeFilters();
}
function filterBikeType(v) { bikeTypeFilter = v; applyBikeFilters(); }
function filterBikeStatus(v) { bikeStatusFilter = v; applyBikeFilters(); }
function applyBikeFilters() {
  const rows = document.querySelectorAll('#bikes-tbody tr');
  let visibleCount = 0;

  rows.forEach(r => {
    const searchOk = !bikeSearchFilter || r.dataset.id.toLowerCase().includes(bikeSearchFilter) || r.dataset.name.toLowerCase().includes(bikeSearchFilter);
    const typeOk = !bikeTypeFilter || r.dataset.type === bikeTypeFilter;
    const statusOk = !bikeStatusFilter || r.dataset.status === bikeStatusFilter;
    const isVisible = searchOk && typeOk && statusOk;

    r.style.display = isVisible ? '' : 'none';
    if (isVisible) visibleCount++;
  });

  document.getElementById('bikes-footer-count').textContent = `Showing ${visibleCount} bikes`;
}
// ── Password verification para sa edit ug delete sa bike ──────────────────
// Pag click sa edit/delete, mo-pop up una ang password modal. Kung sakto ang
// password (gi-check sa backend), unya pa mo-open ang edit form o delete confirmation.
let pendingBikeAction = null;

function requestBikeAction(bikeId, action, details) {
  pendingBikeAction = { bikeId, action, details };
  document.getElementById('bike-password-prompt').textContent =
    action === 'delete'
      ? 'Enter your admin password to delete this bike.'
      : 'Enter your admin password to edit this bike.';
  document.getElementById('bike-password-error').hidden = true;
  document.getElementById('bike-password-form').reset();
  openModal('bike-password-modal');
  document.getElementById('bike-action-password').focus();
}

// same names gihapon sa onclick sa _bikes.blade.php, pero password na ang una
function openEditBike(id, name, type, status, condition) {
  requestBikeAction(id, 'edit', { name, type, status, condition });
}
function openDeleteBike(id, name) {
  requestBikeAction(id, 'delete', { name });
}

document.getElementById('bike-password-form').addEventListener('submit', async function (event) {
  event.preventDefault();
  if (!pendingBikeAction) return;

  const form = event.currentTarget;
  const error = document.getElementById('bike-password-error');
  const submit = form.querySelector('[type="submit"]');
  const verifyUrl = form.dataset.verifyUrl.replace('__BIKE_ID__', encodeURIComponent(pendingBikeAction.bikeId));
  const body = new URLSearchParams(new FormData(form));
  body.set('action', pendingBikeAction.action);
  submit.disabled = true;
  error.hidden = true;

  try {
    const response = await fetch(verifyUrl, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.message || 'Password verification failed.');

    // sakto ang password, i-proceed na sa edit o delete
    const action = pendingBikeAction;
    pendingBikeAction = null;
    closeModal('bike-password-modal');
    if (action.action === 'edit') {
      openEditBikeForm(action.bikeId, action.details.name, action.details.type, action.details.status, action.details.condition);
    } else {
      openDeleteBikeConfirm(action.bikeId, action.details.name);
    }
  } catch (verificationError) {
    // sayop ang password: dili mo-proceed, ipakita lang ang error
    error.textContent = verificationError.message;
    error.hidden = false;
  } finally {
    submit.disabled = false;
    document.getElementById('bike-action-password').value = '';
  }
});

function openEditBikeForm(id, name, type, status, condition) {
  document.getElementById('edit-bike-id').value = id;

  // gi-separate nako ang model ug make kay mao na ang actual fields sa bicycle table
  const nameParts = name.split(' · ');
  document.getElementById('edit-bike-name').value = nameParts.shift() || name;
  document.getElementById('edit-bike-make').value = nameParts.join(' · ');

  document.getElementById('edit-bike-type').value = type;
  document.getElementById('edit-bike-status').value = status;
  document.getElementById('edit-bike-condition').value = condition;
  // gi add nako para: 
  // i-remember kinsa nga bike ang gi-edit (gamit ang id, dili name,
  // kay pwede man magsama og name ang duha ka bike unlike sa staff)
  window._editingBikeId = id;
  // i-set ang form action sa tama nga bike (qr_code) para mo-reach sa admin.bikes.update
  document.getElementById('edit-bike-form').action = `{{ url('/admin/bikes') }}/${encodeURIComponent(id)}`;
  openModal('edit-bike-modal');
}
function openDeleteBikeConfirm(id, name) {
  document.getElementById('delete-bike-name-display').textContent = name + ' (' + id + ')';
  // same sa taas 
  window._deletingBikeId = id;
  openModal('delete-bike-modal');
}
function openQR(id, name, code) {
  document.getElementById('qr-bike-name').textContent = name;
  document.getElementById('qr-bike-id').textContent = id;
  document.getElementById('qr-code-label').textContent = 'Code: ' + code;
  const box = document.getElementById('qr-box');
  const seed = code.split('').reduce((a, c) => a + c.charCodeAt(0), 0);
  let html = '<div class="qr-grid">';
  for (let i = 0; i < 64; i++) {
    const on = (seed * 31 + i * 17 + i * i * 7) % 13 > 5;
    html += `<div class="qr-cell" style="background:${on ? '#111827' : '#fff'}"></div>`;
  }
  html += '</div>';
  box.innerHTML = html;
  openModal('qr-modal');
}
function saveEditBike() {
  const idBeingEdited = window._editingBikeId;
  if (!idBeingEdited) return;

  // TODO: kung naa nay backend, ilisan ni og:
  // fetch(`/bikes/${bike.id}`, { method: 'PUT', body: JSON.stringify(bike), headers: {...} })

  document.getElementById('edit-bike-form').action = `{{ url('/admin/bikes') }}/${encodeURIComponent(idBeingEdited)}`;
  document.getElementById('edit-bike-form').submit();
}


async function confirmDeleteBike() {
  const idBeingDeleted = window._deletingBikeId;
  if (!idBeingDeleted) return;

  // i-disable ang button para dili ma double-click ang delete
  const button = document.getElementById('confirm-delete-bike-btn');
  button.disabled = true;

  try {
    // DELETE request sa admin.bikes.destroy; qr_code (bike_code) ang gamiton sa route
    const response = await fetch(`{{ url('/admin/bikes') }}/${encodeURIComponent(idBeingDeleted)}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    });
    const result = await response.json();
    // kung 422/409/500, i-throw para ma-catch ug ma-toast ang error message
    if (!response.ok) throw new Error(result.message || 'Unable to delete this bike.');

    // successful na sa database, kuhaa na ang row sa table ug i-update ang count
    document.querySelector(`#bikes-tbody tr[data-id="${CSS.escape(idBeingDeleted)}"]`)?.remove();
    applyBikeFilters();
    closeModal('delete-bike-modal');
    showToast(result.message);
    window._deletingBikeId = null;
  } catch (deleteError) {
    // naa gihapon ang bike; ipakita lang ang error ug i-close ang modal
    closeModal('delete-bike-modal');
    showToast(deleteError.message);
  } finally {
    button.disabled = false;
  }
}

@if(session('active_tab') === 'bikes' && $errors->any())
window.addEventListener('DOMContentLoaded', function () {
  // ipakita ang error gikan sa bike edit (pananglitan wala na-verify o sayop ang status)
  showToast(@json($errors->first()));
});
@endif
</script>