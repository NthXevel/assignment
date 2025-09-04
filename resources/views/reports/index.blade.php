@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Reports Dashboard</h1>
    <ul>
        <li><a href="{{ route('reports.stock') }}">Stock Report</a></li>
        <li><a href="{{ route('reports.orders') }}">Orders Report</a></li>
        <li><a href="{{ route('reports.sales') }}">Sales Report</a></li>
    </ul>
</div>
<div class="container">
    <h1>Settings Dashboard</h1>
    <ul>
        <li><a href="{{ route('settings.profile') }}">Profile Settings</a></li>
        <li><a href="{{ route('settings.security') }}">Security Settings</a></li>
    </ul>
</div>
@endsection
