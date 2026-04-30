<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    
    public function show_verify(Request $request)
    {
        if (! $request->session()->has('2fa:user_id')) {
            return redirect()->route('login')->with('error', 'Your login session expired. Please sign in again.');
        }

        return view('auth.2fa_verify');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|digits:6',
        ]);

        $userId = $request->session()->get('2fa:user_id');

        if (!$userId) {
            return redirect()->route('login')->with('error', 'Your login session expired. Please sign in again.');
        }

        $user = User::find($userId);

        if (!$user || empty($user->google2fa_secret)) {
            return redirect()->route('login')->with('error', 'Two-factor authentication is not configured for this account.');
        }

        $google2fa = new Google2FA();

        $otpValid = $google2fa->verifyKey($user->google2fa_secret, $request->input('one_time_password'));

        if (! $otpValid) {
            return back()->with('error', 'Invalid OTP. Please try again.');
        }

        $remember = (bool) $request->session()->get('2fa:remember', false);

        Auth::login($user, $remember);

        // Clear 2FA session placeholders and harden session
        $request->session()->forget(['2fa:user_id', '2fa:remember']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('success', 'You are now logged in!');
    }

    public function setup()
    {
        $google2fa = new Google2FA();

        $user = Auth::user();

        if ($user->google2fa_secret) {
            return redirect()->route('dashboard')->with('error', '2FA already enabled.');
        }

        $secret = $google2fa->generateSecretKey();

        $qrUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(200),
                new SvgImageBackEnd()
            )
        );

        $qrCodeSvg = base64_encode($writer->writeString($qrUrl));

        session(['2fa_secret' => $secret]);

        return view('auth.2fa_setup', compact('qrCodeSvg', 'secret'));
    }

    public function enable(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = Auth::user();
        $secret = session('2fa_secret');
        $google2fa = new Google2FA();

        if ($google2fa->verifyKey($secret, $request->otp)) {
            $user->google2fa_secret = $secret;
            $user->save();

            return redirect()->route('dashboard')->with('success', '2FA enabled successfully!');
        }

        return back()->with('error', 'Invalid OTP. Please try again.');
    }

    public function show_disable_form()
    {
        $user = Auth::user();

        if (!$user->google2fa_secret) {
            return redirect()->route('dashboard')->with('error', '2FA is not enabled.');
        }

        return view('auth.2fa_disable');
    }

    public function disable(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = Auth::user();

        if (!$user->google2fa_secret) {
            return redirect()->route('dashboard')->with('error', '2FA is not enabled.');
        }

        $google2fa = new Google2FA();

        $otpValid = $google2fa->verifyKey($user->google2fa_secret, $request->input('otp'));

        if (!$otpValid) {
            return back()->with('error', 'Invalid OTP. Please try again.');
        }

        $user->google2fa_secret = null;
        $user->save();

        session()->forget('google2fa_passed');


        return redirect()->route('dashboard')->with('success', '2FA has been disabled.');
    }

}
