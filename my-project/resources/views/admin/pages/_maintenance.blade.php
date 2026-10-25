<section id="page-maintenance" class="page">
  <div class="page-header">
    <div>
      <div class="page-title">Maintenance &amp; Reports</div>
      <div class="page-sub">View and manage staff reports on bike issues</div>
    </div>
    <div class="maint-counters">
      <div class="maint-counter"><div class="counter-dot" style="background:#eab308"></div>{{ $pendingReports ?? 2 }} Pending</div>
      <div class="maint-counter"><div class="counter-dot" style="background:#3b82f6"></div>{{ $inProgressReports ?? 1 }} In Progress</div>
      <div class="maint-counter"><div class="counter-dot" style="background:#22c55e"></div>{{ $resolvedReports ?? 5 }} Resolved</div>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Report ID</th><th>Type</th><th>Bike</th><th>Reported By</th><th>Description</th><th>Date</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        @foreach($reports ?? [] as $report)
        <tr>
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
          <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $report->description }}</td>
          <td style="color:#6b7280;font-size:13px">{{ $report->created_at->format('M d, Y') }}</td>
          <td>
            <span class="badge {{ $report->status === 'Pending' ? 'status-pending' : ($report->status === 'In Progress' ? 'status-inprogress' : 'status-resolved') }}">
              {{ $report->status }}
            </span>
          </td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openViewReport(...)">View</button>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="table-footer">
      <p>Showing {{ count($reports ?? []) }} reports</p>
    </div>
  </div>
</section>