<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('settings.index'); // This is your settings dashboard
    }

    public function profile()
    {
        return view('settings.profile'); // Example profile page
    }

    public function security()
    {
        return view('settings.security'); // Example security page
    }
}
