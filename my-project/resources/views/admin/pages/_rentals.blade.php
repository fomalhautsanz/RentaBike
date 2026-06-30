<section id="page-rentals" class="page">
  <div class="page-header">
    <div>
      <div class="page-title">Rental History</div>
      <div class="page-sub">Full log of all bike rentals and returns</div>
    </div>
    <button class="btn btn-outline">
      <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </button>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Rental ID</th><th>Borrower</th><th>Bike</th><th>Staff</th><th>Borrow Time</th><th>Return Time</th><th>Duration</th><th>Status</th></tr>
      </thead>
      <tbody>
        @foreach($rentals ?? [] as $rental)
        <tr>
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
      <p>Showing {{ count($rentals ?? []) }} rentals</p>
    </div>
  </div>
</section>