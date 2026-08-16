<section id="page-dashboard" class="page active">
  <div class="page-header flex items-start justify-between mb-8">
    <div>
      <div class="page-title text-2xl font-bold text-gray-900">Admin Dashboard</div>
      <div class="page-sub text-sm text-gray-500 mt-1">Welcome back! Here's what's happening today!</div>
    </div>
    <div class="flex gap-2.5">
      <button class="btn btn-outline">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export Report
      </button>
    </div>
  </div>

  {{-- STAT CARDS --}}
  <div class="grid grid-cols-4 gap-5 mb-6">
    <div class="bg-white border border-gray-200 rounded-xl p-6">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-[10px] flex items-center justify-center bg-green-50 text-green-600">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/><path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/></svg>
        </div>
        <span class="text-[13px] font-medium text-green-600">↑ 12%</span>
      </div>
      <div class="text-[28px] font-bold text-gray-900 mb-1">{{ $stats['total_bikes'] ?? 24 }}</div>
      <div class="text-[13px] text-gray-500">Total Bikes</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-6">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-[10px] flex items-center justify-center bg-blue-50 text-blue-600">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <span class="text-[13px] font-medium text-green-600">↑ 8%</span>
      </div>
      <div class="text-[28px] font-bold text-gray-900 mb-1">{{ $stats['active_rentals'] ?? 8 }}</div>
      <div class="text-[13px] text-gray-500">Active Rentals</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-6">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-[10px] flex items-center justify-center bg-orange-50 text-orange-600">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </div>
        <span class="text-[13px] font-medium text-red-500">↑ 2</span>
      </div>
      <div class="text-[28px] font-bold text-gray-900 mb-1">{{ $stats['under_maintenance'] ?? 3 }}</div>
      <div class="text-[13px] text-gray-500">Under Maintenance</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-6">
      <div class="flex items-center justify-between mb-4">
        <div class="w-12 h-12 rounded-[10px] flex items-center justify-center bg-green-50 text-green-600">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <span class="text-[13px] font-medium text-green-600">↑ 18%</span>
      </div>
      <div class="text-[28px] font-bold text-gray-900 mb-1">₱{{ number_format($stats['revenue'] ?? 19800) }}</div>
      <div class="text-[13px] text-gray-500">Revenue This Month</div>
    </div>
  </div>

  {{-- CHARTS ROW 1 --}}
  <div class="grid grid-cols-3 gap-5 mb-5">
    <div class="col-span-2 bg-white border border-gray-200 rounded-xl p-6">
      <div class="text-base font-semibold text-gray-900 mb-4">Weekly Rentals</div>
      <div class="relative w-full h-[280px]"><canvas id="weeklyChart"></canvas></div>
    </div>
    <div class="col-span-1 bg-white border border-gray-200 rounded-xl p-6">
      <div class="text-base font-semibold text-gray-900 mb-4">Bike Type Distribution</div>
      <div class="relative w-full h-[280px]"><canvas id="pieChart"></canvas></div>
      <div class="flex flex-col gap-2.5 mt-4">
        <div class="flex items-center justify-between text-[13px] text-gray-700">
          <div class="flex items-center"><div class="w-2.5 h-2.5 rounded-full mr-2 bg-[#8b5cf6]"></div>E-Scooter</div><span>48</span>
        </div>
        <div class="flex items-center justify-between text-[13px] text-gray-700">
          <div class="flex items-center"><div class="w-2.5 h-2.5 rounded-full mr-2 bg-[#3b82f6]"></div>Lady's/Men's Bike</div><span>68</span>
        </div>
        <div class="flex items-center justify-between text-[13px] text-gray-700">
          <div class="flex items-center"><div class="w-2.5 h-2.5 rounded-full mr-2 bg-[#22c55e]"></div>Mountain Bike</div><span>89</span>
        </div>
        <div class="flex items-center justify-between text-[13px] text-gray-700">
          <div class="flex items-center"><div class="w-2.5 h-2.5 rounded-full mr-2 bg-[#0ea5e9]"></div>City Bike</div><span>72</span>
        </div>
        <div class="flex items-center justify-between text-[13px] text-gray-700">
          <div class="flex items-center"><div class="w-2.5 h-2.5 rounded-full mr-2 bg-[#f59e0b]"></div>Kiddie Bikes</div><span>40</span>
        </div>
      </div>
    </div>
  </div>

  {{-- CHARTS ROW 2 --}}
  <div class="grid grid-cols-2 gap-5 mb-5">
    <div class="bg-white border border-gray-200 rounded-xl p-6">
      <div class="text-base font-semibold text-gray-900 mb-4">Revenue vs Rentals (Monthly)</div>
      <div class="relative w-full h-[280px]"><canvas id="revenueChart"></canvas></div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-6">
      <div class="text-base font-semibold text-gray-900 mb-4">Peak Rental Hours</div>
      <div class="relative w-full h-[280px]"><canvas id="peakChart"></canvas></div>
    </div>
  </div>

  {{-- RECENT ACTIVITY --}}
  <div class="table-wrap">
    <div class="px-6 py-5 border-b border-gray-200">
      <div class="text-base font-semibold text-gray-900">Recent Activity</div>
    </div>
    <div class="divide-y divide-gray-100">
      <div class="flex items-start gap-3 px-6 py-4">
        <div class="w-2 h-2 rounded-full mt-2 shrink-0 bg-green-500"></div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-medium text-gray-900">Bike Rented</div>
          <div class="text-sm text-gray-600 mt-0.5">Mountain Pro X1 (BK-001) rented by Juan dela Cruz</div>
          <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              2 minutes ago
            </span>
            <span>Staff: Alice Cooper</span>
          </div>
        </div>
        <span class="badge badge-green">Rental</span>
      </div>
      <div class="flex items-start gap-3 px-6 py-4">
        <div class="w-2 h-2 rounded-full mt-2 shrink-0 bg-blue-500"></div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-medium text-gray-900">Bike Returned</div>
          <div class="text-sm text-gray-600 mt-0.5">City Explorer C3 (BK-004) returned by Maria Santos</div>
          <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              15 minutes ago
            </span>
            <span>Staff: Bob Wilson</span>
          </div>
        </div>
        <span class="badge badge-blue">Return</span>
      </div>
      <div class="flex items-start gap-3 px-6 py-4">
        <div class="w-2 h-2 rounded-full mt-2 shrink-0 bg-orange-500"></div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-medium text-gray-900">Maintenance Report</div>
          <div class="text-sm text-gray-600 mt-0.5">Mountain Trail M2 (BK-005) flagged for flat rear tire</div>
          <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              1 hour ago
            </span>
            <span>Staff: Carol Martinez</span>
          </div>
        </div>
        <span class="badge badge-orange">Report</span>
      </div>
      <div class="flex items-start gap-3 px-6 py-4">
        <div class="w-2 h-2 rounded-full mt-2 shrink-0 bg-green-500"></div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-medium text-gray-900">New Staff Added</div>
          <div class="text-sm text-gray-600 mt-0.5">David Lee added as Technician</div>
          <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              3 hours ago
            </span>
            <span>By: Admin</span>
          </div>
        </div>
        <span class="badge badge-green">Staff</span>
      </div>
    </div>
  </div>
</section>