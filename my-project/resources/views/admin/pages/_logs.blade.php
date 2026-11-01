<section id="page-rentals" class="page">
  <div class="page-header">
    <div>
      <div class="page-title">Activity Logs</div>
      <div class="page-sub">Audit trail of activity across the system</div>
    </div>
    <div style="display:flex;gap:12px">
      <button class="btn btn-primary" onclick="exportLogsExcel()">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export Logs
      </button>
    </div>
  </div>

  <div class="table-wrap">
    <form class="table-toolbar" id="log-filter-panel" method="GET" action="{{ route('admin.dashboard') }}">
      <input type="hidden" name="tab" value="rentals">
      <select class="filter-select" name="log_action" aria-label="Filter by activity">
        <option value="">All Activities</option>
        @foreach($logActionFilters as $filter => $actions)
          <option value="{{ $filter }}" @selected(($logFilters['log_action'] ?? '') === $filter)>
            {{ ucfirst($filter) }}
          </option>
        @endforeach
      </select>
      <select class="filter-select" id="log-time-select" name="log_period" aria-label="Filter by date">
        <option value="all" @selected(($logFilters['log_period'] ?? 'all') === 'all')>All Time</option>
        <option value="24" @selected(($logFilters['log_period'] ?? '') === '24')>Last 24 Hours</option>
        <option value="168" @selected(($logFilters['log_period'] ?? '') === '168')>Last 7 Days</option>
        <option value="720" @selected(($logFilters['log_period'] ?? '') === '720')>Last 30 Days</option>
        <option value="custom" @selected(($logFilters['log_period'] ?? '') === 'custom')>Custom Range</option>
      </select>
      <select class="filter-select" name="log_user_type" aria-label="Filter by actor">
        <option value="">All Users</option>
        <option value="admin" @selected(($logFilters['log_user_type'] ?? '') === 'admin')>Admins</option>
        <option value="staff" @selected(($logFilters['log_user_type'] ?? '') === 'staff')>Staff</option>
        <option value="system" @selected(($logFilters['log_user_type'] ?? '') === 'system')>System</option>
      </select>
      <div id="custom-range-inputs" style="display:none;align-items:center;gap:8px">
        <input type="date" class="filter-select" name="log_from" aria-label="From date"
               value="{{ $logFilters['log_from'] ?? '' }}">
        <span style="color:#9ca3af;font-size:13px">to</span>
        <input type="date" class="filter-select" name="log_to" aria-label="To date"
               value="{{ $logFilters['log_to'] ?? '' }}">
      </div>
      <button class="btn btn-primary" type="submit">Apply Filters</button>
      <a class="btn" href="{{ route('admin.dashboard', ['tab' => 'rentals']) }}">Clear</a>
    </form>

    <table>
      <thead>
        <tr>
          <th>Log ID</th>
          <th>Actor</th>
          <th>User Type</th>
          <th>Action</th>
          <th>Details</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody id="activity-logs-tbody">
        @forelse($activityLogs as $log)
          <tr>
            <td style="font-weight:600">{{ $log->log_id }}</td>
            <td>{{ $log->display_actor_name }}</td>
            <td>{{ ucfirst($log->user_type) }}</td>
            <td>{{ ucwords(strtolower(str_replace('_', ' ', $log->action))) }}</td>
            <td>{{ $log->details ?: '—' }}</td>
            <td>{{ $log->timestamp->format('M d, Y h:i A') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align:center;color:#6b7280">No activity logs match these filters.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
    <div class="table-footer">
      <p id="logs-count">
        Showing {{ $activityLogs->firstItem() ?? 0 }}–{{ $activityLogs->lastItem() ?? 0 }}
        of {{ $activityLogs->total() }} activity logs
      </p>
      {{ $activityLogs->links() }}
    </div>
  </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const period = document.getElementById('log-time-select');
  const customRange = document.getElementById('custom-range-inputs');
  const fromDate = document.querySelector('[name="log_from"]');
  const toDate = document.querySelector('[name="log_to"]');
  const toggleCustomRange = () => {
    const isCustom = period.value === 'custom';
    customRange.style.display = isCustom ? 'flex' : 'none';
    fromDate.required = isCustom;
    toDate.required = isCustom;
    toDate.min = fromDate.value;
  };

  period.addEventListener('change', toggleCustomRange);
  fromDate.addEventListener('change', toggleCustomRange);
  toggleCustomRange();
});

async function exportLogsExcel() {
  const rows = document.querySelectorAll('#activity-logs-tbody tr');
  const wb = new ExcelJS.Workbook();
  const ws = wb.addWorksheet('Activity Logs');

  ws.columns = [
    { width: 12 }, { width: 24 }, { width: 16 },
    { width: 24 }, { width: 60 }, { width: 24 },
  ];

  const thin = { style: 'thin' };
  const border = { top: thin, left: thin, bottom: thin, right: thin };
  const fill = (argb) => ({ type: 'pattern', pattern: 'solid', fgColor: { argb } });

  ws.mergeCells('A1:F1');
  const title = ws.getCell('A1');
  title.value = 'RENTABIKE — ACTIVITY LOGS';
  title.font = { bold: true, size: 16, color: { argb: 'FF1F3864' } };
  title.fill = fill('FFFFFF00');
  title.alignment = { horizontal: 'center', vertical: 'middle' };
  ws.getRow(1).height = 30;

  ws.mergeCells('A2:F2');
  const generated = ws.getCell('A2');
  generated.value = new Date().toLocaleString('en-US', {
    timeZone: 'Asia/Manila',
    month: 'long', day: 'numeric', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: true
  });
  generated.font = { italic: true, size: 11 };
  generated.alignment = { horizontal: 'center' };

  const headers = ['Log ID', 'Actor', 'User Type', 'Action', 'Details', 'Timestamp'];
  const headerRow = ws.getRow(4);
  headers.forEach((header, index) => {
    const cell = headerRow.getCell(index + 1);
    cell.value = header;
    cell.font = { bold: true };
    cell.fill = fill('FFD9EAF7');
    cell.border = border;
  });

  let rowIndex = 5;
  rows.forEach(row => {
    if (!row.querySelector('td[colspan]')) {
      const excelRow = ws.getRow(rowIndex++);
      row.querySelectorAll('td').forEach((cell, index) => {
        const excelCell = excelRow.getCell(index + 1);
        excelCell.value = cell.textContent.trim();
        excelCell.border = border;
        excelCell.alignment = { vertical: 'top', wrapText: true };
      });
    }
  });

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