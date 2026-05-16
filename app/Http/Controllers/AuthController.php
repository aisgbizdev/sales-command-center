<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $users = User::query()
            ->with(['unit:id,name', 'team:id,name'])
            ->orderByRaw("case role when 'super_admin' then 1 when 'kepala' then 2 when 'manager' then 3 when 'penjualan' then 4 else 5 end")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'unit_id', 'team_id']);

        return view('auth.login', [
            'loginUsers' => $users,
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak valid.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
