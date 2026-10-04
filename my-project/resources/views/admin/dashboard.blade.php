@extends('layouts.admin')

@section('content')
    @include('admin.pages._dashboard')
    @include('admin.pages._staff')
    @include('admin.pages._bikes')
    @include('admin.pages._maintenance')
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

    @if(session('active_tab'))
        <script>
            window.addEventListener('DOMContentLoaded', function () {
                var tab = @json(session('active_tab'));
                var btn = document.getElementById('nav-' + tab);
                if (btn) {
                    nav(tab, btn);
                }
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