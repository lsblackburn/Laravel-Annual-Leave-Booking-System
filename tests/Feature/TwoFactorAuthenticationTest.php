<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireTwoFactorForRememberedSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_two_factor_enabled_are_sent_to_the_otp_challenge_after_password_login(): void
    {
        $user = $this->createUserWithTwoFactorSecret();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('2fa.verify'));
        $response->assertSessionHas('2fa:user_id', $user->id);
    }

    public function test_two_factor_verify_page_requires_pending_challenge_session(): void
    {
        $this->get(route('2fa.verify'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your login session expired. Please sign in again.');
    }

    public function test_two_factor_verify_page_renders_with_pending_challenge_session(): void
    {
        $user = $this->createUserWithTwoFactorSecret();

        $this->withSession(['2fa:user_id' => $user->id])
            ->get(route('2fa.verify'))
            ->assertOk();
    }

    public function test_two_factor_password_handoff_does_not_rotate_existing_remember_token(): void
    {
        $user = $this->createUserWithTwoFactorSecret();
        $user->setRememberToken('existing-remember-token');
        $user->save();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('2fa.verify'));
        $response->assertSessionHas('2fa:user_id', $user->id);
        $response->assertSessionHas('2fa:remember', true);
        $this->assertSame('existing-remember-token', $user->fresh()->getRememberToken());
    }

    public function test_failed_otp_submissions_are_rate_limited(): void
    {
        $user = $this->createUserWithTwoFactorSecret();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withSession(['2fa:user_id' => $user->id])
                ->post(route('2fa'), ['one_time_password' => '000000'])
                ->assertRedirect()
                ->assertSessionHasErrors(['one_time_password']);
        }

        $this->withSession(['2fa:user_id' => $user->id])
            ->post(route('2fa'), ['one_time_password' => '000000'])
            ->assertRedirect()
            ->assertSessionHasErrors(['one_time_password']);

        $this->assertTrue(RateLimiter::tooManyAttempts($this->otpThrottleKey($user), 5));
    }

    public function test_otp_submission_requires_pending_challenge_session(): void
    {
        $this->post(route('2fa'), ['one_time_password' => '123456'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your login session expired. Please sign in again.');
    }

    public function test_otp_submission_requires_configured_two_factor_secret(): void
    {
        $user = User::factory()->create();

        $this->withSession(['2fa:user_id' => $user->id])
            ->post(route('2fa'), ['one_time_password' => '123456'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Two-factor authentication is not configured for this account.');
    }

    public function test_enabling_two_factor_encrypts_the_stored_secret_while_preserving_model_access(): void
    {
        $user = User::factory()->create();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $this->actingAs($user)
            ->withSession(['2fa_secret' => $secret])
            ->post(route('2fa.enable'), ['otp' => $google2fa->getCurrentOtp($secret)])
            ->assertRedirect(route('dashboard'))
            ->assertSessionMissing('2fa_secret');

        $this->assertSame($secret, $user->fresh()->google2fa_secret);
        $this->assertNotSame($secret, DB::table('users')->whereKey($user->id)->value('google2fa_secret'));
    }

    public function test_two_factor_secret_is_hidden_from_serialized_user_payloads(): void
    {
        $user = $this->createUserWithTwoFactorSecret();

        $this->assertSame($user->google2fa_secret, $user->fresh()->google2fa_secret);
        $this->assertArrayNotHasKey('google2fa_secret', $user->fresh()->toArray());
    }

    public function test_enabling_two_factor_requires_a_setup_secret_in_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('2fa.enable'), ['otp' => '123456'])
            ->assertRedirect(route('2fa.setup'))
            ->assertSessionHas('error', 'Your 2FA setup session expired. Please start again.');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_two_factor_setup_page_generates_secret_and_qr_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('2fa.setup'))
            ->assertOk()
            ->assertViewHas('qrCodeSvg')
            ->assertViewHas('secret')
            ->assertSessionHas('2fa_secret');
    }

    public function test_two_factor_setup_redirects_when_already_enabled(): void
    {
        $user = $this->createUserWithTwoFactorSecret();

        $this->actingAs($user)
            ->get(route('2fa.setup'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', '2FA already enabled.');
    }

    public function test_invalid_otp_does_not_enable_two_factor(): void
    {
        $user = User::factory()->create();
        $secret = (new Google2FA())->generateSecretKey();

        $this->actingAs($user)
            ->withSession(['2fa_secret' => $secret])
            ->post(route('2fa.enable'), ['otp' => '000000'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Invalid OTP. Please try again.');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_valid_otp_logs_in_user_after_two_factor_is_enabled(): void
    {
        $user = $this->createUserWithTwoFactorSecret();
        $otp = (new Google2FA())->getCurrentOtp($user->google2fa_secret);

        $response = $this->withSession(['2fa:user_id' => $user->id])
            ->post(route('2fa'), ['one_time_password' => $otp]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_valid_otp_preserves_remember_me_cookie(): void
    {
        $user = $this->createUserWithTwoFactorSecret();
        $otp = (new Google2FA())->getCurrentOtp($user->google2fa_secret);

        $response = $this->withSession([
            '2fa:user_id' => $user->id,
            '2fa:remember' => true,
        ])->post(route('2fa'), ['one_time_password' => $otp]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
        $response->assertCookie(Auth::guard('web')->getRecallerName());
    }

    public function test_remembered_two_factor_session_is_sent_back_to_otp_challenge(): void
    {
        $user = $this->createUserWithTwoFactorSecret();
        $guard = Auth::guard('web');

        $request = Request::create('/verify-email/123/example?expires=1700000000&signature=test', 'GET');
        $request->setLaravelSession($this->app['session']->driver());

        $guard->setRequest($request);
        $guard->setUser($user);

        $viaRemember = new \ReflectionProperty($guard, 'viaRemember');
        $viaRemember->setValue($guard, true);

        $response = (new RequireTwoFactorForRememberedSession())->handle($request, fn () => response('ok'));

        $this->assertGuest();
        $this->assertSame(route('2fa.verify'), $response->getTargetUrl());
        $this->assertSame($user->id, $request->session()->get('2fa:user_id'));
        $this->assertTrue($request->session()->get('2fa:remember'));
        $this->assertSame(
            'http://localhost/verify-email/123/example?expires=1700000000&signature=test',
            $request->session()->get('url.intended')
        );
    }

    public function test_remembered_two_factor_session_does_not_store_intended_url_for_non_get_requests(): void
    {
        $user = $this->createUserWithTwoFactorSecret();
        $guard = Auth::guard('web');

        $request = Request::create('/profile', 'PATCH');
        $request->setLaravelSession($this->app['session']->driver());

        $guard->setRequest($request);
        $guard->setUser($user);

        $viaRemember = new \ReflectionProperty($guard, 'viaRemember');
        $viaRemember->setValue($guard, true);

        $response = (new RequireTwoFactorForRememberedSession())->handle($request, fn () => response('ok'));

        $this->assertGuest();
        $this->assertSame(route('2fa.verify'), $response->getTargetUrl());
        $this->assertSame($user->id, $request->session()->get('2fa:user_id'));
        $this->assertTrue($request->session()->get('2fa:remember'));
        $this->assertNull($request->session()->get('url.intended'));
    }

    public function test_valid_otp_disables_two_factor(): void
    {
        $user = $this->createUserWithTwoFactorSecret();
        $otp = (new Google2FA())->getCurrentOtp($user->google2fa_secret);

        $this->actingAs($user)
            ->post(route('2fa.disable'), ['otp' => $otp])
            ->assertRedirect(route('dashboard'));

        $this->assertNull($user->fresh()->google2fa_secret);
    }

    public function test_disable_form_redirects_when_two_factor_is_not_enabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('2fa.disable.form'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', '2FA is not enabled.');
    }

    public function test_disable_form_renders_when_two_factor_is_enabled(): void
    {
        $user = $this->createUserWithTwoFactorSecret();

        $this->actingAs($user)
            ->get(route('2fa.disable.form'))
            ->assertOk();
    }

    public function test_disable_requires_two_factor_to_be_enabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('2fa.disable'), ['otp' => '123456'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', '2FA is not enabled.');
    }

    public function test_invalid_otp_does_not_disable_two_factor(): void
    {
        $user = $this->createUserWithTwoFactorSecret();

        $this->actingAs($user)
            ->post(route('2fa.disable'), ['otp' => '000000'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Invalid OTP. Please try again.');

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    private function createUserWithTwoFactorSecret(): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'google2fa_secret' => (new Google2FA())->generateSecretKey(),
        ])->save();

        return $user;
    }

    private function otpThrottleKey(User $user): string
    {
        return '2fa|'.$user->id.'|127.0.0.1';
    }
}
