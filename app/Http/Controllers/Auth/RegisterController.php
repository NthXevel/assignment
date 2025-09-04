<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Validate registration input.
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'branch_id' => ['required', 'integer', 'between:1,5'], // restrict to 1-5
            'role' => ['required', 'in:admin,stock_manager,branch_manager,order_creator'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     */
    protected function create(array $data)
    {
        return User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'branch_id' => $data['branch_id'],
            'role' => $data['role'],
        ]);
    }

    protected function registered(\Illuminate\Http\Request $request, $user)
    {
        // Prevent auto login
        $this->guard()->logout();

        return redirect('/login')->with('status', 'Registration successful! Please login.');
    }

}
