<section id="page-rentals" class="page">
  <div class="page-header">
    <div>
      <div class="page-title">Activity Logs</div>
      <div class="page-sub">Full log of all bike rentals and returns</div>
    </div>
    <div style="display:flex;gap:12px">
      <button class="btn btn-primary" onclick="exportLogsExcel()">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export Logs
      </button>
    </div>
  </div>

  {{-- newly added na shi --}}
  <div class="table-wrap">
    {{-- FILTER ROW --}}
    <div class="table-toolbar" id="log-filter-panel">
      <select class="filter-select" onchange="filterLogType(this.value)">
        <option value="">All Activities</option>
        <option value="Rental">Rentals</option>
        <option value="Maintenance">Maintenance</option>
        <option value="Staff">Staff Actions</option>
        <option value="Payment">Payments</option>
      </select>
      <select class="filter-select" id="log-time-select" onchange="filterLogTime(this.value)">
        <option value="24">Last 24 Hours</option>
        <option value="168">Last 7 Days</option>
        <option value="720">Last 30 Days</option>
        <option value="custom">Custom Range</option>
      </select>
      <select class="filter-select" onchange="filterLogUser(this.value)">
        <option value="">All Users</option>
        <option value="staff">Staff Only</option>
        <option value="customers">Customers Only</option>
      </select>
      {{-- custom date inputs --}}
      <div id="custom-range-inputs" style="display:none;align-items:center;gap:8px">
        <input type="date" class="filter-select" id="custom-range-from" onchange="applyLogFilters()">
        <span style="color:#9ca3af;font-size:13px">to</span>
        <input type="date" class="filter-select" id="custom-range-to" onchange="applyLogFilters()">
      </div>
    </div>

    <table>
      <thead>
        <tr><th>Rental ID</th><th>Borrower</th><th>Bike</th><th>Staff</th><th>Borrow Time</th><th>Return Time</th><th>Duration</th><th>Status</th></tr>
      </thead>
      <tbody id="rentals-tbody">

        {{-- hardcoded demo rows --}}
        @if(count($rentals ?? []) === 0)
          <tr data-type="Rental" data-user-type="customers" data-hours-ago="0.03" data-timestamp="2026-08-22">
            <td style="font-weight:600">R-1041</td>
            <td>John Smith</td>
            <td>
              <div>Mountain Pro X1</div>
              <div style="font-size:12px;color:#9ca3af">BK-001</div>
            </td>
            <td>Alice Cooper</td>
            <td style="font-size:13px;color:#6b7280">Aug 22, 2026 06:58 PM</td>
            <td style="font-size:13px;color:#6b7280">Aug 22, 2026 07:00 PM</td>
            <td style="font-size:13px">2 mins</td>
            <td><span class="badge badge-green">Completed</span></td>
          </tr>
          <tr data-type="Rental" data-user-type="customers" data-hours-ago="0.25" data-timestamp="2026-08-22">
            <td style="font-weight:600">R-1040</td>
            <td>Sarah Johnson</td>
            <td>
              <div>Tricycle Comfort T2</div>
              <div style="font-size:12px;color:#9ca3af">BK-002</div>
            </td>
            <td>David Lee</td>
            <td style="font-size:13px;color:#6b7280">Aug 22, 2026 06:45 PM</td>
            <td style="font-size:13px;color:#6b7280">—</td>
            <td style="font-size:13px">—</td>
            <td><span class="badge badge-blue">Active</span></td>
          </tr>
          <tr data-type="Rental" data-user-type="customers" data-hours-ago="26" data-timestamp="2026-08-21">
            <td style="font-weight:600">R-1039</td>
            <td>Mark Reyes</td>
            <td>
              <div>City Cruiser C1</div>
              <div style="font-size:12px;color:#9ca3af">BK-003</div>
            </td>
            <td>Alice Cooper</td>
            <td style="font-size:13px;color:#6b7280">Aug 21, 2026 05:00 PM</td>
            <td style="font-size:13px;color:#6b7280">—</td>
            <td style="font-size:13px">—</td>
            <td><span class="badge badge-red">Overdue</span></td>
          </tr>
        @endif

        @foreach($rentals ?? [] as $rental)
        <tr data-type="Rental" data-user-type="customers" data-hours-ago="{{ $rental->hours_ago ?? 0 }}" data-timestamp="{{ $rental->borrow_time->format('Y-m-d') }}">
          <td style="font-weight:600">{{ $rental->rental_code }}</td>
          <td>{{ $rental->borrower_name }}</td>
          <td>
            <div>{{ $rental->bike->name ?? 'N/A' }}</div>
            <div style="font-size:12px;color:#9ca3af">{{ $rental->bike->bike_code ?? '' }}</div>
          </td>
          <td>{{ $rental->staff->name ?? 'N/A' }}</td>
          <td style="font-size:13px;color:#6b7280">{{ $rental->borrow_time->format('M d, Y h:i A') }}</td>
          <td style="font-size:13px;color:#6b7280">{{ $rental->return_time ? $rental->return_time->format('M d, Y h:i A') : '—' }}</td>
          <td style="font-size:13px">{{ $rental->duration ?? '—' }}</td>
          <td>
            <span class="badge {{ $rental->status === 'Completed' ? 'badge-green' : ($rental->status === 'Active' ? 'badge-blue' : 'badge-red') }}">
              {{ $rental->status }}
            </span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="table-footer">
      <p id="logs-count">Showing {{ count($rentals ?? []) ?: 3 }} rentals</p>
    </div>
  </div>
</section>

{{-- ExcelJS library (remove this line if your layout already loads it) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>

{{-- gi add nako func sa filtering, og export excel --}}
{{-- mga functions, diri nlng ibutang if kanang sa ui ra ha or gamay ra --}}
<script>
// filtering 
let logTypeFilter = '', logTimeFilter = '24', logUserTypeFilter = '';

function filterLogType(v) { logTypeFilter = v; applyLogFilters(); }

function filterLogTime(v) {
  logTimeFilter = v;
  const customInputs = document.getElementById('custom-range-inputs');
  // trigger custom range 
  customInputs.style.display = (v === 'custom') ? 'flex' : 'none';
  applyLogFilters();
}

function filterLogUser(v) { logUserTypeFilter = v; applyLogFilters(); }

function applyLogFilters() {
  const rows = document.querySelectorAll('#rentals-tbody tr');
  let visibleCount = 0;

  const fromDate = document.getElementById('custom-range-from')?.value;
  const toDate = document.getElementById('custom-range-to')?.value;

  rows.forEach(row => {
    const typeOk = !logTypeFilter || row.dataset.type === logTypeFilter;
    const userOk = !logUserTypeFilter || row.dataset.userType === logUserTypeFilter;

    let timeOk = true;
    if (logTimeFilter === 'custom') {
      // custom range: i-compare ang row's date sa gipili nga from/to 
      if (fromDate && row.dataset.timestamp < fromDate) timeOk = false;
      if (toDate && row.dataset.timestamp > toDate) timeOk = false;
    } else {
      timeOk = parseFloat(row.dataset.hoursAgo) <= parseFloat(logTimeFilter);
    }

    const show = typeOk && userOk && timeOk;
    row.style.display = show ? '' : 'none';
    if (show) visibleCount++;
  });

  document.getElementById('logs-count').textContent = `Showing ${visibleCount} rentals`;
}

// initial filter application pag mo-load ang page (default: Last 24 Hours)
document.addEventListener('DOMContentLoaded', applyLogFilters);

// export logs to styled excel (.xlsx)
async function exportLogsExcel() {
  const rows = document.querySelectorAll('#rentals-tbody tr');

  const wb = new ExcelJS.Workbook();
  const ws = wb.addWorksheet('Activity Logs');

  ws.columns = [
    { width: 12 }, { width: 22 }, { width: 24 }, { width: 18 },
    { width: 24 }, { width: 24 }, { width: 14 }, { width: 14 },
  ];

  const thin = { style: 'thin' };
  const border = { top: thin, left: thin, bottom: thin, right: thin };
  const fill = (argb) => ({ type: 'pattern', pattern: 'solid', fgColor: { argb } });

  // Title banner
  ws.mergeCells('A1:H1');
  const title = ws.getCell('A1');
  title.value = 'RENTABIKE';
  title.font = { bold: true, size: 18, color: { argb: 'FF1F3864' } };
  title.fill = fill('FFFFFF00');
  title.alignment = { horizontal: 'center', vertical: 'middle' };
  ws.getRow(1).height = 32;

  // Report title
  ws.mergeCells('A2:H2');
  const sub = ws.getCell('A2');
  sub.value = 'ACTIVITY LOGS REPORT';
  sub.font = { bold: true, size: 12, color: { argb: 'FF1F3864' } };
  sub.fill = fill('FFFCE4D6');
  sub.alignment = { horizontal: 'center' };

  // Date generated
  ws.mergeCells('A3:H3');
  const dateCell = ws.getCell('A3');
  dateCell.value = new Date().toLocaleString('en-US', {
    timeZone: 'Asia/Manila',
    month: 'long', day: 'numeric', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: true
  });
  dateCell.font = { italic: true, size: 11 };
  dateCell.alignment = { horizontal: 'center' };

  // Header row (row 5)
  const headers = ['Rental ID', 'Borrower', 'Bike', 'Staff', 'Borrow Time', 'Return Time', 'Duration', 'Status'];
  const headerColors = ['FF00B0F0', 'FF00B0F0', 'FFD9D2C0', 'FFD9D2C0', 'FFF4B183', 'FFF4B183', 'FF92D050', 'FF92D050'];
  const headerRow = ws.getRow(5);
  headers.forEach((h, i) => {
    const c = headerRow.getCell(i + 1);
    c.value = h;
    c.font = { bold: true };
    c.fill = fill(headerColors[i]);
    c.border = border;
    c.alignment = { horizontal: 'center', vertical: 'middle' };
  });
  headerRow.height = 26;

  // Data rows (only the ones currently visible after filtering)
  let r = 6;
  rows.forEach(row => {
    if (row.style.display === 'none') return;

    const cells = row.querySelectorAll('td');
    const values = [
      cells[0].textContent.trim(),
      cells[1].textContent.trim(),
      cells[2].querySelector('div').textContent.trim(),
      cells[3].textContent.trim(),
      cells[4].textContent.trim(),
      cells[5].textContent.trim(),
      cells[6].textContent.trim(),
      cells[7].textContent.trim(),
    ];

    const excelRow = ws.getRow(r++);
    values.forEach((v, i) => {
      const c = excelRow.getCell(i + 1);
      c.value = v;
      c.border = border;
      c.alignment = { vertical: 'middle', horizontal: i === 0 ? 'center' : 'left' };
    });
    excelRow.getCell(1).font = { bold: true };

    // Color the status cell
    const statusCell = excelRow.getCell(8);
    const status = values[7];
    if (status === 'Completed') statusCell.fill = fill('FFC6EFCE');
    else if (status === 'Active') statusCell.fill = fill('FFBDD7EE');
    else if (status === 'Overdue') statusCell.fill = fill('FFFFC7CE');
    statusCell.alignment = { horizontal: 'center', vertical: 'middle' };
  });

  // Download
  const buffer = await wb.xlsx.writeBuffer();
  const blob = new Blob([buffer], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `activity-logs-${new Date().toISOString().slice(0, 10)}.xlsx`;
  link.click();
  URL.revokeObjectURL(url);

  showToast('Activity logs exported successfully.');
}
</script>