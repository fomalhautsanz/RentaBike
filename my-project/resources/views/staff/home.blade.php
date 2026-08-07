@extends('layouts.staff')

@section('content')
    @include('staff.pages._login')
    @include('staff.pages._home')
    @include('staff.pages._scanner')
    @include('staff.pages._inventory')
    @include('staff.pages._report')
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