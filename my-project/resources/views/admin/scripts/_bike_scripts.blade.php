<script>
function filterBikes(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#bikes-tbody tr').forEach(r => {
    r.style.display = (r.dataset.id.toLowerCase().includes(q) || r.dataset.name.toLowerCase().includes(q)) ? '' : 'none';
  });
}
let bikeTypeFilter = '', bikeStatusFilter = '';
function filterBikeType(v) { bikeTypeFilter = v; applyBikeFilters(); }
function filterBikeStatus(v) { bikeStatusFilter = v; applyBikeFilters(); }
function applyBikeFilters() {
  document.querySelectorAll('#bikes-tbody tr').forEach(r => {
    const typeOk = !bikeTypeFilter || r.dataset.type === bikeTypeFilter;
    const statusOk = !bikeStatusFilter || r.dataset.status === bikeStatusFilter;
    r.style.display = typeOk && statusOk ? '' : 'none';
  });
}
function openEditBike(id, name, type, status, condition) {
  document.getElementById('edit-bike-id').value = id;
  document.getElementById('edit-bike-name').value = name;
  document.getElementById('edit-bike-type').value = type;
  document.getElementById('edit-bike-status').value = status;
  document.getElementById('edit-bike-condition').value = condition;
  // gi add nako para: 
  // i-remember kinsa nga bike ang gi-edit (gamit ang id, dili name,
  // kay pwede man magsama og name ang duha ka bike unlike sa staff)
  window._editingBikeId = id;
  openModal('edit-bike-modal');
}
function openDeleteBike(id, name) {
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
  const bikeId = 'BK' + String(idBeingEdited).replace('BK', '').padStart(4, '0');
  const bike = bikeData.find(b => ('BK' + String(b.id).padStart(4, '0')) === idBeingEdited);
  if (!bike) return;

  bike.name = document.getElementById('edit-bike-name').value.trim();
  bike.type = document.getElementById('edit-bike-type').value;
  bike.status = document.getElementById('edit-bike-status').value;
  bike.condition = document.getElementById('edit-bike-condition').value;

  // TODO: kung naa nay backend, ilisan ni og:
  // fetch(`/bikes/${bike.id}`, { method: 'PUT', body: JSON.stringify(bike), headers: {...} })

  renderBikesTable();
  closeModal('edit-bike-modal');
  showToast(`${bike.name} was updated successfully.`);
}


function confirmDeleteBike() {
  const idBeingDeleted = window._deletingBikeId;
  if (!idBeingDeleted) return;

  // TODO: kung naa nay backend, ilisan ni og: 
  // fetch(`/bikes/${id}`, { method: 'DELETE' })

  bikeData = bikeData.filter(b => ('BK' + String(b.id).padStart(4, '0')) !== idBeingDeleted);

  renderBikesTable();
  closeModal('delete-bike-modal');
  showToast('Bike was removed from inventory.');
  window._deletingBikeId = null;
}
</script>