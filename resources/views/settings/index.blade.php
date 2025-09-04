@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Settings</h1>
    <ul class="list-group mb-4">
        <li class="list-group-item">
            <a href="{{ route('settings.profile') }}">Profile Settings</a>
        </li>
        <li class="list-group-item">
            <a href="{{ route('settings.security') }}">Security Settings</a>
        </li>
    </ul>

    <!-- Logout button -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-danger">
            Logout
        </button>
    </form>
</div>
@endsection
