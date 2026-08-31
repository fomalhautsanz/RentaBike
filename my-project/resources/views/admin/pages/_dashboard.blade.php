{{-- mao ni akong gi fix na ui kay naguba pag merge argh --}}

{{-- update: CONNECTED NA SA REAL DATA --}}
<section id="page-dashboard" class="page active">
  <div class="page-header">
    <div>
      <div class="page-title">Admin Dashboard</div>
      <div class="page-sub">Welcome back! Here's what's happening today!</div>
    </div>
  </div>

  {{-- STAT CARDS --}}
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon" style="background:#f0fdf4">
          <svg width="22" height="22" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/><path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/></svg>
        </div>
        <span class="stat-change up">↑ 12%</span>
      </div>
      <div class="stat-value">{{ $stats['total_bikes'] }}</div>
      <div class="stat-label">Total Bikes</div>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon" style="background:#eff6ff">
          <svg width="22" height="22" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
      </div>
      <div class="stat-value">{{ $stats['active_rentals'] }}</div>
      <div class="stat-label">Active Rentals</div>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon" style="background:#fff7ed">
          <svg width="22" height="22" fill="none" stroke="#ea580c" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </div>
      </div>
      <div class="stat-value">{{ $stats['under_maintenance'] }}</div>
      <div class="stat-label">Under Maintenance</div>
    </div>
    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon" style="background:#f0fdf4">
          <svg width="22" height="22" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
      </div>
      <div class="stat-value">₱{{ number_format($stats['revenue'], 2) }}</div>
      <div class="stat-label">Revenue This Month</div>
    </div>
  </div>

  {{-- CHARTS ROW 1 --}}
  <div class="charts-grid">
    <div class="card">
      <div class="chart-title">Weekly Rentals</div>
      <div class="chart-wrap"><canvas id="weeklyChart"></canvas></div>
    </div>
    <div class="card">
      <div class="chart-title">Bike Type Distribution</div>
      <div class="chart-wrap"><canvas id="pieChart"></canvas></div>
      @php
        $pieColors = ['#8b5cf6', '#3b82f6', '#22c55e', '#0ea5e9', '#f59e0b', '#ef4444', '#14b8a6'];
      @endphp
      <div class="pie-legend">
        @forelse ($bikeTypeDistribution as $type => $count)
          <div class="pie-legend-item">
            <div style="display:flex;align-items:center">
              <div class="pie-dot" style="background:{{ $pieColors[$loop->index % count($pieColors)] }}"></div>{{ $type }}
            </div>
            <span>{{ $count }}</span>
          </div>
        @empty
          <div class="pie-legend-item">No bikes recorded yet.</div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- CHARTS ROW 2 --}}
  <div class="charts-grid-2">
    <div class="card">
      <div class="chart-title">Revenue vs Rentals (Monthly)</div>
      <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
    </div>
    <div class="card">
      <div class="chart-title">Peak Rental Hours</div>
      <div class="chart-wrap"><canvas id="peakChart"></canvas></div>
    </div>
  </div>

  {{-- RECENT ACTIVITY --}}
  <div class="table-wrap">
    <div style="padding:20px 24px;border-bottom:1px solid #e5e7eb">
      <div style="font-size:16px;font-weight:600;color:#111827">Recent Activity</div>
    </div>
    <div class="activity-list">
      @php
        $activityStyle = fn ($action) => match (true) {
            str_contains($action, 'OPEN_RENTAL')  => ['#22c55e', 'badge-green', 'Rental'],
            str_contains($action, 'CLOSE_RENTAL')  => ['#3b82f6', 'badge-blue', 'Return'],
            str_contains($action, 'REPORT')        => ['#f97316', 'badge-orange', 'Report'],
            str_contains($action, 'STAFF')         => ['#22c55e', 'badge-green', 'Staff'],
            str_contains($action, 'BIKE')          => ['#0ea5e9', 'badge-blue', 'Bike'],
            default                                 => ['#6b7280', 'badge-gray', 'Activity'],
        };
      @endphp

      @forelse ($recentActivity as $log)
        @php [$dotColor, $badgeClass, $badgeLabel] = $activityStyle($log->action); @endphp
        <div class="activity-item">
          <div class="activity-dot" style="background:{{ $dotColor }}"></div>
          <div class="activity-body">
            <div class="activity-type">{{ ucwords(str_replace('_', ' ', strtolower($log->action))) }}</div>
            <div class="activity-detail">{{ $log->details }}</div>
            <div class="activity-meta">
              <span>
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ $log->timestamp->diffForHumans() }}
              </span>
              <span>{{ ucfirst($log->user_type) }}: {{ $log->actor_name }}</span>
            </div>
          </div>
          <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
        </div>
      @empty
        <div class="activity-item">
          <div class="activity-body">
            <div class="activity-detail">No activity recorded yet.</div>
          </div>
        </div>
      @endforelse
    </div>
  </div>
</section>