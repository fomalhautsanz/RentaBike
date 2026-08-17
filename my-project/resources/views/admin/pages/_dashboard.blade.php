<section id="page-dashboard" class="page active">

  {{-- PAGE HEADER --}}
  <div class="flex items-start justify-between mb-8">
    <div>
      <div class="text-[22px] font-bold text-gray-900">Dashboard Overview</div>
      <div class="text-sm text-gray-500 mt-1">Welcome back! Here's what's happening today.</div>
    </div>
    <button class="btn btn-outline">
      <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export Report
    </button>
  </div>

  {{-- STAT CARDS --}}
  <div class="grid grid-cols-4 gap-5 mb-6">

    {{-- Total Bikes --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-5 flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-blue-500">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/><path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/></svg>
        </div>
        <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded-full">+12%</span>
      </div>
      <div>
        <div class="text-[28px] font-bold text-gray-900 leading-none mb-1">{{ $stats['total_bikes'] ?? 248 }}</div>
        <div class="text-[13px] text-gray-500 font-medium">Total Bikes</div>
      </div>
    </div>

    {{-- Active Rentals --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-5 flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-green-500">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
        </div>
        <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded-full">+8%</span>
      </div>
      <div>
        <div class="text-[28px] font-bold text-gray-900 leading-none mb-1">{{ $stats['active_rentals'] ?? 87 }}</div>
        <div class="text-[13px] text-gray-500 font-medium">Active Rentals</div>
      </div>
    </div>

    {{-- Available Bikes --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-5 flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-purple-500">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/><path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/></svg>
        </div>
        <span class="text-xs font-semibold text-red-500 bg-red-50 px-2 py-1 rounded-full">-5%</span>
      </div>
      <div>
        <div class="text-[28px] font-bold text-gray-900 leading-none mb-1">{{ $stats['available_bikes'] ?? 161 }}</div>
        <div class="text-[13px] text-gray-500 font-medium">Available Bikes</div>
      </div>
    </div>

    {{-- Revenue Today --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-5 flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-yellow-400">
          <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded-full">+23%</span>
      </div>
      <div>
        <div class="text-[28px] font-bold text-gray-900 leading-none mb-1">₱{{ number_format($stats['revenue'] ?? 2840) }}</div>
        <div class="text-[13px] text-gray-500 font-medium">Revenue Today</div>
      </div>
    </div>

  </div>

  {{-- CHARTS ROW 1 --}}
  <div class="grid grid-cols-3 gap-5 mb-5">
    <div class="col-span-2 bg-white border border-gray-200 rounded-2xl p-6">
      <div class="text-[15px] font-semibold text-gray-900 mb-4">Weekly Rental Trends</div>
      <div class="relative w-full h-[260px]"><canvas id="weeklyChart"></canvas></div>
    </div>
    <div class="col-span-1 bg-white border border-gray-200 rounded-2xl p-6">
      <div class="text-[15px] font-semibold text-gray-900 mb-4">Bike Types Distribution</div>
      <div class="relative w-full h-[180px]"><canvas id="pieChart"></canvas></div>
      <div class="flex flex-col gap-2 mt-4">
        <div class="flex items-center justify-between text-[13px] text-gray-600">
          <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-[#22c55e]"></div>Mountain</div><span class="font-medium">89</span>
        </div>
        <div class="flex items-center justify-between text-[13px] text-gray-600">
          <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-[#3b82f6]"></div>City Bike</div><span class="font-medium">72</span>
        </div>
        <div class="flex items-center justify-between text-[13px] text-gray-600">
          <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-[#f59e0b]"></div>Kiddie</div><span class="font-medium">40</span>
        </div>
        <div class="flex items-center justify-between text-[13px] text-gray-600">
          <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-[#8b5cf6]"></div>E-Scooter</div><span class="font-medium">48</span>
        </div>
        <div class="flex items-center justify-between text-[13px] text-gray-600">
          <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-[#0ea5e9]"></div>Lady's/Men's</div><span class="font-medium">68</span>
        </div>
      </div>
    </div>
  </div>

  {{-- CHARTS ROW 2 --}}
  <div class="grid grid-cols-2 gap-5 mb-5">
    <div class="bg-white border border-gray-200 rounded-2xl p-6">
      <div class="text-[15px] font-semibold text-gray-900 mb-4">Revenue vs Rentals (Monthly)</div>
      <div class="relative w-full h-[260px]"><canvas id="revenueChart"></canvas></div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl p-6">
      <div class="text-[15px] font-semibold text-gray-900 mb-4">Peak Rental Hours</div>
      <div class="relative w-full h-[260px]"><canvas id="peakChart"></canvas></div>
    </div>
  </div>

  {{-- RECENT ACTIVITY --}}
  <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
      <div class="text-[15px] font-semibold text-gray-900">Recent Activity</div>
    </div>
    <div class="divide-y divide-gray-50">

      <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <div class="w-2 h-2 rounded-full bg-green-500"></div>
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-semibold text-gray-900">Bike Rented</div>
          <div class="text-sm text-gray-500 mt-0.5">Mountain Pro X1 (BK-001) rented by Juan dela Cruz</div>
          <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              2 minutes ago
            </span>
            <span>Staff: Alice Cooper</span>
          </div>
        </div>
        <span class="badge badge-green text-xs">Rental</span>
      </div>

      <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <div class="w-2 h-2 rounded-full bg-blue-500"></div>
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-semibold text-gray-900">Bike Returned</div>
          <div class="text-sm text-gray-500 mt-0.5">City Explorer C3 (BK-004) returned by Maria Santos</div>
          <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              15 minutes ago
            </span>
            <span>Staff: Bob Wilson</span>
          </div>
        </div>
        <span class="badge badge-blue text-xs">Return</span>
      </div>

      <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
        <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <div class="w-2 h-2 rounded-full bg-orange-500"></div>
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-semibold text-gray-900">Maintenance Report</div>
          <div class="text-sm text-gray-500 mt-0.5">Mountain Trail M2 (BK-005) flagged for flat rear tire</div>
          <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              1 hour ago
            </span>
            <span>Staff: Carol Martinez</span>
          </div>
        </div>
        <span class="badge badge-orange text-xs">Report</span>
      </div>

      <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <div class="w-2 h-2 rounded-full bg-green-500"></div>
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-semibold text-gray-900">New Staff Added</div>
          <div class="text-sm text-gray-500 mt-0.5">David Lee added as Technician</div>
          <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              3 hours ago
            </span>
            <span>By: Admin</span>
          </div>
        </div>
        <span class="badge badge-green text-xs">Staff</span>
      </div>

    </div>
  </div>

</section>