<section id="page-maintenance" class="page">
  <div class="page-header">
    <div>
      <div class="page-title">Maintenance &amp; Reports</div>
      <div class="page-sub">View and manage staff reports on bike issues</div>
    </div>
    <div class="maint-counters">
      <div class="maint-counter" id="counter-all" onclick="filterMaintenanceStatus('')">
        <div class="counter-dot" style="background:#9ca3af"></div><span id="count-all">0</span> All
      </div>
      <div class="maint-counter" id="counter-pending" onclick="filterMaintenanceStatus('Pending')">
        <div class="counter-dot" style="background:#eab308"></div><span id="count-pending">0</span> Pending
      </div>
      <div class="maint-counter" id="counter-inprogress" onclick="filterMaintenanceStatus('In Progress')">
        <div class="counter-dot" style="background:#3b82f6"></div><span id="count-inprogress">0</span> In Progress
      </div>
      <div class="maint-counter" id="counter-resolved" onclick="filterMaintenanceStatus('Resolved')">
        <div class="counter-dot" style="background:#22c55e"></div><span id="count-resolved">0</span> Resolved
      </div>
    </div>
  </div>
  <div class="table-wrap">
    {{-- Maintenance filters: GET form parehas sa Activity Logs, maint_* ang names --}}
    @php($maintenanceFilters = $maintenanceFilters ?? [])
    <form class="table-toolbar" id="maint-filter-panel" style="flex-wrap:wrap" method="GET" action="{{ route('admin.dashboard') }}">
      <input type="hidden" name="tab" value="maintenance">
      <div class="search-wrap" style="min-width:220px">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" class="search-input" name="maint_search" maxlength="100"
               placeholder="Search report ID, bike, staff, description..."
               value="{{ $maintenanceFilters['maint_search'] ?? '' }}">
      </div>
      <select class="filter-select" name="maint_type" aria-label="Filter by type">
        <option value="">All Types</option>
        @foreach(\App\Support\MaintenanceLogs::TYPES as $value => $label)
          <option value="{{ $value }}" @selected(($maintenanceFilters['maint_type'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
      </select>
      <select class="filter-select" name="maint_status" aria-label="Filter by status">
        <option value="">All Status</option>
        @foreach(\App\Support\MaintenanceLogs::STATUSES as $value => $label)
          <option value="{{ $value }}" @selected(($maintenanceFilters['maint_status'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
      </select>
      <select class="filter-select" id="maint-period-select" name="maint_period" aria-label="Filter by date">
        <option value="all" @selected(($maintenanceFilters['maint_period'] ?? 'all') === 'all')>All Time</option>
        <option value="24" @selected(($maintenanceFilters['maint_period'] ?? '') === '24')>Last 24 Hours</option>
        <option value="168" @selected(($maintenanceFilters['maint_period'] ?? '') === '168')>Last 7 Days</option>
        <option value="720" @selected(($maintenanceFilters['maint_period'] ?? '') === '720')>Last 30 Days</option>
        <option value="custom" @selected(($maintenanceFilters['maint_period'] ?? '') === 'custom')>Custom Range</option>
      </select>
      <div id="maint-custom-range" style="display:none;align-items:center;gap:8px">
        <input type="date" class="filter-select" name="maint_from" aria-label="From date"
               value="{{ $maintenanceFilters['maint_from'] ?? '' }}">
        <span style="color:#9ca3af;font-size:13px">to</span>
        <input type="date" class="filter-select" name="maint_to" aria-label="To date"
               value="{{ $maintenanceFilters['maint_to'] ?? '' }}">
      </div>
      <button class="btn btn-primary" type="submit">Apply Filters</button>
      <a class="btn" href="{{ route('admin.dashboard', ['tab' => 'maintenance']) }}">Clear</a>
    </form>

    <table>
      <thead>
        <tr><th>Report ID</th><th>Type</th><th>Bike</th><th>Reported By</th><th>Description</th><th>Date</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody id="maintenance-tbody">
        {{-- kani gi add sad nako --}}
        {{-- hardcoded shi to see unsay gama ani niya --}}
        @forelse($reports ?? [] as $report)
        <tr data-status="{{ $report->status }}" data-issue-id="{{ $report->issue_id }}" data-photo-url="{{ $report->photo_url ?? '' }}">
          <td style="font-weight:600">{{ $report->report_code }}</td>
          <td>
            <span class="badge {{ $report->type === 'Damage' ? 'badge-orange' : ($report->type === 'Missing Bike' ? 'badge-red' : 'badge-blue') }}">
              {{ $report->type }}
            </span>
          </td>
          <td>
            <div style="font-weight:500">{{ $report->bike_name }}</div>
            <div style="font-size:12px;color:#9ca3af">{{ $report->bike_code }}</div>
          </td>
          <td>{{ $report->reporter }}</td>
          <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $report->description }}">{{ $report->description }}</td>
          <td style="color:#6b7280;font-size:13px">{{ $report->date }}</td>
          <td>
            <span class="badge {{ $report->status === 'Pending' ? 'status-pending' : ($report->status === 'In Progress' ? 'status-inprogress' : 'status-resolved') }}">
              {{ $report->status }}
            </span>
          </td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openViewReport('{{ $report->report_code }}')">View</button>
          </td>
        </tr>
        @empty
          {{-- walay data-status para dili maapil sa counters --}}
          <tr>
            <td colspan="8" style="text-align:center;color:#6b7280">No maintenance reports match these filters.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
    <div class="table-footer">
      <p id="maintenance-footer-count">Showing 0 reports</p>
    </div>
  </div>
</section>
{{-- yohooo dito ang tingin nag add kog filtering --}}
<style>
  .maint-counter {
    cursor: pointer;
  }
  .maint-counter.active {
    font-weight: 600;
  }
  #counter-all.active {
    background: #f3f4f6;
    border-color: #9ca3af;
    color: #374151;
  }
  #counter-pending.active {
    background: #fef9c3;
    border-color: #eab308;
    color: #854d0e;
  }
  #counter-inprogress.active {
    background: #dbeafe;
    border-color: #3b82f6;
    color: #1d4ed8;
  }
  #counter-resolved.active {
    background: #dcfce7;
    border-color: #22c55e;
    color: #15803d;
  }
</style>

<script>
  // recomputes counter numbers from whatever rows currently exist in the table
  function updateMaintenanceCounters() {
    // tr[data-status] ra, para dili maapil ang "No reports" row
    const rows = document.querySelectorAll('#maintenance-tbody tr[data-status]');
    let pending = 0, inProgress = 0, resolved = 0;

    rows.forEach(row => {
      const status = row.dataset.status;
      if (status === 'Pending') pending++;
      else if (status === 'In Progress') inProgress++;
      else if (status === 'Resolved') resolved++;
    });

    document.getElementById('count-all').textContent = rows.length;
    document.getElementById('count-pending').textContent = pending;
    document.getElementById('count-inprogress').textContent = inProgress;
    document.getElementById('count-resolved').textContent = resolved;
  }

  // clicking a counter filters the table to that status; clicking "All" clears the filter
  // ok bthc
  let maintenanceStatusFilter = '';
  function filterMaintenanceStatus(status) {
    maintenanceStatusFilter = status;

    document.querySelectorAll('#maintenance-tbody tr[data-status]').forEach(row => {
      row.style.display = (!status || row.dataset.status === status) ? '' : 'none';
    });

    // highlight the active counter
    document.querySelectorAll('.maint-counter').forEach(c => c.classList.remove('active'));
    const activeId = status === 'Pending' ? 'counter-pending'
                    : status === 'In Progress' ? 'counter-inprogress'
                    : status === 'Resolved' ? 'counter-resolved'
                    : 'counter-all';
    document.getElementById(activeId).classList.add('active');

    updateMaintenanceFooterCount();
  }

  function updateMaintenanceFooterCount() {
    const visibleRows = document.querySelectorAll('#maintenance-tbody tr[data-status]:not([style*="display: none"])');
    document.getElementById('maintenance-footer-count').textContent = `Showing ${visibleRows.length} reports`;
  }

  document.addEventListener('DOMContentLoaded', function () {
    updateMaintenanceCounters();
    filterMaintenanceStatus(''); // sa all mag start biskan i refresh

    // ipakita ang from/to dates kung "Custom Range" ang gi-pili
    const period = document.getElementById('maint-period-select');
    const customRange = document.getElementById('maint-custom-range');
    const fromDate = document.querySelector('[name="maint_from"]');
    const toDate = document.querySelector('[name="maint_to"]');
    const toggleMaintCustomRange = () => {
      const isCustom = period.value === 'custom';
      customRange.style.display = isCustom ? 'flex' : 'none';
      fromDate.required = isCustom;
      toDate.required = isCustom;
      toDate.min = fromDate.value;
    };

    period.addEventListener('change', toggleMaintCustomRange);
    fromDate.addEventListener('change', toggleMaintCustomRange);
    toggleMaintCustomRange();
  });
</script>