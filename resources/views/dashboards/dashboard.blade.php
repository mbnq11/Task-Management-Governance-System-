@extends('layouts.layout')


@section('content')

@php
$currentUser = Auth::user();
@endphp

{{-- هذا الملف يعمل الآن كموزع فقط --}}

@if($currentUser->role === 'ciso')
{{-- واجهة المدير العام --}}
@include('dashboards.ciso')

@elseif($currentUser->role === 'manager')
{{-- واجهة مدير الإدارة --}}
@include('dashboards.manager')

@elseif($currentUser->role === 'team_leader')
{{-- واجهة قائد الفريق --}}
@include('dashboards.team_leader')

@elseif($currentUser->role === 'auditor')
{{-- واجهة المدقق --}}
@include('dashboards.auditor')

@else
{{-- يتم تحميل واجهة الموظف --}}
@include('dashboards.employee')
@endif

@endsection