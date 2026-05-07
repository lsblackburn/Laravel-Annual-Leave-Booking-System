<?php

namespace Tests\Feature;

use App\Models\LeaveSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncLeaveAllowanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_february_twenty_ninth_refresh_runs_on_february_twenty_eighth_in_non_leap_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-02-28'));
        $this->configureFebruaryTwentyNinthRefresh();
        $user = $this->createUserForAllowanceSync();

        $this->artisan('leave:sync-allowances')
            ->assertSuccessful();

        $this->assertSame(24.0, (float) $user->refresh()->leave_allowance);
    }

    public function test_february_twenty_ninth_refresh_runs_on_february_twenty_ninth_in_leap_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-02-29'));
        $this->configureFebruaryTwentyNinthRefresh();
        $user = $this->createUserForAllowanceSync();

        $this->artisan('leave:sync-allowances')
            ->assertSuccessful();

        $this->assertSame(23.0, (float) $user->refresh()->leave_allowance);
    }

    public function test_february_twenty_ninth_refresh_does_not_run_before_effective_refresh_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-02-27'));
        $this->configureFebruaryTwentyNinthRefresh();
        $user = $this->createUserForAllowanceSync();

        $this->artisan('leave:sync-allowances')
            ->assertSuccessful();

        $this->assertSame(20.0, (float) $user->refresh()->leave_allowance);
    }

    private function configureFebruaryTwentyNinthRefresh(): void
    {
        LeaveSetting::first()->update([
            'base_allowance' => 20,
            'increase_after_years' => 2,
            'increase_by_days' => 1,
            'maximum_allowance' => 30,
            'leave_refresh_day' => 29,
            'leave_refresh_month' => 2,
        ]);
    }

    private function createUserForAllowanceSync(): User
    {
        return User::factory()->create([
            'employment_start_date' => '2020-02-01',
            'leave_allowance' => 20,
        ]);
    }
}
