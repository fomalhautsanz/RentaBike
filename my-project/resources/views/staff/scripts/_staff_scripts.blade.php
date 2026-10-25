<script>
// ── NAVIGATION ───────────────────────────────────────────────────────────────
function goTo(id) {
  const target = document.getElementById(id);
  if (!target) {
    if (id === 'inventory') {
      showToast('You do not have access to inventory.\nPlease contact your administrator.');
    } else if (id === 'report') {
      showToast('You do not have access to maintenance reports.\nPlease contact your administrator.');
    }
    return;
  }

  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  target.classList.add('active');
  window.scrollTo(0, 0);

  // Camera: ablihi ra kung naa sa scanner screen, ug i-stop dayon kung mobiya (back, nav, ug uban pa)
  const wasOnScanner = scannerScreenOpen;
  scannerScreenOpen = (id === 'scanner');
  if (scannerScreenOpen) {
    startCameraScan();
  } else if (wasOnScanner) {
    stopCameraScan();
    hideSessionSheet();
    pendingBikeCode = null; // clear ang stale nga "Scan to Rent/Return" kung wala nahuman
  }
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

// ── Real camera scanning (html5-qrcode) ──
// Ang QR nga gi-generate sa admin (QuickChart) mao ra ang BARE qr_code, pananglitan "RB-004".
const SCAN_DEBOUNCE_MS = 3000;      // parehas nga code sulod sa 3s, i-ignore
let scannerScreenOpen = false;
let qrScanner = null;               // Html5Qrcode instance
let scannerToken = 0;               // para ma-ignore ang start() nga human na mobiya sa screen
let scannerBusy = false;            // naa pay gi-check nga bike
let resumeCamera = false;           // i-balik ang camera kung mobalik sa tab
let lastScanRaw = null;
let lastScanAt = 0;

function setScannerStatus(message, isError = false) {
  const el = document.getElementById('scanner-status');
  if (!el) return;
  el.textContent = message;
  el.classList.toggle('is-error', isError);
}

function setScannerFrameOff(isOff) {
  const inner = document.querySelector('.scanner-inner');
  if (inner) inner.classList.toggle('is-off', isOff);
}

function setScannerRetry(show) {
  const btn = document.getElementById('scanner-retry');
  if (btn) btn.classList.toggle('show', show);
}

// Bare code ra ang dawaton (letters, numbers, - ug _). Ang uban (URL, text, ug uban pa) = invalid.
function parseBikeCode(raw) {
  const code = String(raw ?? '').trim();
  return /^[A-Za-z0-9_-]{1,100}$/.test(code) ? code : null;
}

function classifyCameraError(err) {
  const text = String((err && (err.name || err.message)) || err || '').toLowerCase();
  if (text.includes('notallowed') || text.includes('permission') || text.includes('denied')) return 'denied';
  if (text.includes('overconstrained') || text.includes('constraint')) return 'constraint';
  if (text.includes('notfound') || text.includes('no camera') || text.includes('requested device not found')) return 'none';
  if (text.includes('notreadable') || text.includes('trackstart') || text.includes('could not start video source') || text.includes('in use')) return 'busy';
  return 'other';
}

function showCameraProblem(statusText, toastText) {
  setScannerStatus(statusText, true);
  setScannerFrameOff(true);
  setScannerRetry(true);
  showToast(toastText || statusText);
}

function handleCameraError(err) {
  switch (classifyCameraError(err)) {
    case 'denied':
      showCameraProblem('Camera permission denied. Allow camera access in your browser settings, or enter the bike code below.', 'Camera permission denied.\nAllow camera access or enter the code manually.');
      break;
    case 'none':
      showCameraProblem('No camera found on this device. Enter the bike code below.', 'No camera found.\nEnter the bike code manually.');
      break;
    case 'busy':
      showCameraProblem('The camera is being used by another app or tab. Close it, then tap "Try camera again".', 'Camera is in use by another app.');
      break;
    default:
      showCameraProblem('Could not start the camera. Try again or enter the bike code below.', 'Could not start the camera.');
  }
}

async function startCameraScan() {
  if (qrScanner) return; // naa nay naga-andar o naga-start
  setScannerRetry(false);

  if (typeof Html5Qrcode === 'undefined') {
    showCameraProblem('Scanner could not load (check your internet). Enter the bike code below.', 'Scanner failed to load.\nUse manual entry.');
    return;
  }
  // Ang camera mo-work ra sa https o localhost
  if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    showCameraProblem('Camera needs https or localhost. Enter the bike code below.', 'Camera is not available on this connection.\nUse https or localhost.');
    return;
  }

  const token = ++scannerToken;
  const instance = new Html5Qrcode('qr-reader', {
    verbose: false,
    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
  });
  qrScanner = instance;
  setScannerFrameOff(false);
  setScannerStatus('Starting camera…');

  const config = { fps: 10, aspectRatio: 1.0 };
  try {
    try {
      await instance.start({ facingMode: 'environment' }, config, onQrDecoded, () => {});
    } catch (err) {
      // Walay rear camera (pananglitan laptop)? Try ang front camera.
      if (classifyCameraError(err) !== 'constraint') throw err;
      await instance.start({ facingMode: 'user' }, config, onQrDecoded, () => {});
    }
    if (token !== scannerToken) { // nahabilin na sa screen, i-release dayon
      releaseScanner(instance);
      return;
    }
    setScannerStatus('Position the bike QR code inside the frame to continue.');
  } catch (err) {
    if (qrScanner === instance) qrScanner = null;
    try { instance.clear(); } catch (e) {}
    if (token === scannerToken) handleCameraError(err);
  }
}

function releaseScanner(instance) {
  return Promise.resolve()
    .then(() => instance.stop())
    .catch(() => {}) // wala pa naka-start / na-stop na
    .then(() => { try { instance.clear(); } catch (e) {} });
}

function stopCameraScan() {
  scannerToken++;
  const instance = qrScanner;
  qrScanner = null;
  if (instance) releaseScanner(instance);
}

// Gi-tawag sa camera sa matag frame nga naay na-basa nga QR
function onQrDecoded(decodedText) {
  const raw = String(decodedText ?? '').trim();
  const now = Date.now();
  if (scannerBusy || sessionSheetOpen) return;
  // Debounce: ang parehas nga QR nga naa pa sa frame dili i-process balik
  if (raw === lastScanRaw && now - lastScanAt < SCAN_DEBOUNCE_MS) return;
  lastScanRaw = raw;
  lastScanAt = now;
  processBikeCode(raw);
}

function submitManualCode() {
  const input = document.getElementById('manual-code-input');
  if (!input) return;
  if (!input.value.trim()) {
    showToast('Enter a bike code first.');
    return;
  }
  processBikeCode(input.value);
}

function processBikeCode(raw) {
  if (scannerBusy || sessionSheetOpen) return;
  const code = parseBikeCode(raw);
  if (!code) {
    showToast('Invalid QR code.\nPlease scan a RentaBike bike QR.');
    return;
  }

  // Gikan sa "Scan to Rent/Return" sa bike modal? Ang parehas nga bike ra ang dapat i-scan.
  if (pendingBikeCode && String(pendingBikeCode).toLowerCase() !== code.toLowerCase()) {
    showToast(`Wrong bike.\nPlease scan ${pendingBikeCode}.`);
    return;
  }

  scannerBusy = true;

  sendJson('GET', `/staff/scan/${encodeURIComponent(code)}`)
    .then(({ ok, data }) => {
      if (!ok) {
        // 404 "Bike not found" / 422 "This bike cannot be rented"
        showToast(`${data.message || 'Could not check this bike.'}\n(${code})`);
        return;
      }

      const input = document.getElementById('manual-code-input');
      if (input) input.value = '';

      if (data.next_action === 'return') {
        openSessionSheet(data); // Rented: detalye sa session + End / Extend
      } else {
        openRentalForm(data);   // Available: rental form (package, presyo, return time)
      }
    })
    .catch(handleRequestError)
    .finally(() => {
      scannerBusy = false;
    });
}

// Shared fetch helper: JSON + CSRF (meta csrf-token), ug check kung na-expire ang staff session
function sendJson(method, url, payload) {
  const options = {
    method,
    credentials: 'same-origin',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  };
  if (payload !== undefined) {
    options.headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(payload);
  }

  return fetch(url, options).then(response => {
    // Na-expire ang staff session? (staff.auth mo-redirect sa login page)
    if (response.redirected || response.status === 401 || response.status === 419) {
      const err = new Error('session');
      err.sessionExpired = true;
      throw err;
    }
    return response.json().then(data => ({ ok: response.ok, data }));
  });
}

function handleRequestError(error) {
  if (error && error.sessionExpired) {
    showToast('Session expired.\nPlease log in again.');
    setTimeout(() => { window.location.href = '/staff/login'; }, 1500);
    return;
  }
  showToast('Something went wrong.\nCheck your connection and try again.');
}

// I-stop ang camera kung gi-tago ang tab, ug i-balik kung mobalik
document.addEventListener('visibilitychange', () => {
  if (!scannerScreenOpen) return;
  if (document.hidden) {
    resumeCamera = !!qrScanner;
    stopCameraScan();
  } else if (resumeCamera) {
    resumeCamera = false;
    startCameraScan();
  }
});

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
let rentalBike = null;          // { bike, pricing } gikan sa scan
let selectedMinutes = null;     // gipili nga duration
let rentalSubmitting = false;
let timerInterval = null;

function formatPeso(n) {
  const v = Number(n) || 0;
  return '₱' + (Number.isInteger(v) ? v : v.toFixed(2));
}

function formatClock(h24, m) {
  return `${h24 % 12 || 12}:${pad(m)} ${h24 < 12 ? 'AM' : 'PM'}`;
}

function goHomeFresh() {
  // Reload para ma-update ang stats/bike status sa dashboard
  window.location.href = "{{ route('staff.home') }}";
}

// Available nga bike ang na-scan: ablihi ang rental form
function openRentalForm(data) {
  rentalBike = { bike: data.bike, pricing: data.pricing };
  selectedMinutes = null;

  document.getElementById('rf-bike-name').textContent = data.bike.name || data.bike.qr_code;
  document.getElementById('rf-bike-meta').textContent = `${data.bike.qr_code} · ${data.bike.bike_type} · ${data.pricing.rate_label}`;
  document.getElementById('rf-name').value = '';
  document.getElementById('rf-contact').value = '';
  document.getElementById('rf-borrow-time').value = data.server_time || '';

  const wrap = document.getElementById('rf-packages');
  wrap.innerHTML = '';
  data.pricing.options.forEach(opt => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'rf-pkg';
    btn.dataset.minutes = opt.minutes;
    const time = document.createElement('span');
    time.className = 'rf-pkg-time';
    time.textContent = opt.label;
    const price = document.createElement('span');
    price.className = 'rf-pkg-price';
    price.textContent = formatPeso(opt.price);
    btn.append(time, price);
    btn.addEventListener('click', () => selectPackage(opt.minutes));
    wrap.appendChild(btn);
  });

  // Pilia dayon ang una nga option para naay total ug return time
  if (data.pricing.options.length) selectPackage(data.pricing.options[0].minutes);
  else updateRentalSummary();

  goTo('rental-form');
  showToast(`${data.bike.qr_code} is available.\nFill in the rental form.`);
}

function selectPackage(minutes) {
  selectedMinutes = minutes;
  document.querySelectorAll('#rf-packages .rf-pkg').forEach(btn => {
    btn.classList.toggle('active', Number(btn.dataset.minutes) === minutes);
  });
  updateRentalSummary();
}

// Total ug expected return time: ang system ra ang mo-compute (borrow time + duration)
function updateRentalSummary() {
  if (!rentalBike) return;
  const totalEl = document.getElementById('rf-total');
  const returnEl = document.getElementById('rf-expected-return');
  const opt = rentalBike.pricing.options.find(o => o.minutes === selectedMinutes);

  totalEl.textContent = opt ? formatPeso(opt.price) : '₱0';

  const time = document.getElementById('rf-borrow-time').value;
  if (!opt || !time) {
    returnEl.textContent = '—';
    return;
  }
  const [h, m] = time.split(':').map(Number);
  const end = h * 60 + m + opt.minutes;
  const label = formatClock(Math.floor(end / 60) % 24, end % 60);
  returnEl.textContent = end >= 1440 ? `${label} (next day)` : label;
}

function submitRental() {
  if (rentalSubmitting) return;
  if (!rentalBike) {
    showToast('Scan a bike first.');
    return;
  }

  const name = document.getElementById('rf-name').value.trim();
  const contact = document.getElementById('rf-contact').value.trim();
  const borrowTime = document.getElementById('rf-borrow-time').value;

  if (!name) { showToast('Please enter the borrower name.'); return; }
  if (!contact) { showToast('Please enter the contact number.'); return; }
  if (!borrowTime) { showToast('Please enter the borrow time.'); return; }
  if (!selectedMinutes) { showToast('Please select a rental duration.'); return; }

  rentalSubmitting = true;
  const confirmBtn = document.getElementById('rf-confirm');
  confirmBtn.disabled = true;

  sendJson('POST', '/staff/rentals', {
    code: rentalBike.bike.qr_code,
    borrower_name: name,
    contact_no: contact,
    minutes: selectedMinutes,
    borrow_time: borrowTime,
  })
    .then(({ ok, data }) => {
      if (!ok) {
        showToast(data.message || 'Could not save the rental.');
        return;
      }

      const r = data.rental;
      document.getElementById('success-sub').textContent = `${data.bike.qr_code} has been successfully rented.`;
      document.getElementById('receipt-borrower').textContent = r.borrower_name || name;
      document.getElementById('receipt-bike').textContent = `${data.bike.name} (${data.bike.qr_code})`;
      document.getElementById('receipt-duration').textContent = `${r.duration_label} · until ${r.expected_end_label}`;
      document.getElementById('receipt-fee').textContent = formatPeso(r.base_fee);

      rentalBike = null;
      selectedMinutes = null;
      goTo('success');
      startTimer(r.seconds_remaining);
    })
    .catch(handleRequestError)
    .finally(() => {
      rentalSubmitting = false;
      confirmBtn.disabled = false;
    });
}

function startTimer(totalSeconds) {
  if (timerInterval) clearInterval(timerInterval);
  const el = document.getElementById('timer');
  const deadline = Date.now() + Math.max(0, totalSeconds) * 1000;
  const render = () => {
    const remaining = Math.max(0, Math.round((deadline - Date.now()) / 1000));
    el.textContent = pad(Math.floor(remaining / 3600)) + ':' + pad(Math.floor((remaining % 3600) / 60)) + ':' + pad(remaining % 60);
    if (remaining === 0) { clearInterval(timerInterval); timerInterval = null; }
  };
  render();
  timerInterval = setInterval(render, 1000);
}

// ── SESSION SHEET: rented nga bike ang na-scan (details, End Session, Extend) ──
let sessionSheetOpen = false;
let sessionData = null;         // { bike, rental }
let sessionDeadline = 0;        // Date.now() + segundo nga nahabilin
let sessionTick = null;
let sessionEndArmed = false;    // 2-step: tap End Session 2x
let sessionEnded = false;
let extendMinutes = null;
let sessionBusy = false;

function formatCountdown(sec) {
  const s = Math.abs(sec);
  return pad(Math.floor(s / 3600)) + ':' + pad(Math.floor((s % 3600) / 60)) + ':' + pad(s % 60);
}

function ssRow(label, value, extraClass = '', id = '') {
  const row = document.createElement('div');
  row.className = 'modal-detail-row';
  const l = document.createElement('span');
  l.className = 'label';
  l.textContent = label;
  const v = document.createElement('span');
  v.className = 'value' + (extraClass ? ' ' + extraClass : '');
  v.textContent = value;
  if (id) v.id = id;
  row.append(l, v);
  return row;
}

function openSessionSheet(data) {
  sessionData = { bike: data.bike, rental: data.rental };
  sessionEndArmed = false;
  sessionEnded = false;
  extendMinutes = null;
  sessionBusy = false;
  sessionSheetOpen = true;
  if (data.rental) sessionDeadline = Date.now() + data.rental.seconds_remaining * 1000;

  renderSession();
  document.getElementById('session-sheet').classList.add('show');
  startSessionTick();
}

function renderSession() {
  const { bike, rental } = sessionData;
  const body = document.getElementById('ss-body');
  const actions = document.getElementById('ss-actions');
  const extendBtn = document.getElementById('ss-extend-btn');
  const endBtn = document.getElementById('ss-end-btn');

  document.getElementById('ss-title').textContent = bike.name || bike.qr_code;
  document.getElementById('ss-sub').textContent = `${bike.qr_code} · ${bike.bike_type}`;
  body.innerHTML = '';
  setExtendPanel(false);
  endBtn.className = 'ss-btn ss-btn-danger';
  endBtn.disabled = false;
  sessionEndArmed = false;

  if (!rental) {
    // Walay rental record (daang data nga gi-mark ra nga rented): existing return behavior
    const note = document.createElement('div');
    note.className = 'ss-note';
    note.textContent = 'No active rental record was found for this bike. You can still mark it as returned.';
    body.appendChild(note);
    extendBtn.style.display = 'none';
    actions.classList.add('single');
    endBtn.textContent = 'Mark as Returned';
    return;
  }

  extendBtn.style.display = '';
  actions.classList.remove('single');
  endBtn.textContent = 'End Session';

  body.append(
    ssRow('Borrower', rental.borrower_name || '—'),
    ssRow('Contact', rental.contact_no || '—'),
    ssRow('Borrowed at', rental.start_label),
    ssRow('Interval', rental.duration_label),
    ssRow('Expected return', rental.expected_end_label),
    ssRow('Time remaining', '', '', 'ss-remaining'),
    ssRow('Paid so far', formatPeso(rental.amount_paid)),
  );
  if (rental.estimated_overtime_fee > 0) {
    body.appendChild(ssRow('Overtime fee (est.)', formatPeso(rental.estimated_overtime_fee), 'ss-over'));
  }
  updateSessionClock();
  renderExtendChips();
}

function updateSessionClock() {
  const el = document.getElementById('ss-remaining');
  if (!el) return;
  const remaining = Math.round((sessionDeadline - Date.now()) / 1000);
  el.textContent = remaining < 0 ? `Overtime ${formatCountdown(remaining)}` : formatCountdown(remaining);
  el.classList.toggle('ss-over', remaining < 0);
}

function startSessionTick() {
  stopSessionTick();
  sessionTick = setInterval(updateSessionClock, 1000);
}

function stopSessionTick() {
  if (sessionTick) { clearInterval(sessionTick); sessionTick = null; }
}

function setExtendPanel(show) {
  document.getElementById('ss-extend-panel').classList.toggle('show', show);
  if (!show) {
    extendMinutes = null;
    document.getElementById('ss-extend-confirm').classList.remove('show');
    document.querySelectorAll('#ss-chips .ss-chip').forEach(c => c.classList.remove('active'));
  }
}

function toggleExtendPanel() {
  if (sessionBusy || sessionEnded || !sessionData || !sessionData.rental) return;
  const panel = document.getElementById('ss-extend-panel');
  resetEndButton();
  setExtendPanel(!panel.classList.contains('show'));
}

function renderExtendChips() {
  const chips = document.getElementById('ss-chips');
  chips.innerHTML = '';
  sessionData.rental.extend_options.forEach(opt => {
    const chip = document.createElement('button');
    chip.type = 'button';
    chip.className = 'ss-chip';
    chip.dataset.minutes = opt.minutes;
    const b = document.createElement('b');
    b.textContent = `+${opt.label}`;
    const span = document.createElement('span');
    span.textContent = formatPeso(opt.price);
    chip.append(b, span);
    chip.addEventListener('click', () => selectExtend(opt));
    chips.appendChild(chip);
  });
}

function selectExtend(opt) {
  extendMinutes = opt.minutes;
  document.querySelectorAll('#ss-chips .ss-chip').forEach(c => {
    c.classList.toggle('active', Number(c.dataset.minutes) === opt.minutes);
  });
  const confirmBtn = document.getElementById('ss-extend-confirm');
  confirmBtn.textContent = `Confirm +${opt.label} (${formatPeso(opt.price)})`;
  confirmBtn.classList.add('show');
}

function confirmExtend() {
  if (sessionBusy || !extendMinutes || !sessionData || !sessionData.rental) return;
  sessionBusy = true;

  sendJson('POST', `/staff/rentals/${sessionData.rental.rental_id}/extend`, { minutes: extendMinutes })
    .then(({ ok, data }) => {
      if (!ok) {
        showToast(data.message || 'Could not extend the rental.');
        return;
      }
      sessionData.rental = data.rental;
      sessionDeadline = Date.now() + data.rental.seconds_remaining * 1000;
      renderSession();
      showToast(data.message);
    })
    .catch(handleRequestError)
    .finally(() => {
      sessionBusy = false;
    });
}

function resetEndButton() {
  sessionEndArmed = false;
  const endBtn = document.getElementById('ss-end-btn');
  if (endBtn && sessionData) endBtn.textContent = sessionData.rental ? 'End Session' : 'Mark as Returned';
}

function endSession() {
  if (sessionBusy || !sessionData) return;
  if (sessionEnded) { goHomeFresh(); return; } // "Done" button human ma-end

  const rental = sessionData.rental;
  if (!rental) {
    // Existing return behavior (PATCH toggle-status)
    const code = sessionData.bike.qr_code;
    hideSessionSheet();
    toggleBikeStatus(code);
    return;
  }

  // 2-step para dili ma-end sa sayop nga tap
  if (!sessionEndArmed) {
    sessionEndArmed = true;
    setExtendPanel(false);
    document.getElementById('ss-end-btn').textContent = 'Tap again to confirm';
    showToast(rental.estimated_overtime_fee > 0
      ? `Overtime fee ${formatPeso(rental.estimated_overtime_fee)} will be collected.\nTap End Session again to confirm.`
      : 'Tap End Session again to confirm.');
    setTimeout(() => { if (sessionEndArmed && !sessionEnded) resetEndButton(); }, 5000);
    return;
  }

  sessionBusy = true;
  sendJson('POST', `/staff/rentals/${rental.rental_id}/end`)
    .then(({ ok, data }) => {
      if (!ok) {
        showToast(data.message || 'Could not end the session.');
        resetEndButton();
        return;
      }
      showSessionReceipt(data.receipt);
    })
    .catch(handleRequestError)
    .finally(() => {
      sessionBusy = false;
    });
}

function showSessionReceipt(receipt) {
  sessionEnded = true;
  stopSessionTick();
  setExtendPanel(false);

  document.getElementById('ss-title').textContent = 'Session ended';
  const body = document.getElementById('ss-body');
  body.innerHTML = '';
  body.append(
    ssRow('Borrower', receipt.borrower_name || '—'),
    ssRow('Time used', receipt.time_used),
    ssRow('Rental fee', formatPeso(receipt.base_fee)),
    ssRow('Overtime fee', formatPeso(receipt.overtime_fee)),
    ssRow('Total fee', formatPeso(receipt.total_fee)),
    ssRow('Amount paid', formatPeso(receipt.amount_paid)),
    receipt.balance_due > 0
      ? ssRow('Balance due', formatPeso(receipt.balance_due), 'ss-over')
      : ssRow('Payment', 'Settled'),
  );

  document.getElementById('ss-extend-btn').style.display = 'none';
  document.getElementById('ss-actions').classList.add('single');
  const endBtn = document.getElementById('ss-end-btn');
  endBtn.className = 'ss-btn ss-btn-primary';
  endBtn.textContent = 'Done';
  showToast(receipt.balance_due > 0 ? `Session ended.\nCollect ${formatPeso(receipt.balance_due)}.` : 'Session ended.');
}

function closeSessionSheet() {
  if (sessionEnded) { goHomeFresh(); return; }
  hideSessionSheet();
}

function hideSessionSheet() {
  stopSessionTick();
  sessionSheetOpen = false;
  sessionData = null;
  lastScanAt = 0; // pwede na ma-scan balik ang parehas nga QR
  const sheet = document.getElementById('session-sheet');
  if (sheet) sheet.classList.remove('show');
}
function pad(n) { return String(n).padStart(2, '0'); }

// ── MODALS ───────────────────────────────────────────────────────────────────
function openBikeAction(action, data = {}) {
  const content = document.getElementById('modalContent');
  const title = action === 'edit' ? `Edit ${data.id ?? 'Bike'}` : 'Delete Bike';
  const body = action === 'delete'
    ? `<p class="modal-confirmation-message">Are you sure you want to remove ${data.id ?? 'this bike'} from inventory?</p>
       <form method="POST" action="{{ url('/staff/inventory') }}/${encodeURIComponent(data.id ?? '')}" data-live-form="delete">
         @csrf
         @method('DELETE')
         <div class="modal-actions"><button type="submit" class="primary-btn">Delete Bike</button><button type="button" class="primary-btn outline" onclick="closeModal()">Cancel</button></div>
       </form>`
    : `<form method="POST" action="{{ url('/staff/inventory') }}/${encodeURIComponent(data.id ?? '')}" data-live-form="edit" data-bike-code-preview-url="{{ route('staff.inventory.next-code') }}">
         @csrf
         @method('PATCH')
         <div class="form-group"><label class="form-label" for="edit-qr-code">Bike ID</label><input id="edit-qr-code" data-bike-code class="form-input" value="${data.qrCode ?? ''}" readonly></div>
         <div class="form-group"><label class="form-label" for="edit-model">Model</label><input id="edit-model" name="model" class="form-input" value="${data.model ?? ''}" required></div>
         <div class="form-group"><label class="form-label" for="edit-make">Make</label><input id="edit-make" name="make" class="form-input" value="${data.make ?? ''}" required></div>
         <div class="form-group"><label class="form-label" for="edit-bike-type">Bike Type</label><select id="edit-bike-type" name="bike_type" class="form-select" onchange="updateStaffBikeCodePreview(this)" required>${['Mountain Bike', 'City Bike', "Lady's/Men's Bike", 'E-Scooter', 'Road Bike', 'Sidecar Bike', "Children's Bike"].map(type => `<option value="${type}" ${data.type === type ? 'selected' : ''}>${type}</option>`).join('')}</select></div>
         <div class="form-group"><label class="form-label" for="edit-condition">Condition</label><select id="edit-condition" name="condition" class="form-select" required><option value="Good" ${data.condition === 'Good' ? 'selected' : ''}>Good</option><option value="Needs Repair" ${data.condition === 'Needs Repair' ? 'selected' : ''}>Needs Repair</option><option value="Missing" ${data.condition === 'Missing' ? 'selected' : ''}>Missing</option></select></div>
         <div class="modal-actions"><button type="submit" class="primary-btn">Save Changes</button><button type="button" class="primary-btn outline" onclick="closeModal()">Cancel</button></div>
       </form>`;
  content.innerHTML = `<div class="modal-bike-title">${title}</div>${body}`;
  if (action === 'edit') {
    const form = content.querySelector('[data-live-form="edit"]');
    const typeSelect = form.querySelector('[name="bike_type"]');
    if (![...typeSelect.options].some(option => option.value === data.type)) {
      typeSelect.add(new Option(data.type, data.type));
    }
    form.dataset.originalBikeType = data.type ?? '';
    form.dataset.originalBikeCode = data.qrCode ?? '';
    form.dataset.excludeQrCode = data.qrCode ?? '';
  }
  document.getElementById('modalBg').classList.add('open');
}

function updateStaffBikeCodePreview(typeSelect) {
  const form = typeSelect.form;
  const codeInput = form.querySelector('[data-bike-code]');
  if (!codeInput) return;

  if (form.dataset.liveForm === 'edit' && typeSelect.value === form.dataset.originalBikeType) {
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

function confirmDeleteBike(id, qrCode) {
  openBikeAction('delete', { id: id || qrCode || '' });
}

function updateInventoryRowFromBike(bike, previousBikeCode = null) {
  const bikeCode = bike.qr_code || bike.id;
  const category = bike.bike_type || 'Unknown';
  const statusValue = bike.status || 'available';
  const conditionLabel = bike.condition === 'repair' ? 'Repair' : (bike.condition === 'missing' ? 'Missing' : 'Available');
  const statusLabel = statusValue === 'rented' ? 'Rented' : (statusValue === 'repair' ? 'Repair' : 'Available');
  const statusClass = statusValue === 'rented' ? 'badge-blue' : (statusValue === 'repair' ? 'badge-orange' : 'badge-green');

  function syncCategoryBlock(categoryName) {
    const block = document.querySelector(`.inv-category-block[data-category="${categoryName}"]`);
    if (!block) return null;

    const rows = block.querySelectorAll('.inv-bike-row');
    const count = rows.length;
    const countEl = block.querySelector('.cat-count');
    if (countEl) {
      countEl.textContent = `${count} ${count === 1 ? 'unit' : 'units'}`;
    }

    return block;
  }

  const existingRow = document.querySelector(
    `.inv-bike-row[data-qr="${String(bikeCode).toLowerCase()}"]`
  ) || (previousBikeCode
    ? document.querySelector(`.inv-bike-row[data-qr="${String(previousBikeCode).toLowerCase()}"]`)
    : null);
  const currentCategory = existingRow ? existingRow.dataset.category : null;

  if (existingRow) {
    const oldCategory = currentCategory;
    existingRow.dataset.qr = String(bikeCode).toLowerCase();
    existingRow.dataset.model = `${(bike.model || '').toLowerCase()} ${(bike.make || '').toLowerCase()}`.trim();
    existingRow.dataset.category = category;
    existingRow.dataset.status = statusValue;
    existingRow.innerHTML = `
      <div style="flex:1;min-width:0">
        <div class="inv-row-id">${bikeCode}</div>
        <div class="inv-row-name">${bike.model} · ${bike.make}</div>
      </div>
      <span class="badge ${statusClass}" style="flex-shrink:0;font-size:11px">${statusLabel}</span>
      <div style="display:flex;gap:4px;flex-shrink:0">
        <button type="button" onclick="openBikeAction('edit', { id: '${bikeCode}', qrCode: '${bikeCode}', model: '${(bike.model || '').replace(/'/g, "\\'")}', make: '${(bike.make || '').replace(/'/g, "\\'")}', type: '${(bike.bike_type || '').replace(/'/g, "\\'")}', condition: '${conditionLabel === 'Repair' ? 'Needs Repair' : (conditionLabel === 'Missing' ? 'Missing' : 'Good')}' })" class="action-btn" style="width:30px;height:30px" title="Edit ${bikeCode}">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>
        <button type="button" onclick="openBikeAction('delete', { id: '${bikeCode}' })" class="action-btn" style="width:30px;height:30px;color:#ef4444" title="Delete ${bikeCode}">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </button>
      </div>`;

    if (oldCategory && oldCategory !== category) {
      const oldBlock = document.querySelector(`.inv-category-block[data-category="${oldCategory}"]`);
      if (oldBlock) {
        const oldList = oldBlock.querySelector('.inv-list');
        if (oldList && existingRow.parentNode === oldList) {
          oldList.removeChild(existingRow);
        }
      }
    }

    let targetBlock = document.querySelector(`.inv-category-block[data-category="${category}"]`);
    if (!targetBlock) {
      const template = document.querySelector('.inv-category-block');
      if (!template) return;
      const clone = template.cloneNode(true);
      clone.dataset.category = category;
      clone.querySelector('.inv-cat-header h3').textContent = category;
      clone.querySelector('.inv-list').innerHTML = '';
      template.parentNode.insertBefore(clone, template.nextSibling);
      targetBlock = clone;
    }

    const targetList = targetBlock.querySelector('.inv-list');
    if (targetList && existingRow.parentNode !== targetList) {
      targetList.appendChild(existingRow);
    }

    syncCategoryBlock(category);
    if (currentCategory && currentCategory !== category) {
      syncCategoryBlock(currentCategory);
    }
    return;
  }

  let targetBlock = document.querySelector(`.inv-category-block[data-category="${category}"]`);
  if (!targetBlock) {
    const template = document.querySelector('.inv-category-block');
    if (!template) return;
    const clone = template.cloneNode(true);
    clone.dataset.category = category;
    clone.querySelector('.inv-cat-header h3').textContent = category;
    clone.querySelector('.cat-count').textContent = '1 unit';
    clone.querySelector('.inv-list').innerHTML = '';
    template.parentNode.insertBefore(clone, template.nextSibling);
    targetBlock = clone;
  }

  const list = targetBlock.querySelector('.inv-list');
  const el = document.createElement('div');
  el.className = 'inv-row inv-bike-row';
  el.dataset.qr = bikeCode.toLowerCase();
  el.dataset.model = `${(bike.model || '').toLowerCase()} ${(bike.make || '').toLowerCase()}`.trim();
  el.dataset.category = category;
  el.dataset.status = statusValue;
  el.style.gap = '8px';
  el.style.alignItems = 'center';
  el.innerHTML = `
    <div style="flex:1;min-width:0">
      <div class="inv-row-id">${bikeCode}</div>
      <div class="inv-row-name">${bike.model} · ${bike.make}</div>
    </div>
    <span class="badge ${statusClass}" style="flex-shrink:0;font-size:11px">${statusLabel}</span>
    <div style="display:flex;gap:4px;flex-shrink:0">
      <button type="button" onclick="openBikeAction('edit', { id: '${bikeCode}', qrCode: '${bikeCode}', model: '${(bike.model || '').replace(/'/g, "\\'")}', make: '${(bike.make || '').replace(/'/g, "\\'")}', type: '${(bike.bike_type || '').replace(/'/g, "\\'")}', condition: '${conditionLabel === 'Repair' ? 'Needs Repair' : (conditionLabel === 'Missing' ? 'Missing' : 'Good')}' })" class="action-btn" style="width:30px;height:30px" title="Edit ${bikeCode}">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      </button>
      <button type="button" onclick="openBikeAction('delete', { id: '${bikeCode}' })" class="action-btn" style="width:30px;height:30px;color:#ef4444" title="Delete ${bikeCode}">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
      </button>
    </div>`;
  list.appendChild(el);
  syncCategoryBlock(category);
}

function handleLiveInventorySubmit(event) {
  const form = event.target;
  if (!form || !form.matches('[data-live-form]')) return;
  if (event.submitter && event.submitter.type === 'button') {
    // let the natural form submit happen for non-ajax cases; handled below
  }

  event.preventDefault();
  const action = form.dataset.liveForm;
  const url = form.action;
  const methodOverride = form.querySelector('input[name="_method"]')?.value || form.method;
  const formData = new FormData(form);
  const body = new FormData(form);
  const headers = {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };

  fetch(url, {
    method: methodOverride.toUpperCase(),
    headers,
    body,
  })
    .then(async response => {
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        throw new Error(payload.message || 'Something went wrong.');
      }
      return payload;
    })
    .then((payload) => {
      if (action === 'create' && payload.bike) {
        updateInventoryRowFromBike(payload.bike);
        const qrInput = form.querySelector('#qr-code');
        if (qrInput && payload.next_qr_code) qrInput.defaultValue = payload.next_qr_code;
      }
      if (action === 'edit' && payload.bike) {
        const previousBikeCode = decodeURIComponent(form.action.split('/').pop());
        updateInventoryRowFromBike(payload.bike, previousBikeCode);
      }
      if (action === 'delete') {
        const deletedId = form.action.split('/').pop();
        document.querySelectorAll('.inv-bike-row').forEach(row => {
          if ((row.dataset.qr || '').toLowerCase() === decodeURIComponent(deletedId).toLowerCase()) {
            row.remove();
          }
        });
      }
      closeModal();
      goTo('inventory');
      showToast(payload.message || 'Updated successfully.');
      form.reset();
      if (action === 'create') {
        const bikeType = form.querySelector('[name="bike_type"]');
        if (bikeType) updateStaffBikeCodePreview(bikeType);
      }
    })
    .catch((error) => {
      showToast(error.message || 'Could not complete the action.');
    });
}

document.addEventListener('submit', handleLiveInventorySubmit);

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