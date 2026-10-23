<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
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

    const action = pendingBikeAction;
    pendingBikeAction = null;
    closeModal('bike-password-modal');
    if (action.action === 'edit') {
      openEditBikeForm(action.bikeId, action.details.name, action.details.type, action.details.status, action.details.condition);
    } else {
      openDeleteBikeConfirm(action.bikeId, action.details.name);
    }
  } catch (verificationError) {
    error.textContent = verificationError.message;
    error.hidden = false;
  } finally {
    submit.disabled = false;
    document.getElementById('bike-action-password').value = '';
  }
});

function openEditBikeForm(id, name, type, status, condition) {
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

function openDeleteBikeConfirm(id, name) {
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

  const url = 'https://quickchart.io/qr?' + new URLSearchParams({
    text: code, size: 220, margin: 2, ecLevel: 'M', caption: code
  });
  document.getElementById('qr-box').innerHTML =
    `<img src="${url}" alt="QR for ${code}" width="220" height="220">`;
  window._qrUrl = url;
  window._qrCode = code;
  openModal('qr-modal');
}

function qrImageUrl(code, size = 300, withCaption = true) {
  const params = { text: code, size, margin: 2, ecLevel: 'M' };
  if (withCaption) params.caption = code;
  return 'https://quickchart.io/qr?' + new URLSearchParams(params);
}

const QR_PREVIEW_LIMIT = 12;
const QR_BATCH_SIZE = 5;

// Fetches QR images a few at a time so hundreds of bikes don't hit QuickChart's rate limit.
async function fetchQrImages(codes, withCaption, onProgress) {
  const images = {};
  let done = 0;
  for (let i = 0; i < codes.length; i += QR_BATCH_SIZE) {
    await Promise.all(codes.slice(i, i + QR_BATCH_SIZE).map(async code => {
      const res = await fetch(qrImageUrl(code, 300, withCaption));
      if (!res.ok) throw new Error(`QR request failed for ${code}`);
      images[code] = await res.blob();
      onProgress(++done, codes.length);
    }));
  }
  return images;
}

function blobToDataUrl(blob) {
  return new Promise(resolve => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.readAsDataURL(blob);
  });
}

// Bikes currently visible in the table, so the search/type/status filters decide what gets printed.
function visibleBikeCodes() {
  return [...document.querySelectorAll('#bikes-tbody tr')]
    .filter(r => r.style.display !== 'none')
    .map(r => r.dataset.id);
}

function openQrSheet() {
  const codes = visibleBikeCodes();
  window._qrSheetCodes = codes;
  document.getElementById('qr-sheet-count').textContent =
    `${codes.length} bike${codes.length === 1 ? '' : 's'}`;
  const preview = codes.slice(0, QR_PREVIEW_LIMIT);
  const remaining = codes.length - preview.length;
  document.getElementById('qr-sheet-grid').innerHTML = preview.map(code =>
    `<div class="qr-sheet-item"><img src="${qrImageUrl(code, 200, false)}" alt="QR for ${code}">${code}</div>`
  ).join('');
  document.getElementById('qr-sheet-more').textContent =
    remaining > 0 ? `and ${remaining} more (included in Print and ZIP)` : '';
  openModal('qr-sheet-modal');
}

async function printQrSheet() {
  const codes = window._qrSheetCodes || [];
  if (!codes.length) return;
  // Open the tab right away (inside the click) so the browser doesn't block it as a pop-up.
  const win = window.open('', '_blank');
  if (!win) {
    alert('Please allow pop-ups for this site to print the QR sheet.');
    return;
  }
  win.document.write('<p style="font-family:sans-serif">Loading QR codes...</p>');

  const btn = document.getElementById('qr-print-btn');
  btn.disabled = true;
  try {
    const images = await fetchQrImages(codes, false, (done, total) => {
      btn.textContent = `Loading ${done}/${total}...`;
    });
    const items = await Promise.all(codes.map(async code =>
      `<div class="item"><img src="${await blobToDataUrl(images[code])}">${code}</div>`
    ));
    win.document.open();
    win.document.write(`<!doctype html><html><head><title>Bike QR Codes</title><style>
      body { font-family: sans-serif; margin: 16px; }
      .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
      .item { text-align: center; font-weight: 600; font-size: 14px; border: 1px dashed #ccc; padding: 10px; break-inside: avoid; }
      .item img { width: 100%; max-width: 160px; display: block; margin: 0 auto 6px; }
    </style></head><body onload="window.print()"><div class="grid">${items.join('')}</div></body></html>`);
    win.document.close();
  } catch (e) {
    win.close();
    alert('Could not load the QR codes. Check your internet connection and try again.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Print';
  }
}

async function downloadQrZip() {
  const codes = window._qrSheetCodes || [];
  if (!codes.length) return;
  const btn = document.getElementById('qr-zip-btn');
  btn.disabled = true;
  btn.textContent = 'Preparing...';
  try {
    const zip = new JSZip();
    // Each PNG keeps its caption, since a single file has no label beside it.
    const images = await fetchQrImages(codes, true, (done, total) => {
      btn.textContent = `Preparing ${done}/${total}...`;
    });
    codes.forEach(code => zip.file(`${code}.png`, images[code]));
    const blob = await zip.generateAsync({ type: 'blob' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'bike-qr-codes.zip';
    a.click();
    URL.revokeObjectURL(a.href);
  } catch (e) {
    alert('Could not download the QR codes. Check your internet connection and try again.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Download ZIP';
  }
}

async function downloadQR() {
  const blob = await (await fetch(window._qrUrl)).blob();
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `${window._qrCode}.png`;
  a.click();
  URL.revokeObjectURL(a.href);
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