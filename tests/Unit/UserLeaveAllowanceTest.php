<?php

namespace Tests\Unit;

use App\Models\Leave;
use App\Models\LeaveSetting;
use App\Models\UserDepartment;
use App\Models\User;
use App\Models\WorkDay;
use App\Services\LeaveAllowanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_pending_leave_number_returns_pending_leave_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-06'));
        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 4,
        ]);

        $user = User::factory()->create(['leave_allowance' => 20]);

        $this->createLeave($user, '2026-05-01', '2026-05-03', 'pending');
        $this->createLeave($user, '2026-05-04', '2026-05-04', 'pending', true);
        $this->createLeave($user, '2026-05-04', '2026-05-04', 'approved');
        $this->createLeave($user, '2026-05-05', '2026-05-05', 'rejected');
        $this->createLeave($user, '2026-03-05', '2026-03-05', 'pending');

        $this->assertSame(3.5, $user->calculatePendingLeaveNumber());
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

    public function test_leave_usage_only_counts_configured_working_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-06'));
        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 1,
        ]);
        WorkDay::whereIn('day', ['Saturday', 'Sunday'])->update(['active' => false]);

        $user = User::factory()->create(['leave_allowance' => 20]);

        $this->createLeave($user, '2026-02-13', '2026-02-16', 'approved');

        $this->assertSame(2.0, $user->approvedLeaveDaysUsed());
        $this->assertSame(18.0, $user->remainingLeaveAllowance());
    }

    public function test_half_day_leave_on_non_working_day_does_not_use_allowance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-06'));
        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 1,
        ]);
        WorkDay::where('day', 'Saturday')->update(['active' => false]);

        $user = User::factory()->create(['leave_allowance' => 20]);

        $this->createLeave($user, '2026-02-14', '2026-02-14', 'approved', true);

        $this->assertSame(0.0, $user->approvedLeaveDaysUsed());
        $this->assertSame(20.0, $user->remainingLeaveAllowance());
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

    public function test_calculating_leave_allowance_falls_back_to_user_allowance_when_settings_are_missing(): void
    {
        LeaveSetting::query()->delete();

        $user = User::factory()->create([
            'employment_start_date' => '2020-01-01',
            'leave_allowance' => 13,
        ]);

        $this->assertSame(13.0, app(LeaveAllowanceService::class)->calculateAllowance($user));
    }

    public function test_leave_request_without_refresh_settings_uses_existing_leave_allowance(): void
    {
        LeaveSetting::query()->delete();

        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->createLeave($user, '2026-02-02', '2026-02-02', 'approved');

        app(LeaveAllowanceService::class)->ensureLeaveRequestFitsAllowance($user, [
            'start_date' => '2026-02-03',
            'end_date' => '2026-02-03',
            'is_half_day' => false,
        ]);

        $this->assertTrue(true);
    }

    public function test_leave_request_without_refresh_settings_ignores_request_being_updated(): void
    {
        LeaveSetting::query()->delete();

        $user = User::factory()->create(['leave_allowance' => 1]);
        $leave = $this->createLeave($user, '2026-02-02', '2026-02-02', 'pending');

        app(LeaveAllowanceService::class)->ensureLeaveRequestFitsAllowance($user, [
            'start_date' => '2026-02-02',
            'end_date' => '2026-02-02',
            'is_half_day' => false,
        ], $leave);

        $this->assertTrue(true);
    }

    public function test_leave_request_without_refresh_settings_is_rejected_when_allowance_is_exceeded(): void
    {
        LeaveSetting::query()->delete();

        $user = User::factory()->create(['leave_allowance' => 1]);
        $this->createLeave($user, '2026-02-02', '2026-02-02', 'approved');

        $this->expectException(ValidationException::class);

        app(LeaveAllowanceService::class)->ensureLeaveRequestFitsAllowance($user, [
            'start_date' => '2026-02-03',
            'end_date' => '2026-02-03',
            'is_half_day' => false,
        ]);
    }

    public function test_cross_refresh_request_skips_non_working_segment_before_validating_next_allowance_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01'));

        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 4,
        ]);
        WorkDay::whereIn('day', ['Saturday', 'Sunday'])->update(['active' => false]);

        $user = User::factory()->create([
            'leave_allowance' => 1,
            'employment_start_date' => '2025-01-01',
        ]);

        app(LeaveAllowanceService::class)->ensureLeaveRequestFitsAllowance($user, [
            'start_date' => '2027-03-27',
            'end_date' => '2027-04-01',
            'is_half_day' => false,
        ]);

        $this->assertTrue(true);
    }

    public function test_cross_refresh_request_continues_past_empty_allowance_year_segment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01'));

        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 4,
        ]);
        WorkDay::query()->update(['active' => false]);
        WorkDay::where('day', 'Wednesday')->update(['active' => true]);

        $user = User::factory()->create([
            'leave_allowance' => 1,
            'employment_start_date' => '2025-01-01',
        ]);

        app(LeaveAllowanceService::class)->ensureLeaveRequestFitsAllowance($user, [
            'start_date' => '2026-03-31',
            'end_date' => '2026-04-01',
            'is_half_day' => false,
        ]);

        $this->assertTrue(true);
    }

    public function test_leave_days_within_window_returns_zero_when_request_is_outside_window(): void
    {
        $service = app(LeaveAllowanceService::class);
        $method = new \ReflectionMethod($service, 'leaveDaysWithinWindow');

        $days = $method->invoke($service, [
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-02',
            'is_half_day' => false,
        ], Carbon::parse('2026-03-01'), Carbon::parse('2026-04-01'));

        $this->assertSame(0.0, $days);
    }

    public function test_half_day_leave_uses_half_a_day_when_no_refresh_settings_exist(): void
    {
        LeaveSetting::query()->delete();

        $user = User::factory()->create(['leave_allowance' => 20]);
        $this->createLeave($user, '2026-02-02', '2026-02-02', 'approved', true);

        $this->assertSame(0.5, $user->approvedLeaveDaysUsed());
    }

    public function test_department_relationship_and_employee_role_helper(): void
    {
        $department = UserDepartment::create(['department' => 'Operations']);
        $user = User::factory()->create([
            'role' => 'employee',
            'department_id' => $department->id,
        ]);

        $this->assertTrue($user->isEmployee());
        $this->assertTrue($user->department->is($department));
    }

    private function createLeave(User $user, string $startDate, string $endDate, string $status, bool $isHalfDay = false): Leave
    {
        $leave = Leave::create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Annual leave',
            'is_half_day' => $isHalfDay,
        ]);

        $leave->forceFill(['status' => $status])->save();

        return $leave;
    }
}
