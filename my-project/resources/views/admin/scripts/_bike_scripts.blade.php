<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
let bikeSearchFilter = '', bikeTypeFilter = '', bikeStatusFilter = '';

function updateBikeCodePreview(typeSelect) {
  const form = typeSelect.form;
  const codeInput = document.getElementById(
    form.id === 'edit-bike-form' ? 'edit-bike-id' : 'add-bike-id'
  );
  if (!codeInput) return;

  if (form.id === 'edit-bike-form' && typeSelect.value === form.dataset.originalBikeType) {
    codeInput.dataset.requestId = String(Number(codeInput.dataset.requestId || 0) + 1);
    codeInput.value = form.dataset.originalBikeCode;
    return;
  }

  const requestId = Number(codeInput.dataset.requestId || 0) + 1;
  codeInput.dataset.requestId = String(requestId);
  const params = new URLSearchParams({ bike_type: typeSelect.value });
  if (form.dataset.excludeQrCode) params.set('exclude_qr_code', form.dataset.excludeQrCode);

  fetch(`${form.dataset.bikeCodePreviewUrl}?${params}`, {
    headers: { Accept: 'application/json' },
  })
    .then(async response => {
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || 'Could not preview the bike ID.');
      return result;
    })
    .then(result => {
      if (codeInput.dataset.requestId === String(requestId)) codeInput.value = result.bike_code;
    })
    .catch(error => {
      if (codeInput.dataset.requestId === String(requestId)) {
        showToast(error.message || 'Could not preview the bike ID.');
      }
    });
}

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

let adminRentalOptions = [];
let adminRentalSubmitting = false;
let adminRentalOptionsRequest = 0;
let adminRentReturnContext = null;

function openAdminRent(bikeId, bikeName, optionsUrl, rentUrl) {
  const requestId = ++adminRentalOptionsRequest;
  const form = document.getElementById('admin-rent-form');
  form.reset();
  adminRentalOptions = [];
  form.dataset.optionsUrl = optionsUrl.replace('__BIKE__', encodeURIComponent(bikeId));
  form.dataset.rentUrl = rentUrl.replace('__BIKE__', encodeURIComponent(bikeId));
  document.getElementById('admin-rent-bike-name').textContent = bikeName;
  document.getElementById('admin-rent-bike-id').textContent = bikeId;
  document.getElementById('admin-rent-bike-type').textContent = 'Loading rental options…';
  document.getElementById('admin-rent-id-name').textContent = 'Choose a JPG, PNG, WEBP, or PDF';
  document.getElementById('admin-rent-duration').innerHTML = '';
  document.getElementById('admin-rent-time').value = '';
  document.getElementById('admin-rent-total').textContent = '₱0';
  document.getElementById('admin-rent-expected-return').textContent = '—';
  document.getElementById('admin-rent-error').hidden = true;
  const submit = document.getElementById('admin-rent-submit');
  submit.disabled = true;
  submit.textContent = 'Loading…';
  openModal('admin-rent-modal');

  fetch(form.dataset.optionsUrl, { headers: { Accept: 'application/json' } })
    .then(async response => {
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || 'This bike is not available for rental.');
      return result;
    })
    .then(result => {
      if (requestId !== adminRentalOptionsRequest) return;
      adminRentalOptions = result.pricing.options;
      document.getElementById('admin-rent-bike-type').textContent = result.bike.bike_type;
      document.getElementById('admin-rent-time').value = result.server_time || '';
      const duration = document.getElementById('admin-rent-duration');
      duration.innerHTML = '';
      adminRentalOptions.forEach(option => {
        const element = new Option(`${option.label} · ${formatAdminPeso(option.price)}`, option.minutes);
        duration.add(element);
      });
      if (!adminRentalOptions.length) throw new Error('No rental options are configured for this bike.');
      submit.disabled = false;
      submit.textContent = 'Confirm Rental';
      updateAdminRentSummary();
    })
    .catch(error => {
      if (requestId !== adminRentalOptionsRequest) return;
      const errorEl = document.getElementById('admin-rent-error');
      errorEl.textContent = error.message || 'Could not load rental options.';
      errorEl.hidden = false;
      submit.textContent = 'Unavailable';
    });
}

function startAdminRentFromEdit(previousStatus) {
    const form = document.getElementById('edit-bike-form');
    const context = {
      bikeId: document.getElementById('edit-bike-id').value,
      model: document.getElementById('edit-bike-name').value,
      make: document.getElementById('edit-bike-make').value,
      type: document.getElementById('edit-bike-type').value,
      status: previousStatus,
      condition: document.getElementById('edit-bike-condition').value,
    };
    adminRentReturnContext = context;
    closeModal('edit-bike-modal');
    openAdminRent(
      context.bikeId,
      [context.model, context.make].filter(Boolean).join(' · '),
      form.dataset.rentalOptionsUrl,
      form.dataset.rentUrl
    );
}

function cancelAdminRent() {
    closeModal('admin-rent-modal');
    if (!adminRentReturnContext) return;

    const context = adminRentReturnContext;
    adminRentReturnContext = null;
    const typeSelect = document.getElementById('edit-bike-type');
    if (![...typeSelect.options].some(option => option.value === context.type)) {
      typeSelect.add(new Option(context.type, context.type));
    }
    document.getElementById('edit-bike-id').value = context.bikeId;
    document.getElementById('edit-bike-name').value = context.model;
    document.getElementById('edit-bike-make').value = context.make;
    typeSelect.value = context.type;
    const statusSelect = document.getElementById('edit-bike-status');
    statusSelect.value = context.status;
    statusSelect.dataset.previousValue = context.status;
    setEditBikeConditionOptions(context.status, context.condition);
    openModal('edit-bike-modal');
}

function closeAdminRentOutside(event) {
    if (event.target === event.currentTarget) cancelAdminRent();
}

function formatAdminPeso(amount) {
  return `₱${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
}

function updateAdminRentSummary() {
  const minutes = Number(document.getElementById('admin-rent-duration').value);
  const option = adminRentalOptions.find(item => item.minutes === minutes);
  const time = document.getElementById('admin-rent-time').value;
  document.getElementById('admin-rent-total').textContent = option ? formatAdminPeso(option.price) : '₱0';

  if (!option || !time) {
    document.getElementById('admin-rent-expected-return').textContent = '—';
    return;
  }

  const [hours, mins] = time.split(':').map(Number);
  const end = hours * 60 + mins + option.minutes;
  const endHours = Math.floor(end / 60) % 24;
  const endMinutes = end % 60;
  const suffix = endHours >= 12 ? 'PM' : 'AM';
  const displayHour = endHours % 12 || 12;
  const label = `${displayHour}:${String(endMinutes).padStart(2, '0')} ${suffix}`;
  document.getElementById('admin-rent-expected-return').textContent = end >= 1440 ? `${label} (next day)` : label;
}

document.getElementById('admin-rent-form').addEventListener('submit', async function (event) {
  event.preventDefault();
  if (adminRentalSubmitting) return;

  const form = event.currentTarget;
  const errorEl = document.getElementById('admin-rent-error');
  const submit = document.getElementById('admin-rent-submit');
  const file = document.getElementById('admin-rent-id').files[0];
  if (!file) {
    errorEl.textContent = "Please upload the borrower's valid ID.";
    errorEl.hidden = false;
    return;
  }
  if (file.size > 2 * 1024 * 1024) {
    errorEl.textContent = 'The borrower ID must be 2 MB or smaller.';
    errorEl.hidden = false;
    return;
  }

  adminRentalSubmitting = true;
  submit.disabled = true;
  errorEl.hidden = true;
  try {
    const response = await fetch(form.dataset.rentUrl, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: new FormData(form),
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.message || 'Could not save the rental.');
    showToast('Bike rented successfully.');
    closeModal('admin-rent-modal');
    window.setTimeout(() => window.location.reload(), 500);
  } catch (error) {
    errorEl.textContent = error.message || 'Could not save the rental.';
    errorEl.hidden = false;
  } finally {
    adminRentalSubmitting = false;
    submit.disabled = false;
    submit.textContent = 'Confirm Rental';
  }
});

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

document.getElementById('admin-rent-id').addEventListener('change', function () {
  const file = this.files && this.files[0];
  document.getElementById('admin-rent-id-name').textContent =
    file ? file.name : 'Choose a JPG, PNG, WEBP, or PDF';
  document.getElementById('admin-rent-error').hidden = true;
});

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

function setEditBikeConditionOptions(status, preferredCondition) {
  const conditionSelect = document.getElementById('edit-bike-condition');
  const isMaintenance = status === 'repair';
  const options = isMaintenance
    ? [['repair', 'Damage'], ['missing', 'Missing'], ['other', 'Other']]
    : [['good', 'Good']];

  conditionSelect.innerHTML = options
    .map(([value, label]) => `<option value="${value}">${label}</option>`)
    .join('');
  conditionSelect.value = options.some(([value]) => value === preferredCondition)
    ? preferredCondition
    : (isMaintenance ? 'repair' : 'good');
}

document.getElementById('edit-bike-status').addEventListener('change', function () {
  if (this.value === 'rented' && this.dataset.previousValue !== 'rented') {
    const previousStatus = this.dataset.previousValue || 'available';
    this.value = previousStatus;
    startAdminRentFromEdit(previousStatus);
    return;
  }

  this.dataset.previousValue = this.value;
  setEditBikeConditionOptions(this.value, this.value === 'repair' ? 'repair' : 'good');
});

function openEditBikeForm(id, name, type, status, condition) {
  const form = document.getElementById('edit-bike-form');
  const typeSelect = document.getElementById('edit-bike-type');
  const selectedName = String(name || '').split(' · ')[0] || name;
  const make = String(name || '').split(' · ').slice(1).join(' · ') || '';
  const rawStatus = String(status || '').toLowerCase();
  const normalizedStatus = rawStatus === 'maintenance' || rawStatus === 'repair'
    ? 'repair'
    : (rawStatus === 'rented' ? 'rented' : 'available');
  const rawCondition = String(condition || '').toLowerCase();
  const normalizedCondition = ['repair', 'damage', 'needs repair'].includes(rawCondition)
    ? 'repair'
    : (['missing', 'other'].includes(rawCondition) ? rawCondition : 'good');

  if (![...typeSelect.options].some(option => option.value === type)) {
    typeSelect.add(new Option(type, type));
  }
  document.getElementById('edit-bike-id').value = id;
  document.getElementById('edit-bike-name').value = selectedName;
  document.getElementById('edit-bike-make').value = make;
  typeSelect.value = type;
  form.dataset.originalBikeType = type;
  form.dataset.originalBikeCode = id;
  form.dataset.excludeQrCode = id;
  const statusSelect = document.getElementById('edit-bike-status');
  statusSelect.value = normalizedStatus;
  statusSelect.dataset.previousValue = normalizedStatus;
  setEditBikeConditionOptions(normalizedStatus, normalizedCondition);
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