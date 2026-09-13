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
    <table>
      <thead>
        <tr><th>Report ID</th><th>Type</th><th>Bike</th><th>Reported By</th><th>Description</th><th>Date</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody id="maintenance-tbody">
        {{-- kani gi add sad nako --}}
        {{-- hardcoded shi to see unsay gama ani niya --}}
        @if(count($reports ?? []) === 0)
          <tr data-status="Pending">
            <td style="font-weight:600">RPT-1008</td>
            <td><span class="badge badge-orange">Damage</span></td>
            <td>
              <div style="font-weight:500">Mountain Trail M2</div>
              <div style="font-size:12px;color:#9ca3af">BK-005</div>
            </td>
            <td>David Lee</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="Flat rear tire, needs replacement">Flat rear tire, needs replacement</td>
            <td style="color:#6b7280;font-size:13px">Aug 22, 2026</td>
            <td><span class="badge status-pending">Pending</span></td>
            <td><button class="btn btn-outline btn-sm" onclick="openViewReport('RPT-1008')">View</button></td>
          </tr>
          <tr data-status="Pending">
            <td style="font-weight:600">RPT-1007</td>
            <td><span class="badge badge-red">Missing Bike</span></td>
            <td>
              <div style="font-weight:500">City Explorer C3</div>
              <div style="font-size:12px;color:#9ca3af">BK-004</div>
            </td>
            <td>Carol Martinez</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="Bike not returned after rental period">Bike not returned after rental period</td>
            <td style="color:#6b7280;font-size:13px">Aug 21, 2026</td>
            <td><span class="badge status-pending">Pending</span></td>
            <td><button class="btn btn-outline btn-sm" onclick="openViewReport('RPT-1007')">View</button></td>
          </tr>
          <tr data-status="In Progress">
            <td style="font-weight:600">RPT-1006</td>
            <td><span class="badge badge-blue">Mechanical</span></td>
            <td>
              <div style="font-weight:500">City Cruiser C1</div>
              <div style="font-size:12px;color:#9ca3af">BK-003</div>
            </td>
            <td>Alice Cooper</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="Brake lever loose, being repaired">Brake lever loose, being repaired</td>
            <td style="color:#6b7280;font-size:13px">Aug 20, 2026</td>
            <td><span class="badge status-inprogress">In Progress</span></td>
            <td><button class="btn btn-outline btn-sm" onclick="openViewReport('RPT-1006')">View</button></td>
          </tr>
          <tr data-status="Resolved">
            <td style="font-weight:600">RPT-1005</td>
            <td><span class="badge badge-orange">Damage</span></td>
            <td>
              <div style="font-weight:500">Tricycle Comfort T2</div>
              <div style="font-size:12px;color:#9ca3af">BK-002</div>
            </td>
            <td>Bob Wilson</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="Seat torn, repaired same day">Seat torn, repaired same day</td>
            <td style="color:#6b7280;font-size:13px">Aug 18, 2026</td>
            <td><span class="badge status-resolved">Resolved</span></td>
            <td><button class="btn btn-outline btn-sm" onclick="openViewReport('RPT-1005')">View</button></td>
          </tr>
        @endif

        @foreach($reports ?? [] as $report)
        <tr data-status="{{ $report->status }}">
          <td style="font-weight:600">{{ $report->report_code }}</td>
          <td>
            <span class="badge {{ $report->type === 'Damage' ? 'badge-orange' : ($report->type === 'Missing Bike' ? 'badge-red' : 'badge-blue') }}">
              {{ $report->type }}
            </span>
          </td>
          <td>
            <div style="font-weight:500">{{ $report->bike->name ?? 'N/A' }}</div>
            <div style="font-size:12px;color:#9ca3af">{{ $report->bike->bike_code ?? '' }}</div>
          </td>
          <td>{{ $report->reporter->name ?? 'Unknown' }}</td>
          <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $report->description }}">{{ $report->description }}</td>
          <td style="color:#6b7280;font-size:13px">{{ $report->created_at->format('M d, Y') }}</td>
          <td>
            <span class="badge {{ $report->status === 'Pending' ? 'status-pending' : ($report->status === 'In Progress' ? 'status-inprogress' : 'status-resolved') }}">
              {{ $report->status }}
            </span>
          </td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openViewReport('{{ $report->report_code }}')">View</button>
          </td>
        </tr>
        @endforeach
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
    const rows = document.querySelectorAll('#maintenance-tbody tr');
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

    document.querySelectorAll('#maintenance-tbody tr').forEach(row => {
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
    const visibleRows = document.querySelectorAll('#maintenance-tbody tr:not([style*="display: none"])');
    document.getElementById('maintenance-footer-count').textContent = `Showing ${visibleRows.length} reports`;
  }

  document.addEventListener('DOMContentLoaded', function () {
    updateMaintenanceCounters();
    filterMaintenanceStatus(''); // sa all mag start biskan i refresh
  });
</script>