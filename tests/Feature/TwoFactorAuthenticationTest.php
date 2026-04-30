<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_enabling_two_factor_encrypts_the_stored_secret_while_preserving_model_access(): void
    {
        $user = User::factory()->create();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $this->actingAs($user)
            ->withSession(['2fa_secret' => $secret])
            ->post(route('2fa.enable'), ['otp' => $google2fa->getCurrentOtp($secret)])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($secret, $user->fresh()->google2fa_secret);
        $this->assertNotSame($secret, DB::table('users')->whereKey($user->id)->value('google2fa_secret'));
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

    public function test_valid_otp_logs_in_user_after_two_factor_is_enabled(): void
    {
        $user = $this->createUserWithTwoFactorSecret();
        $otp = (new Google2FA())->getCurrentOtp($user->google2fa_secret);

        $response = $this->withSession(['2fa:user_id' => $user->id])
            ->post(route('2fa'), ['one_time_password' => $otp]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
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
