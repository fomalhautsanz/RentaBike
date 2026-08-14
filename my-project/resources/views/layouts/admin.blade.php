<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<title>RentaBike — Admin Portal</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<style>

  /* ── RESET ── */
  *, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  /* ── BASE ── */
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #f9fafb;
    color: #111827;
    font-size: 15px;
  }

  /* ── LAYOUT ── */
  .app {
    display: flex;
    height: 100vh;
    overflow: hidden;
  }

  .sidebar {
    width: 256px;
    background: #fff;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
  }

  .main {
    flex: 1;
    overflow-y: auto;
    background: #f9fafb;
  }

  /* ── SIDEBAR ── */
  .sidebar-logo {
    padding: 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .logo-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .logo-icon svg { color: #fff; }

  .logo-title {
    font-weight: 700;
    color: #111827;
    font-size: 15px;
  }

  .logo-sub {
    font-size: 12px;
    color: #6b7280;
  }

  nav {
    flex: 1;
    padding: 16px;
  }

  nav ul {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .nav-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    border-radius: 10px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
    transition: background .15s;
    text-align: left;
  }

  .nav-btn:hover { background: #f3f4f6; }

  .nav-btn.active {
    background: #f0fdf4;
    color: #15803d;
    font-weight: 500;
  }

  .nav-btn.active svg { color: #16a34a; }
  .nav-btn svg { color: #9ca3af; flex-shrink: 0; }

  .sidebar-user {
    padding: 16px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dcfce7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 13px;
    color: #15803d;
    flex-shrink: 0;
  }

  .user-name {
    font-size: 14px;
    font-weight: 500;
    color: #111827;
  }

  .user-email {
    font-size: 12px;
    color: #6b7280;
  }

  /* ── PAGES ── */
  .page {
    display: none;
    padding: 32px;
  }

  .page.active { display: block; }

  .page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 32px;
  }

  .page-title {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
  }

  .page-sub {
    color: #6b7280;
    margin-top: 4px;
    font-size: 14px;
  }

  /* ── BUTTONS ── */
  .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all .15s;
    text-decoration: none;
  }

  .btn-primary {
    background: #16a34a;
    color: #fff;
  }

  .btn-primary:hover { background: #15803d; }

  .btn-outline {
    background: #fff;
    color: #374151;
    border: 1px solid #d1d5db;
  }

  .btn-outline:hover { background: #f9fafb; }

  .btn-sm {
    padding: 6px 12px;
    font-size: 13px;
  }

  /* ── CARDS ── */
  .card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
  }

  /* ── STATS ── */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
  }

  .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
  }

  .stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
  }

  .stat-change {
    font-size: 13px;
    font-weight: 500;
  }

  .stat-change.up { color: #16a34a; }
  .stat-change.down { color: #ef4444; }

  .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
  }

  .stat-label {
    font-size: 13px;
    color: #6b7280;
  }

  /* ── CHARTS ── */
  .charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
  }

  .charts-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
  }

  .chart-title {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 16px;
  }

  .chart-wrap {
    position: relative;
    width: 100%;
    height: 280px;
  }

  /* ── TABLES ── */
  .table-wrap {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
  }

  .table-toolbar {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .search-wrap {
    position: relative;
    flex: 1;
  }

  .search-wrap svg {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    width: 16px;
    height: 16px;
  }

  .search-input,
  .filter-select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    color: #374151;
    outline: none;
    background: #fff;
  }

  .search-input {
    padding-left: 36px;
    width: 100%;
  }

  .search-input:focus,
  .filter-select:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, .1);
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  thead tr {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
  }

  th {
    text-align: left;
    padding: 12px 24px;
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
  }

  td {
    padding: 14px 24px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
    color: #374151;
  }

  tr:last-child td { border-bottom: none; }
  tbody tr:hover td { background: #fafafa; }

  .table-footer {
    padding: 12px 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .table-footer p {
    font-size: 13px;
    color: #6b7280;
  }

  /* ── PAGINATION ── */
  .pagination {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .page-btn {
    padding: 4px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
    background: #fff;
  }

  .page-btn:hover { background: #f3f4f6; }

  .page-btn.active {
    background: #16a34a;
    color: #fff;
    border-color: #16a34a;
  }

  /* ── BADGES ── */
  .badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
  }

  .badge-green  { background: #f0fdf4; color: #15803d; }
  .badge-blue   { background: #eff6ff; color: #1d4ed8; }
  .badge-yellow { background: #fefce8; color: #a16207; }
  .badge-red    { background: #fef2f2; color: #b91c1c; }
  .badge-orange { background: #fff7ed; color: #c2410c; }
  .badge-purple { background: #faf5ff; color: #7e22ce; }
  .badge-gray   { background: #f3f4f6; color: #374151; }

  .status-pending    { background: #fefce8; color: #a16207; }
  .status-inprogress { background: #eff6ff; color: #1d4ed8; }
  .status-resolved   { background: #f0fdf4; color: #15803d; }

  /* ── ACTION BUTTONS ── */
  .action-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    transition: background .15s;
  }

  .action-btn:hover { background: #f3f4f6; }

  /* ── PIE LEGEND ── */
  .pie-legend {
    margin-top: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .pie-legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
  }

  .pie-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 8px;
  }

  /* ── ACTIVITY ── */
  .activity-list {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  .activity-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #f3f4f6;
  }

  .activity-item:last-child { border-bottom: none; }

  .activity-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 5px;
    flex-shrink: 0;
  }

  .activity-body { flex: 1; }

  .activity-type {
    font-weight: 500;
    color: #111827;
    font-size: 14px;
  }

  .activity-detail {
    font-size: 13px;
    color: #6b7280;
    margin-top: 2px;
  }

  .activity-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-top: 6px;
  }

  .activity-meta span {
    font-size: 12px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 4px;
  }

  /* ── PROGRESS ── */
  .progress-bar {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
  }

  .progress-fill {
    height: 100%;
    background: #22c55e;
    border-radius: 3px;
  }

  /* ── MODALS ── */
  .modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .35);
    z-index: 100;
    align-items: center;
    justify-content: center;
  }

  .modal-backdrop.open { display: flex; }

  .modal {
    background: #fff;
    border-radius: 16px;
    padding: 28px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
  }

  .modal-lg { max-width: 640px; }

  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
  }

  .modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
  }

  .modal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
  }

  .modal-close:hover { background: #f3f4f6; }

  /* ── FORMS ── */
  .form-group { margin-bottom: 16px; }

  .form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
  }

  .form-input,
  .form-select,
  .form-textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    color: #111827;
    outline: none;
    font-family: inherit;
  }

  .form-input:focus,
  .form-select:focus,
  .form-textarea:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, .1);
  }

  .form-actions {
    display: flex;
    gap: 12px;
    margin-top: 8px;
  }

  .form-actions .btn {
    flex: 1;
    justify-content: center;
  }

  /* ── DELETE WARNING ── */
  .delete-warning {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 8px 0 20px;
  }

  .delete-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #fef2f2;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
  }

  .delete-icon svg { color: #ef4444; }

  .delete-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
  }

  .delete-desc {
    font-size: 14px;
    color: #6b7280;
    line-height: 1.5;
  }

  /* ── MISC ── */
  .rank-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f0fdf4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
    color: #15803d;
  }

  .maint-counters {
    display: flex;
    gap: 8px;
  }

  .maint-counter {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #374151;
  }

  .counter-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  .contact-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .contact-line {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #6b7280;
  }

  .report-info-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 10px;
    margin-bottom: 20px;
  }

  .report-type-icon {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .photo-placeholder {
    background: #f3f4f6;
    border: 2px dashed #d1d5db;
    border-radius: 10px;
    padding: 32px;
    text-align: center;
  }

  /* ── QR ── */
  .qr-display {
    text-align: center;
    padding: 16px 0;
  }

  .qr-box { display: inline-block; margin-bottom: 12px; }

  .qr-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 2px;
    width: 128px;
    height: 128px;
  }

  .qr-cell { border-radius: 1px; }

  /* ── ICONS ── */
  svg { display: inline-block; vertical-align: middle; }
  .icon    { width: 20px; height: 20px; }
  .icon-sm { width: 16px; height: 16px; }
  .icon-lg { width: 24px; height: 24px; }

</style>
</head>
<body>

<div class="app" id="main-app">
  {{-- SIDEBAR --}}
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/>
          <path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/>
          <path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/>
        </svg>
      </div>
      <div>
        <div class="logo-title">RentaBike</div>
        <div class="logo-sub">Admin Portal</div>
      </div>
    </div>

    <nav>
      <ul>
        <li>
          <button class="nav-btn active" id="nav-dashboard" onclick="nav('dashboard', this)">
            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            Dashboard
          </button>
        </li>
        <li>
          <button class="nav-btn" id="nav-staff" onclick="nav('staff', this)">
            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Staff Management
          </button>
        </li>
        <li>
          <button class="nav-btn" id="nav-bikes" onclick="nav('bikes', this)">
            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/><path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/></svg>
            Bike Inventory
          </button>
        </li>
        <li>
          <button class="nav-btn" id="nav-maintenance" onclick="nav('maintenance', this)">
            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            Maintenance
          </button>
        </li>
        <li>
          <button class="nav-btn" id="nav-reports" onclick="nav('reports', this)">
            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Reports & Analytics
          </button>
        </li>
        <li>
          <button class="nav-btn" id="nav-rentals" onclick="nav('rentals', this)">
            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Activity Logs
          </button>
        </li>
      </ul>
    </nav>

    <div class="sidebar-user">
      <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}</div>
      <div style="flex:1;min-width:0">
        <div class="user-name">{{ auth()->user()->name ?? 'Admin' }}</div>
        <div class="user-email">{{ auth()->user()->email ?? 'admin@rentabike.com' }}</div>
      </div>
      <form method="POST" action="#">
        @csrf
        <button type="submit" style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:4px">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
      </form>
    </div>
  </aside>

  {{-- MAIN CONTENT --}}
  <main class="main">
    @yield('content')
  </main>
</div>

{{-- MODALS --}}
@yield('modals')

{{-- SCRIPTS --}}
<script>
function nav(id, btn) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('page-' + id).classList.add('active');
  btn.classList.add('active');
}
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function closeModalOutside(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }
</script>
@yield('scripts')
</body>
</html>