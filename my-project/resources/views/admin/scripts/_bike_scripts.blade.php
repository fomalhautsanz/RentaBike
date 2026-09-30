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
    const statusOk = !bikeStatusFilter || r.dataset.status.toLowerCase() === bikeStatusFilter.toLowerCase();
    const isVisible = searchOk && typeOk && statusOk;

    r.style.display = isVisible ? '' : 'none';
    if (isVisible) visibleCount++;
  });

  document.getElementById('bikes-footer-count').textContent = `Showing ${visibleCount} bikes`;
}
function openEditBike(id, name, type, status, condition) {
  const form = document.getElementById('edit-bike-form');
  const selectedName = String(name || '').split(' · ')[0] || name;
  const make = String(name || '').split(' · ').slice(1).join(' · ') || '';
  const normalizedStatus = status === 'Maintenance' ? 'repair' : (status === 'Rented' ? 'rented' : 'available');
  const normalizedCondition = condition === 'Needs Repair' ? 'repair' : (condition === 'Missing' ? 'missing' : 'good');

  document.getElementById('edit-bike-id').value = id;
  document.getElementById('edit-bike-name').value = selectedName;
  document.getElementById('edit-bike-make').value = make;
  document.getElementById('edit-bike-type').value = type;
  document.getElementById('edit-bike-status').value = normalizedStatus;
  document.getElementById('edit-bike-condition').value = normalizedCondition;
  if (form) form.action = '/admin/bikes/' + encodeURIComponent(id);

  window._editingBikeId = id;
  openModal('edit-bike-modal');
}
function openDeleteBike(id, name) {
  document.getElementById('delete-bike-name-display').textContent = name + ' (' + id + ')';
  const form = document.getElementById('delete-bike-form');
  if (form) form.action = '/admin/bikes/' + encodeURIComponent(id);
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
  const form = document.getElementById('edit-bike-form');
  if (form) form.requestSubmit();
}

function confirmDeleteBike() {
  const form = document.getElementById('delete-bike-form');
  if (form) form.requestSubmit();
  window._deletingBikeId = null;
}
</script>