<section id="page-reports" class="page">
  <div class="page-header">
    <div>
      <div class="page-title">Reports &amp; Analytics</div>
      <div class="page-sub">Track performance and gain insights into your business</div>
    </div>
    <div style="display:flex;gap:10px">
      <select class="filter-select" onchange="filterReportsRange(this.value)">
        <option value="ytd">Year to Date</option>
        <option value="30">Last 30 Days</option>
        <option value="90">Last 90 Days</option>
        <option value="12m">Last 12 Months</option>
      </select>
      <button class="btn btn-outline" onclick="exportReports()">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export Data
      </button>
    </div>
  </div>

  {{-- STAT CARDS --}}
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon" style="background:#f0fdf4">
          <svg width="22" height="22" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <span class="stat-change up">↑ 18.2%</span>
      </div>
      <div class="stat-value">₱{{ number_format($reportStats['total_revenue'] ?? 88800) }}</div>
      <div class="stat-label">Total Revenue (YTD)</div>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon" style="background:#eff6ff">
          <svg width="22" height="22" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        </div>
        <span class="stat-change up">↑ 12.5%</span>
      </div>
      <div class="stat-value">{{ number_format($reportStats['total_rentals'] ?? 1773) }}</div>
      <div class="stat-label">Total Rentals (YTD)</div>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon" style="background:#f5f3ff">
          <svg width="22" height="22" fill="none" stroke="#7c3aed" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <span class="stat-change up">↑ 24.3%</span>
      </div>
      <div class="stat-value">{{ number_format($reportStats['unique_customers'] ?? 892) }}</div>
      <div class="stat-label">Unique Customers</div>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon" style="background:#fffbeb">
          <svg width="22" height="22" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <span class="stat-change down">↓ 5.8%</span>
      </div>
      <div class="stat-value">{{ $reportStats['avg_duration'] ?? '3.2h' }}</div>
      <div class="stat-label">Avg Rental Duration</div>
    </div>
  </div>

  {{-- CHARTS ROW --}}
  <div class="charts-grid-2">
    <div class="card">
      <div class="chart-title">Revenue &amp; Rentals Trend</div>
      <div class="chart-wrap"><canvas id="reportsRevenueChart"></canvas></div>
    </div>
    <div class="card">
      <div class="chart-title">Peak Rental Hours</div>
      <div class="chart-wrap"><canvas id="reportsPeakChart"></canvas></div>
    </div>
  </div>

  {{-- BIKE TYPE PERFORMANCE BREAKDOWN --}}
  <div class="table-wrap">
    <div style="padding:20px 24px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center">
      <div>
        <div style="font-size:16px;font-weight:600;color:#111827">Performance by Bike Type</div>
        <div style="font-size:13px;color:#6b7280;margin-top:2px">Rentals and revenue contribution per category</div>
      </div>
    </div>
    <table>
      <thead>
        <tr><th>Bike Type</th><th>Total Rentals</th><th>Revenue</th><th>Avg. Duration</th><th>Utilization</th></tr>
      </thead>
      <tbody>
        @forelse($bikeTypePerformance ?? [] as $row)
        <tr>
          <td style="font-weight:500">{{ $row->type }}</td>
          <td>{{ number_format($row->total_rentals) }}</td>
          <td>₱{{ number_format($row->revenue) }}</td>
          <td>{{ $row->avg_duration }}</td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="flex:1;height:6px;background:#f3f4f6;border-radius:4px;overflow:hidden;max-width:100px">
                <div style="height:100%;background:#22c55e;width:{{ $row->utilization }}%"></div>
              </div>
              <span style="font-size:12px;color:#6b7280">{{ $row->utilization }}%</span>
            </div>
          </td>
        </tr>
        @empty
        <tr><td style="font-weight:500">Mountain Bike</td><td>612</td><td>₱30,600</td><td>1.6h</td>
          <td><div style="display:flex;align-items:center;gap:8px"><div style="flex:1;height:6px;background:#f3f4f6;border-radius:4px;overflow:hidden;max-width:100px"><div style="height:100%;background:#22c55e;width:78%"></div></div><span style="font-size:12px;color:#6b7280">78%</span></div></td>
        </tr>
        <tr><td style="font-weight:500">Lady's/Men's Bike</td><td>471</td><td>₱23,550</td><td>1.4h</td>
          <td><div style="display:flex;align-items:center;gap:8px"><div style="flex:1;height:6px;background:#f3f4f6;border-radius:4px;overflow:hidden;max-width:100px"><div style="height:100%;background:#22c55e;width:64%"></div></div><span style="font-size:12px;color:#6b7280">64%</span></div></td>
        </tr>
        <tr><td style="font-weight:500">City Bike</td><td>398</td><td>₱19,900</td><td>1.5h</td>
          <td><div style="display:flex;align-items:center;gap:8px"><div style="flex:1;height:6px;background:#f3f4f6;border-radius:4px;overflow:hidden;max-width:100px"><div style="height:100%;background:#22c55e;width:55%"></div></div><span style="font-size:12px;color:#6b7280">55%</span></div></td>
        </tr>
        <tr><td style="font-weight:500">E-Scooter</td><td>216</td><td>₱10,800</td><td>1.1h</td>
          <td><div style="display:flex;align-items:center;gap:8px"><div style="flex:1;height:6px;background:#f3f4f6;border-radius:4px;overflow:hidden;max-width:100px"><div style="height:100%;background:#22c55e;width:41%"></div></div><span style="font-size:12px;color:#6b7280">41%</span></div></td>
        </tr>
        <tr><td style="font-weight:500">Kiddie Bikes</td><td>76</td><td>₱3,800</td><td>0.9h</td>
          <td><div style="display:flex;align-items:center;gap:8px"><div style="flex:1;height:6px;background:#f3f4f6;border-radius:4px;overflow:hidden;max-width:100px"><div style="height:100%;background:#22c55e;width:22%"></div></div><span style="font-size:12px;color:#6b7280">22%</span></div></td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>