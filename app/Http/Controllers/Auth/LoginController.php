<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * After successful authentication.
     */
    protected function authenticated(Request $request, $user)
    {
        // Mark user as active
        $user->update(['is_active' => true]);
    }

    /**
     * On logout set is_active back to 0.
     */
    public function logout(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $user->update(['is_active' => false]); // 👈 set inactive on logout
        }

        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
