<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $r)
    {
        $data = $r->validate(['email' => 'required|email|max:254', 'password' => 'required|string|max:1024']);
        $key = 'login:'.hash('sha256', mb_strtolower($data['email']).'|'.$r->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }
        if (! Auth::attempt([...$data, 'active' => true])) {
            RateLimiter::hit($key, 300);
            throw ValidationException::withMessages(['email' => 'The credentials are incorrect or the account is disabled.']);
        }
        RateLimiter::clear($key);
        $r->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('login');
    }
}
