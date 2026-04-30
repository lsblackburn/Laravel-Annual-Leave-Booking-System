<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorForRememberedSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');
        $user = $guard->user();

        if ($user && $guard->viaRemember() && $user->hasTwoFactorEnabled()) {
            $guard->logoutCurrentDevice();

            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:remember', true);
            $request->session()->regenerate();

            return redirect()->route('2fa.verify');
        }

        return $next($request);
    }
}
