<?php

namespace Tests\Unit;

use App\Models\Leave;
use App\Models\LeaveSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLeaveAllowanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_remaining_leave_allowance_only_subtracts_approved_leave_in_current_allowance_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-06'));
        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 4,
        ]);

        $user = User::factory()->create(['leave_allowance' => 20]);

        $this->createLeave($user, '2025-06-10', '2025-06-12', 'approved');
        $this->createLeave($user, '2026-04-10', '2026-04-12', 'approved');
        $this->createLeave($user, '2026-05-01', '2026-05-02', 'pending');

        $this->assertSame(3.0, $user->approvedLeaveDaysUsed());
        $this->assertSame(17.0, $user->remainingLeaveAllowance());
    }

    public function test_leave_crossing_refresh_date_only_counts_days_in_current_allowance_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-06'));
        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 4,
        ]);

        $user = User::factory()->create(['leave_allowance' => 20]);

        $this->createLeave($user, '2026-03-30', '2026-04-02', 'approved');

        $this->assertSame(2.0, $user->approvedLeaveDaysUsed());
        $this->assertSame(18.0, $user->remainingLeaveAllowance());
    }

    public function test_february_twenty_ninth_allowance_year_ends_on_february_twenty_eighth_in_non_leap_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-02-27'));
        LeaveSetting::first()->update([
            'leave_refresh_day' => 29,
            'leave_refresh_month' => 2,
        ]);

        $user = User::factory()->create(['leave_allowance' => 20]);

        $this->createLeave($user, '2025-02-28', '2025-02-28', 'approved');

        $this->assertSame(0.0, $user->approvedLeaveDaysUsed());
        $this->assertSame(20.0, $user->remainingLeaveAllowance());
    }

    public function test_calculating_leave_allowance_preserves_existing_allowance_without_employment_start_date(): void
    {
        LeaveSetting::first()->update([
            'base_allowance' => 20,
            'increase_after_years' => 2,
            'increase_by_days' => 1,
            'maximum_allowance' => 30,
        ]);

        $user = User::factory()->create([
            'employment_start_date' => null,
            'leave_allowance' => 25,
        ]);

        $this->assertSame(25.0, $user->calculateLeaveAllowance());
    }

    public function test_leave_allowance_uses_whole_completed_years_for_service_increases(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-06'));
        LeaveSetting::first()->update([
            'base_allowance' => 20,
            'increase_after_years' => 2,
            'increase_by_days' => 1,
            'maximum_allowance' => 30,
        ]);

        $user = User::factory()->create([
            'employment_start_date' => '2024-04-06',
            'leave_allowance' => 20,
        ]);

        $this->assertSame(21.0, $user->calculateLeaveAllowance());
    }

    public function test_leave_allowance_does_not_apply_next_increment_before_completed_anniversary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-06'));
        LeaveSetting::first()->update([
            'base_allowance' => 20,
            'increase_after_years' => 2,
            'increase_by_days' => 1,
            'maximum_allowance' => 30,
        ]);

        $user = User::factory()->create([
            'employment_start_date' => '2023-05-07',
            'leave_allowance' => 20,
        ]);

        $this->assertSame(21.0, $user->calculateLeaveAllowance());
    }

    public function test_leave_allowance_is_synced_when_user_is_created_with_employment_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-06'));
        LeaveSetting::first()->update([
            'base_allowance' => 20,
            'increase_after_years' => 2,
            'increase_by_days' => 1,
            'maximum_allowance' => 30,
        ]);

        $user = User::factory()->create([
            'employment_start_date' => '2023-05-06',
        ]);

        $this->assertSame(22.0, (float) $user->refresh()->leave_allowance);
    }

    public function test_leave_allowance_uses_database_default_when_user_is_created_without_employment_start_date_or_allowance(): void
    {
        $user = User::create([
            'name' => 'No Start Date',
            'email' => 'no-start-date@example.com',
            'password' => 'password',
            'employment_start_date' => null,
        ]);

        $this->assertSame(20.0, (float) $user->refresh()->leave_allowance);
    }

    public function test_leave_allowance_is_synced_when_employment_start_date_changes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-06'));
        LeaveSetting::first()->update([
            'base_allowance' => 20,
            'increase_after_years' => 2,
            'increase_by_days' => 1,
            'maximum_allowance' => 30,
        ]);

        $user = User::factory()->create([
            'employment_start_date' => '2025-05-06',
        ]);

        $this->assertSame(20.0, (float) $user->refresh()->leave_allowance);

        $user->update(['employment_start_date' => '2023-05-06']);

        $this->assertSame(22.0, (float) $user->refresh()->leave_allowance);
    }

    public function test_leave_allowance_is_not_synced_for_unrelated_user_updates(): void
    {
        $user = User::factory()->create([
            'employment_start_date' => null,
            'leave_allowance' => 25,
        ]);

        $user->forceFill(['leave_allowance' => 17])->save();

        $user->update(['name' => 'Updated User']);

        $this->assertSame(17.0, (float) $user->refresh()->leave_allowance);
    }

    private function createLeave(User $user, string $startDate, string $endDate, string $status): Leave
    {
        $leave = Leave::create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Annual leave',
        ]);

        $leave->forceFill(['status' => $status])->save();

        return $leave;
    }
}
