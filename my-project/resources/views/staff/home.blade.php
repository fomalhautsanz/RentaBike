@extends('layouts.staff')

@section('content')
    @php($staffPermissions = $staffPermissions ?? [])
    @php($canViewInventory = in_array('View Inventory', $staffPermissions, true))
    @php($canManageStaff = in_array('Manage Staff', $staffPermissions, true))
    @php($canHandleMaintenance = in_array('Handle Maintenance', $staffPermissions, true))
    @include('staff.pages._home')
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
@endsection

@section('modals')
    @include('staff.modals._bike_modals')
@endsection

@section('scripts')
    @include('staff.scripts._staff_scripts')

    @if(session('success') || request('screen'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const params = new URLSearchParams(window.location.search);
                const screen = params.get('screen') || @json(session('active_screen') ?? null);

                if (screen && document.getElementById(screen)) {
                    goTo(screen);
                }

                const message = @json(session('success') ?: session('status') ?: null);
                if (message) {
                    showToast(message);
                }
            });
        </script>
    @endif
@endsection
