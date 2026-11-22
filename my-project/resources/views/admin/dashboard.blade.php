@extends('layouts.admin')

@section('content')
    @include('admin.pages._dashboard')
    {{-- Role-based: ang page ma-include ra kung naa ang privilege --}}
    @adminCan('Manage Staff')
        @include('admin.pages._staff')
    @endadminCan
    @adminCan('View Inventory')
        @include('admin.pages._bikes')
    @endadminCan
    @adminCan('Handle Maintenance')
        @include('admin.pages._maintenance')
    @endadminCan
    @adminCan('Manage Pricing')
        @include('admin.pages._pricing')
    @endadminCan
    @include('admin.pages._logs')
    @include('admin.pages._reports')
@endsection

@section('modals')
    @include('admin.modals._staff_modals')
    @include('admin.modals._bike_modals')
    @include('admin.modals._report_modals')
@endsection

@section('scripts')
    @include('admin.scripts._staff_scripts')
    @include('admin.scripts._bike_scripts')
    @include('admin.scripts._dashboard_charts')
    @include('admin.scripts._reports_charts')

    @if(request('tab') || session('active_tab'))
        <script>
            window.addEventListener('DOMContentLoaded', function () {
                var tab = @json(request('tab', session('active_tab')));
                var btn = document.getElementById('nav-' + tab);
                if (btn) {
                    nav(tab, btn);
                }
            });
        </script>
    @endif

    @if($errors->has('permission'))
        <script>
            window.addEventListener('DOMContentLoaded', function () {
                showToast(@json($errors->first('permission')));
            });
        </script>
    @endif

    @if(session('success'))
        <script>
            window.addEventListener('DOMContentLoaded', function () {
                showToast(@json(session('success')));
            });
        </script>
    @endif
@endsection