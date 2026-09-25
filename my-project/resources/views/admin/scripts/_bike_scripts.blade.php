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
// kini gi add nako function sa modals 
// temp data no db
let bikeData = [];
let nextBikeId = 1;

// same logic sa staff
// gamit gihapon ang parehas nga data-id / data-name / data-type / data-status
// attributes para magpadayon paggana ang filterBikes() ug applyBikeFilters()
// ok 
function renderBikesTable() {
  const tbody = document.getElementById('bikes-tbody');
  tbody.innerHTML = '';

  bikeData.forEach(bike => {
    const bikeId = 'BK' + String(bike.id).padStart(4, '0');
    const statusBadge = bike.status === 'Available' ? 'badge-green'
                        : bike.status === 'Rented' ? 'badge-blue'
                        : 'badge-yellow';
    const conditionBadge = bike.condition === 'Good' ? 'badge-green'
                        : bike.condition === 'Needs Repair' ? 'badge-yellow'
                        : 'badge-gray';

    const row = document.createElement('tr');
    row.dataset.id = bikeId;
    row.dataset.name = bike.name;
    row.dataset.type = bike.type;
    row.dataset.status = bike.status;

    row.innerHTML = `
      <td>
        <div style="font-weight:500;color:#111827">${bike.name}</div>
        <div style="font-size:12px;color:#9ca3af">ID: ${bikeId}</div>
      </td>
      <td>${bike.type}</td>
      <td><span class="badge ${statusBadge}">${bike.status}</span></td>
      <td><span class="badge ${conditionBadge}">${bike.condition}</span></td>
      <td>
        <button class="action-btn" onclick="openEditBike('${bikeId}','${bike.name}','${bike.type}','${bike.status}','${bike.condition}')">
          <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>
        <button class="action-btn delete-btn" onclick="openDeleteBike('${bikeId}','${bike.name}')">
          <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </button>
        <button class="action-btn" onclick="openQR('${bikeId}','${bike.name}','${bikeId}')">
          <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </button>
      </td>
    `;
    tbody.appendChild(row);
  });

  document.querySelector('#page-bikes .table-footer p').textContent = `Showing ${bikeData.length} bikes`;
}

// ADD BIKE FORM
// Instead of letting it submit to action="#", stop it, validate,
// and save directly — no confirmation step anymore
document.querySelector('#add-bike-modal form').addEventListener('submit', function (e) {
  e.preventDefault(); // stops the real submit, no backend to send it to yet

  const form = e.target;
  const name = form.name.value.trim();
  const type = form.type.value;
  const condition = form.condition.value;

  if (!name) {
    showToast('Please fill in the required fields.');
    return;
  }

  // TODO: once backend exists, replace the two lines below with:
  // fetch('/bikes', { method: 'POST', body: JSON.stringify({ name, type, condition }), headers: {...} })
  //   .then(res => res.json())
  //   .then(saved => { bikeData.push(saved); renderBikesTable(); ... });

  bikeData.push({ id: nextBikeId++, name, type, condition, status: 'Available' });
  renderBikesTable();

  closeModal('add-bike-modal');
  form.reset();
  showToast(`${name} was added successfully.`);
});

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