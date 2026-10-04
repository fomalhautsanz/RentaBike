@extends('layouts.staff')

@section('content')
    @php($staffPermissions = $staffPermissions ?? [])
    @php($canViewInventory = in_array('View Inventory', $staffPermissions, true))
    @php($canManageStaff = in_array('Manage Staff', $staffPermissions, true))
    @php($canHandleMaintenance = in_array('Handle Maintenance', $staffPermissions, true))
    @include('staff.pages._home_feature')
    @include('staff.pages._scanner')
    @if($canViewInventory ?? false)
        @include('staff.pages._inventory')
    @endif
    @if($canAddInventory ?? false)
        @include('staff.pages._inventory_create')
    @endif
    @if($canHandleMaintenance ?? false)
        @include('staff.pages._report')
    @endif
    @if($canManageStaff ?? false)
        @include('staff.pages._staff_management')
    @endif
    @include('staff.pages._report_form')
    @include('staff.pages._rental_form')
    @include('staff.pages._success')

    <nav class="bottom-nav">
        <button class="nav-btn active" data-screen="home" onclick="navActive(this); goTo('home')">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            <span>Home</span>
        </button>
        <button class="nav-btn nav-qr" data-screen="scanner" onclick="navActive(this); goTo('scanner')">
            <div class="qr-pill">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="5" height="5" x="3" y="3" rx=".5"/><rect width="5" height="5" x="16" y="3" rx=".5"/><rect width="5" height="5" x="3" y="16" rx=".5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/></svg>
            </div>
            <span>Scan QR</span>
        </button>
        <button class="nav-btn" data-screen="report" onclick="navActive(this); goTo('report')">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>Report</span>
        </button>
    </nav>
@endsection

@section('modals')
    @include('staff.modals._bike_modals')
@endsection

@section('scripts')
    @include('staff.scripts._staff_scripts')
@endsection
