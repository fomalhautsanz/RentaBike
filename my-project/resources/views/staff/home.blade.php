@extends('layouts.staff')

@section('content'){{-- HOME SCREEN --}}
@php($staffPermissions = $staffPermissions ?? [])
@php($canViewInventory = in_array('View Inventory', $staffPermissions, true))
@php($canAddInventory = in_array('Add Inventory', $staffPermissions, true))
@php($canEditInventory = in_array('Edit Inventory', $staffPermissions, true))
@php($canDeleteInventory = in_array('Delete Inventory', $staffPermissions, true))
@php($canManageStaff = in_array('Manage Staff', $staffPermissions, true))
@php($canHandleMaintenance = in_array('Handle Maintenance', $staffPermissions, true))
<section class="screen" id="home">
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-logo">
        <img src="{{ asset('images/system_logo.png') }}" alt="RentaBike Logo" style="width:28px;height:28px;object-fit:contain;border-radius:6px;">
     </div>
      <div>
        <div class="topbar-brand">RentaBike</div>
        <div class="topbar-sub">Energy Park · Staff View</div>
      </div>
    </div>
    <div class="time-chip" id="liveTime">--:-- --</div>
  </div>

  <div class="content">
    {{-- STAT CARDS ROW 1 --}}
    <div class="stat-row">
      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-icon" style="background:#f0fdf4">
            <svg fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/>
              <path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/>
              <path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/>
            </svg>
          </div>
          <span class="stat-change up">Ready</span>
        </div>
        <div class="stat-value">{{ $stats['available'] ?? 161 }}</div>
        <div class="stat-label">Available</div>
        <div class="stat-track"><div class="stat-fill-green" style="width:65%"></div></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-icon" style="background:#eff6ff">
            <svg fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
              <polyline points="16 7 22 7 22 13"/>
            </svg>
          </div>
          <span class="stat-change neutral">Active</span>
        </div>
        <div class="stat-value">{{ $stats['rented'] ?? 87 }}</div>
        <div class="stat-label">Rented</div>
        <div class="stat-track"><div class="stat-fill-blue" style="width:35%"></div></div>
      </div>
    </div>

    {{-- STAT CARDS ROW 2 --}}
    <div class="stat-row" style="margin-top:-8px">
      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-icon" style="background:#fff7ed">
            <svg fill="none" stroke="#ea580c" stroke-width="2" viewBox="0 0 24 24">
              <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
          </div>
          <span class="stat-change neutral">!</span>
        </div>
        <div class="stat-value">{{ $stats['repair'] ?? 10 }}</div>
        <div class="stat-label">Repair</div>
        <div class="stat-track"><div class="stat-fill-orange" style="width:4%"></div></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-icon" style="background:#f0fdf4">
            <svg fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <span class="stat-change up">Live</span>
        </div>
        <div class="stat-value">{{ $stats['total'] ?? 248 }}</div>
        <div class="stat-label">Total Bikes</div>
        <div class="stat-track"><div class="stat-fill-green" style="width:100%"></div></div>
      </div>
    </div>

    {{-- BIKE LIST --}}
    @if($canViewInventory)
    <div class="section-title">
      <h3>Bike Inventory</h3>
      <a onclick="goTo('inventory')">View all</a>
    </div>
    <div class="bike-list">
      <div class="bike-card" onclick="openModal('available', { id:'BK-101', condition:'Ready for Rental', lastBorrower:'Joshua Rivera', lastReturned:'Today, 12:30 PM' })">
        <div class="bike-icon green">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/><path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/></svg>
        </div>
        <div class="bike-meta"><h4>BK-101</h4><p>Available for rental</p></div>
        <span class="badge badge-green"><span class="badge-dot badge-dot-green"></span>Available</span>
        <div class="bike-card-arrow"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></div>
      </div>
      <div class="bike-card" onclick="openModal('rented', { id:'BK-102', borrower:'Ashley Mendoza', borrowTime:'2:00 PM', returnTime:'4:00 PM' })">
        <div class="bike-icon blue">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/><path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/></svg>
        </div>
        <div class="bike-meta"><h4>BK-102</h4><p>Borrowed by Ashley</p></div>
        <span class="badge badge-blue"><span class="badge-dot badge-dot-blue"></span>Rented</span>
        <div class="bike-card-arrow"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></div>
      </div>
      @if($canHandleMaintenance)
      <div class="bike-card" onclick="openModal('maintenance', { id:'BK-103', issue:'Flat rear tire', updatedBy:'Admin', date:'May 27, 2026' })">
        <div class="bike-icon orange">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M15 6a1 1 0 1 0 2 0 1 1 0 0 0-2 0z"/><path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/></svg>
        </div>
        <div class="bike-meta"><h4>BK-103</h4><p>Needs maintenance</p></div>
        <span class="badge badge-orange"><span class="badge-dot badge-dot-orange"></span>Repair</span>
        <div class="bike-card-arrow"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></div>
      </div>
      @endif
    </div>
    @endif
  </div>

  {{-- BOTTOM NAV --}}
  <nav class="bottom-nav">
    <button class="nav-btn active" onclick="navActive(this); goTo('home')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
      <span>Home</span>
    </button>
    @if($canViewInventory)
    <button class="nav-btn" onclick="navActive(this); goTo('inventory')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M3 17V7h4l4-4 4 4h2l1 4h1v6"/></svg>
      <span>Inventory</span>
    </button>
    @endif
    <button class="nav-btn nav-qr" onclick="navActive(this); goTo('scanner')">
      <div class="qr-pill">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="5" height="5" x="3" y="3" rx=".5"/><rect width="5" height="5" x="16" y="3" rx=".5"/><rect width="5" height="5" x="3" y="16" rx=".5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/></svg>
      </div>
      <span>Scan QR</span>
    </button>
    @if($canHandleMaintenance)
    <button class="nav-btn" onclick="navActive(this); goTo('report')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <span>Report</span>
    </button>
    @endif
    @if($canManageStaff)
    <button class="nav-btn" onclick="navActive(this); goTo('staff-management')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      <span>Staff</span>
    </button>
    @endif
  </nav>
</section>
    @include('staff.pages._home')
    @include('staff.pages._scanner')
    @if($canViewInventory)
      @include('staff.pages._inventory')
    @endif
    @if($canHandleMaintenance)
      @include('staff.pages._report')
    @endif
    @if($canManageStaff)
      @include('staff.pages._staff_management')
    @endif
    @include('staff.pages._report_form')
    @include('staff.pages._rental_form')
    @include('staff.pages._success')
@endsection

@section('modals')
    @include('staff.modals._bike_modals')
@endsection

@section('scripts')
    @include('staff.scripts._staff_scripts')
@endsection