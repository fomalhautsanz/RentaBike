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
  openModal('edit-bike-modal');
}
function openDeleteBike(id, name) {
  document.getElementById('delete-bike-name-display').textContent = name + ' (' + id + ')';
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
</script>