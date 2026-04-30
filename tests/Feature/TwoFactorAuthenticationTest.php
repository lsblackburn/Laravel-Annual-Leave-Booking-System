<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
