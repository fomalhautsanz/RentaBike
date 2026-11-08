{{-- HOME SCREEN --}}
<?php
$totalBikes = (int) ($stats['total'] ?? 0);
$availablePercent = $totalBikes > 0 ? round(((int) ($stats['available'] ?? 0) / $totalBikes) * 100) : 0;
$rentedPercent = $totalBikes > 0 ? round(((int) ($stats['rented'] ?? 0) / $totalBikes) * 100) : 0;
$repairPercent = $totalBikes > 0 ? round(((int) ($stats['repair'] ?? 0) / $totalBikes) * 100) : 0;
?>
<section class="screen active" id="home">
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-logo">
        <img src="{{ asset('images/system_logo.png') }}" alt="RentaBike Logo">
      </div>
      <div>
        <div class="topbar-brand">RentaBike</div>
        <div class="topbar-sub">Energy Park · Staff View</div>
      </div>
    </div>
   <div class="topbar-actions">
    <div class="time-chip" id="liveTime">--:-- --</div>

    <form method="POST" action="{{ route('staff.logout') }}">
        @csrf
        <button type="submit" class="logout-btn" title="Logout">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span>Logout</span>
        </button>
    </form>
</div>
  </div>

  <div class="content">
    {{-- STAT CARDS ROW 1 --}}
    <div class="stat-row">
      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-icon" style="background:#f0fdf4">
            <svg fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/>
              <path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/>
              <path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/>
            </svg>
          </div>
          <span class="stat-change up">Ready</span>
        </div>
        <div class="stat-value" id="stat-available">{{ $stats['available'] ?? 0 }}</div>
        <div class="stat-label">Available</div>
        <div class="stat-track"><div class="stat-fill-green" data-fill-width="{{ $availablePercent }}"></div></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-icon" style="background:#eff6ff">
            <svg fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
              <polyline points="16 7 22 7 22 13"/>
            </svg>
          </div>
          <span class="stat-change neutral">Active</span>
        </div>
        <div class="stat-value" id="stat-rented">{{ $stats['rented'] ?? 0 }}</div>
        <div class="stat-label">Rented</div>
        <div class="stat-track"><div class="stat-fill-blue" data-fill-width="{{ $rentedPercent }}"></div></div>
      </div>
    </div>

    {{-- STAT CARDS ROW 2 --}}
    <div class="stat-row" style="margin-top:-8px">
      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-icon" style="background:#fff7ed">
            <svg fill="none" stroke="#ea580c" stroke-width="2" viewBox="0 0 24 24">
              <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
          </div>
          <span class="stat-change neutral">!</span>
        </div>
        <div class="stat-value" id="stat-repair">{{ $stats['repair'] ?? 0 }}</div>
        <div class="stat-label">Repair</div>
        <div class="stat-track"><div class="stat-fill-orange" data-fill-width="{{ $repairPercent }}"></div></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-icon" style="background:#f0fdf4">
            <svg fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <span class="stat-change up">Live</span>
        </div>
        <div class="stat-value" id="stat-total">{{ $stats['total'] ?? 0 }}</div>
        <div class="stat-label">Total Bikes</div>
        <div class="stat-track"><div class="stat-fill-green" data-fill-width="100"></div></div>
      </div>
    </div>

    {{-- BIKE LIST --}}
    <div class="section-title dashboard-inventory-section-title">
      <div class="dashboard-section-heading">
        <h3>Bike Inventory</h3>
      </div>
      <div class="dashboard-inventory-actions">
        <button type="button" onclick="exportTodayActivityLogs()"
          style="display:inline-flex;align-items:center;gap:5px;padding:8px 12px;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-md);font-size:12.5px;font-weight:600;color:var(--gray-700);text-decoration:none;box-shadow:var(--shadow-sm);cursor:pointer">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Today's Logs
        </button>
        {{-- Maintenance logs export: Handle Maintenance ra ang makakita --}}
        @if($canHandleMaintenance ?? false)
        <button type="button" onclick="exportMaintenanceLogs()"
          style="display:inline-flex;align-items:center;gap:5px;padding:8px 12px;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-md);font-size:12.5px;font-weight:600;color:var(--gray-700);text-decoration:none;box-shadow:var(--shadow-sm);cursor:pointer">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Maintenance Logs
        </button>
        @endif
        <a class="dashboard-view-all" onclick="goTo('inventory')">
          View all
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </a>
      </div>
    </div>
    <div class="dashboard-category-list dashboard-status-list">
      @forelse($dashboardBikes as $statusType => $bike)
        @php
          $statusInfo = [
            'available' => ['label' => 'Available', 'detail' => 'Available for rental', 'class' => 'available'],
            'rented' => ['label' => 'Rented', 'detail' => 'Currently borrowed', 'class' => 'rented'],
            'maintenance' => ['label' => 'Maintenance', 'detail' => 'Needs maintenance', 'class' => 'maintenance'],
          ][$statusType];
        @endphp
        <div class="dashboard-category-bike" data-status-type="{{ $statusType }}" data-bike-id="{{ $bike->qr_code }}" data-condition="{{ ucfirst($bike->condition ?? 'good') }}" data-issue="{{ ucfirst($bike->condition ?? 'good') }}" data-report-type="damage" onclick="openModal(this.dataset.statusType, { id: this.dataset.bikeId, condition: this.dataset.condition, issue: this.dataset.issue, reportType: this.dataset.reportType })">
            <div class="dashboard-bike-icon dashboard-status-icon {{ $statusInfo['class'] }}" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/></svg>
            </div>
            <div class="dashboard-bike-row-copy">
              <h4>{{ $bike->qr_code }}</h4>
              <p>{{ $statusInfo['detail'] }}</p>
              @if($statusType === 'rented' && $bike->overdue)
                <p style="color:var(--red-600);font-weight:600">Estimated overtime: ₱{{ number_format($bike->estimated_overtime_fee, 2) }}</p>
              @endif
            </div>
            @if($statusType === 'rented' && $bike->overdue)
              <span class="badge badge-red">Overdue</span>
            @else
              <span class="badge {{ $statusInfo['class'] === 'available' ? 'badge-green' : ($statusInfo['class'] === 'rented' ? 'badge-blue' : 'badge-orange') }}"><span class="badge-dot {{ $statusInfo['class'] === 'available' ? 'badge-dot-green' : ($statusInfo['class'] === 'rented' ? 'badge-dot-blue' : 'badge-dot-orange') }}"></span>{{ $statusInfo['label'] }}</span>
            @endif
            <div class="bike-card-arrow"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></div>
        </div>
      @empty
        <p class="dashboard-bike-empty">No available bikes found in the inventory.</p>
      @endforelse
    </div>
  </div>

</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
<script>
async function exportTodayActivityLogs() {
  try {
    const response = await fetch(@json(route('staff.activity-logs.today')), {
      headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) {
      throw new Error('Could not load today’s activity logs.');
    }

    const { date, logs } = await response.json();
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet('Activity Logs');

    worksheet.columns = [
      { width: 12 }, { width: 24 }, { width: 16 },
      { width: 24 }, { width: 60 }, { width: 24 },
    ];

    const thin = { style: 'thin' };
    const border = { top: thin, left: thin, bottom: thin, right: thin };
    const fill = (argb) => ({ type: 'pattern', pattern: 'solid', fgColor: { argb } });

    worksheet.mergeCells('A1:F1');
    const title = worksheet.getCell('A1');
    title.value = 'RENTABIKE — ACTIVITY LOGS';
    title.font = { bold: true, size: 16, color: { argb: 'FF1F3864' } };
    title.fill = fill('FFFFFF00');
    title.alignment = { horizontal: 'center', vertical: 'middle' };
    worksheet.getRow(1).height = 30;

    worksheet.mergeCells('A2:F2');
    const generated = worksheet.getCell('A2');
    generated.value = new Date().toLocaleString('en-US', {
      timeZone: 'Asia/Manila',
      month: 'long', day: 'numeric', year: 'numeric',
      hour: '2-digit', minute: '2-digit', hour12: true
    });
    generated.font = { italic: true, size: 11 };
    generated.alignment = { horizontal: 'center' };

    const headers = ['Log ID', 'Actor', 'User Type', 'Action', 'Details', 'Timestamp'];
    const headerRow = worksheet.getRow(4);
    headers.forEach((header, index) => {
      const cell = headerRow.getCell(index + 1);
      cell.value = header;
      cell.font = { bold: true };
      cell.fill = fill('FFD9EAF7');
      cell.border = border;
    });

    logs.forEach((log, index) => {
      const row = worksheet.getRow(index + 5);
      [log.log_id, log.actor, log.user_type, log.action, log.details, log.timestamp]
        .forEach((value, cellIndex) => {
          const cell = row.getCell(cellIndex + 1);
          cell.value = value;
          cell.border = border;
          cell.alignment = { vertical: 'top', wrapText: true };
        });
    });

    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `activity-logs-${date}.xlsx`;
    link.click();
    URL.revokeObjectURL(url);

    showToast('Today’s activity logs exported successfully.');
  } catch (error) {
    showToast(error.message || 'Could not export today’s activity logs.');
  }
}
</script>

@if($canHandleMaintenance ?? false)
<script>
// Maintenance logs export: JSON gikan sa backend, ExcelJS ang mohimo sa file
async function exportMaintenanceLogs() {
  try {
    const response = await fetch(@json(route('staff.maintenance-logs.export')), {
      headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) {
      throw new Error(response.status === 403
        ? 'You do not have access to maintenance logs.'
        : 'Could not load maintenance logs.');
    }

    const { date, generated_at, logs } = await response.json();
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet('Maintenance Logs');

    worksheet.columns = [
      { width: 12 }, { width: 14 }, { width: 12 }, { width: 26 }, { width: 22 },
      { width: 50 }, { width: 14 }, { width: 22 }, { width: 22 },
    ];

    const thin = { style: 'thin' };
    const border = { top: thin, left: thin, bottom: thin, right: thin };
    const fill = (argb) => ({ type: 'pattern', pattern: 'solid', fgColor: { argb } });

    worksheet.mergeCells('A1:I1');
    const title = worksheet.getCell('A1');
    title.value = 'RENTABIKE — MAINTENANCE LOGS';
    title.font = { bold: true, size: 16, color: { argb: 'FF1F3864' } };
    title.fill = fill('FFFFFF00');
    title.alignment = { horizontal: 'center', vertical: 'middle' };
    worksheet.getRow(1).height = 30;

    worksheet.mergeCells('A2:I2');
    const generated = worksheet.getCell('A2');
    generated.value = generated_at;
    generated.font = { italic: true, size: 11 };
    generated.alignment = { horizontal: 'center' };

    const headers = ['Report ID', 'Type', 'Bike Code', 'Bike', 'Reported By', 'Description', 'Status', 'Reported At', 'Resolved At'];
    const headerRow = worksheet.getRow(4);
    headers.forEach((header, index) => {
      const cell = headerRow.getCell(index + 1);
      cell.value = header;
      cell.font = { bold: true };
      cell.fill = fill('FFD9EAF7');
      cell.border = border;
    });

    // kolor sa status para dali makita (parehas sa badges)
    const statusFill = { 'Pending': 'FFFEF9C3', 'In Progress': 'FFDBEAFE', 'Resolved': 'FFDCFCE7' };

    logs.forEach((log, index) => {
      const row = worksheet.getRow(index + 5);
      [log.report_code, log.type, log.bike_code, log.bike_name, log.reporter,
       log.description, log.status, log.date, log.resolved_at || '—']
        .forEach((value, cellIndex) => {
          const cell = row.getCell(cellIndex + 1);
          cell.value = value;
          cell.border = border;
          cell.alignment = { vertical: 'top', wrapText: true };
        });
      if (statusFill[log.status]) row.getCell(7).fill = fill(statusFill[log.status]);
    });

    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `maintenance-logs-${date}.xlsx`;
    link.click();
    URL.revokeObjectURL(url);

    showToast(logs.length ? 'Maintenance logs exported successfully.' : 'Exported — no maintenance logs yet.');
  } catch (error) {
    showToast(error.message || 'Could not export maintenance logs.');
  }
}
</script>
@endif
