<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // If user has 2FA enabled, require OTP challenge
        if ($user->google2fa_secret) {
            Auth::guard('web')->logout();

            // Park the user ID until they complete 2FA
            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:remember', (bool)$request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->route('2fa.verify');
        }

        $request->session()->regenerate();

        // No 2FA required normal login
        return redirect()->intended(route('dashboard', absolute: false))->with('success', 'You are now logged in!');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
