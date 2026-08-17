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
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #f9fafb;
    color: #111827;
    font-size: 15px;
  }

  /* ── LAYOUT ── */
  .app { display: flex; height: 100vh; overflow: hidden; }
  .sidebar { width: 240px; background: #fff; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; flex-shrink: 0; }
  .main { flex: 1; overflow-y: auto; background: #f9fafb; }

  /* ── SIDEBAR LOGO ── */
  .sidebar-logo {
    padding: 20px 20px 16px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .logo-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
  }
  .logo-icon img { width: 100%; height: 100%; object-fit: cover; }
  .logo-title { font-weight: 700; color: #111827; font-size: 15px; letter-spacing: -.3px; }
  .logo-sub { font-size: 11px; color: #9ca3af; margin-top: 1px; }

  /* ── SIDEBAR NAV ── */
  nav { flex: 1; padding: 12px; overflow-y: auto; }
  nav ul { list-style: none; display: flex; flex-direction: column; gap: 1px; }

  .nav-btn {
    width: 100%;
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px;
    border-radius: 10px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 13.5px;
    font-weight: 500;
    color: #6b7280;
    transition: all .15s;
    text-align: left;
  }
  .nav-btn:hover { background: #f9fafb; color: #374151; }
  .nav-btn.active { background: #f0fdf4; color: #15803d; font-weight: 600; }
  .nav-btn.active svg { color: #16a34a; }
  .nav-btn svg { color: #9ca3af; flex-shrink: 0; width: 18px; height: 18px; }

  /* ── SIDEBAR USER ── */
  .sidebar-user {
    padding: 14px 16px;
    border-top: 1px solid #f3f4f6;
    display: flex; align-items: center; gap: 10px;
  }
  .avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px; color: #fff;
    flex-shrink: 0;
    letter-spacing: .5px;
  }
  .user-name { font-size: 13px; font-weight: 600; color: #111827; }
  .user-email { font-size: 11px; color: #9ca3af; margin-top: 1px; }

  /* ── PAGES ── */
  .page { display: none; padding: 32px; }
  .page.active { display: block; }

  /* ── BUTTONS ── */
  .btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 8px; border: none;
    cursor: pointer; font-size: 13.5px; font-weight: 500;
    transition: all .15s; text-decoration: none;
  }
  .btn-primary { background: #16a34a; color: #fff; }
  .btn-primary:hover { background: #15803d; }
  .btn-outline { background: #fff; color: #374151; border: 1px solid #e5e7eb; }
  .btn-outline:hover { background: #f9fafb; }
  .btn-sm { padding: 6px 12px; font-size: 13px; }

  /* ── TABLES ── */
  .table-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; }
  .table-toolbar { padding: 16px 20px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 10px; }
  .search-wrap { position: relative; flex: 1; }
  .search-wrap svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9ca3af; width: 15px; height: 15px; }
  .search-input, .filter-select { padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13.5px; color: #374151; outline: none; background: #fff; }
  .search-input { padding-left: 34px; width: 100%; }
  .search-input:focus, .filter-select:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.1); }

  table { width: 100%; border-collapse: collapse; }
  thead tr { background: #f9fafb; border-bottom: 1px solid #f3f4f6; }
  th { text-align: left; padding: 11px 20px; font-size: 11.5px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
  td { padding: 13px 20px; border-bottom: 1px solid #f9fafb; font-size: 13.5px; color: #374151; }
  tr:last-child td { border-bottom: none; }
  tbody tr:hover td { background: #fafafa; }

  .table-footer { padding: 12px 20px; border-top: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; }
  .table-footer p { font-size: 13px; color: #9ca3af; }

  /* ── BADGES ── */
  .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
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
  .action-btn { width: 30px; height: 30px; border: none; background: transparent; border-radius: 7px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; color: #9ca3af; transition: background .15s; }
  .action-btn:hover { background: #f3f4f6; color: #374151; }

  /* ── MODALS ── */
  .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 100; align-items: center; justify-content: center; }
  .modal-backdrop.open { display: flex; }
  .modal { background: #fff; border-radius: 16px; padding: 28px; width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; }
  .modal-lg { max-width: 600px; }
  .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
  .modal-title { font-size: 17px; font-weight: 700; color: #111827; }
  .modal-close { width: 30px; height: 30px; border: none; background: transparent; cursor: pointer; border-radius: 7px; display: flex; align-items: center; justify-content: center; color: #9ca3af; }
  .modal-close:hover { background: #f3f4f6; }

  /* ── FORMS ── */
  .form-group { margin-bottom: 14px; }
  .form-label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 5px; }
  .form-input, .form-select, .form-textarea { width: 100%; padding: 9px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13.5px; color: #111827; outline: none; font-family: inherit; background: #fafafa; }
  .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.1); background: #fff; }
  .form-actions { display: flex; gap: 10px; margin-top: 6px; }
  .form-actions .btn { flex: 1; justify-content: center; }

  /* ── DELETE WARNING ── */
  .delete-warning { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 8px 0 20px; }
  .delete-icon { width: 60px; height: 60px; border-radius: 50%; background: #fef2f2; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
  .delete-icon svg { color: #ef4444; }
  .delete-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 6px; }
  .delete-desc { font-size: 13.5px; color: #6b7280; line-height: 1.5; }

  /* ── MISC ── */
  .maint-counters { display: flex; gap: 8px; }
  .maint-counter { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 7px 14px; display: flex; align-items: center; gap: 7px; font-size: 13px; color: #374151; }
  .counter-dot { width: 7px; height: 7px; border-radius: 50%; }
  .contact-cell { display: flex; flex-direction: column; gap: 3px; }
  .contact-line { display: flex; align-items: center; gap: 5px; font-size: 12.5px; color: #6b7280; }

  /* ── QR ── */
  .qr-display { text-align: center; padding: 16px 0; }
  .qr-box { display: inline-block; margin-bottom: 12px; }
  .qr-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px; width: 128px; height: 128px; }
  .qr-cell { border-radius: 1px; }

  /* ── LOGOUT ── */
  .logout-btn { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: none; border-radius: 7px; background: transparent; color: #9ca3af; cursor: pointer; transition: all .15s; }
  .logout-btn:hover { background: #fef2f2; color: #ef4444; }
  .logout-btn svg { width: 17px; height: 17px; }

  /* ── ICONS ── */
  svg { display: inline-block; vertical-align: middle; }
  .icon    { width: 18px; height: 18px; }
  .icon-sm { width: 15px; height: 15px; }
  .icon-lg { width: 22px; height: 22px; }

  /* ── PIE LEGEND ── */
  .pie-legend { display: flex; flex-direction: column; gap: 8px; }
  .pie-legend-item { display: flex; align-items: center; justify-content: space-between; font-size: 13px; }
  .pie-dot { width: 9px; height: 9px; border-radius: 50%; margin-right: 8px; }

  /* ── CHART WRAP ── */
  .chart-wrap { position: relative; width: 100%; height: 280px; }

  /* ── PAGINATION ── */
  .pagination { display: flex; align-items: center; gap: 5px; }
  .page-btn { padding: 4px 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12.5px; color: #374151; cursor: pointer; background: #fff; }
  .page-btn:hover { background: #f9fafb; }
  .page-btn.active { background: #16a34a; color: #fff; border-color: #16a34a; }

  /* ── ID PREVIEW ── */
  .id-preview-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; text-align: center; margin-top: 10px; }
  .id-preview-box svg { width: 36px; height: 36px; color: #d1d5db; margin: 0 auto 6px; display: block; }
  .id-preview-box p { font-size: 12px; color: #9ca3af; }
</style>
</head>
<body>

<div class="app" id="main-app">

  {{-- SIDEBAR --}}
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <img src="{{ asset('images/system_logo.png') }}" alt="RentaBike Logo">
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
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            Overview
          </button>
        </li>
        <li>
          <button class="nav-btn" id="nav-staff" onclick="nav('staff', this)">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Staff Management
          </button>
        </li>
        <li>
          <button class="nav-btn" id="nav-bikes" onclick="nav('bikes', this)">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/><path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/></svg>
            Bike Inventory
          </button>
        </li>
        <li>
          <button class="nav-btn" id="nav-maintenance" onclick="nav('maintenance', this)">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            Maintenance
          </button>
        </li>
        <li>
          <button class="nav-btn" id="nav-reports" onclick="nav('reports', this)">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Reports & Analytics
          </button>
        </li>
        <li>
          <button class="nav-btn" id="nav-rentals" onclick="nav('rentals', this)">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Activity Logs
          </button>
        </li>
      </ul>
    </nav>

    <div class="sidebar-user">
      @php
        $name = auth()->user()->full_name ?? 'Admin';
        $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $name), 0, 2))));
      @endphp
      <div class="avatar">{{ $initials }}</div>
      <div style="flex:1;min-width:0;overflow:hidden">
        <div class="user-name truncate">{{ $name }}</div>
        <div class="user-email truncate">{{ auth()->user()->email ?? 'admin@rentabike.com' }}</div>
      </div>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn" title="Logout">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
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

{{-- BASE SCRIPTS --}}
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