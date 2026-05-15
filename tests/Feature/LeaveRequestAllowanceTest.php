<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\LeaveSetting;
use App\Models\User;
use App\Models\UserDepartment;
use App\Models\WorkDay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestAllowanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-01-01'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_user_cannot_create_leave_request_exceeding_remaining_allowance(): void
    {
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setCalendarYearRefresh();
        $this->createApprovedLeave($user, '2026-01-02', '2026-01-02');

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '10-02-2026',
                'end_date' => '11-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.form'))
            ->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-11',
        ]);
    }

    public function test_user_can_create_leave_request_using_exact_remaining_allowance(): void
    {
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setCalendarYearRefresh();
        $this->createApprovedLeave($user, '2026-01-02', '2026-01-02');

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '10-02-2026',
                'end_date' => '10-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.view'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-10',
            'status' => 'pending',
        ]);
    }

    public function test_non_working_days_do_not_count_against_request_allowance_validation(): void
    {
        $user = User::factory()->create(['leave_allowance' => 1]);
        $this->setCalendarYearRefresh();
        WorkDay::whereIn('day', ['Saturday', 'Sunday'])->update(['active' => false]);

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '13-02-2026',
                'end_date' => '15-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.view'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-02-13',
            'end_date' => '2026-02-15',
            'status' => 'pending',
        ]);
    }

    public function test_request_is_rejected_when_working_days_exceed_remaining_allowance(): void
    {
        $user = User::factory()->create(['leave_allowance' => 1]);
        $this->setCalendarYearRefresh();
        WorkDay::whereIn('day', ['Saturday', 'Sunday'])->update(['active' => false]);

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '13-02-2026',
                'end_date' => '16-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.form'))
            ->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-02-13',
            'end_date' => '2026-02-16',
        ]);
    }

    public function test_user_cannot_create_leave_request_starting_in_the_past(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-14'));
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setCalendarYearRefresh();

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '13-05-2026',
                'end_date' => '13-05-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.form'))
            ->assertSessionHasErrors('start_date');

        $this->assertDatabaseMissing('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-05-13',
        ]);
    }

    public function test_user_can_create_leave_request_starting_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-14'));
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setCalendarYearRefresh();

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '14-05-2026',
                'end_date' => '14-05-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.view'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-05-14',
            'status' => 'pending',
        ]);
    }

    public function test_current_allowance_year_validation_uses_stored_user_allowance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-01'));
        LeaveSetting::first()->update([
            'base_allowance' => 20,
            'increase_after_years' => 2,
            'increase_by_days' => 1,
            'maximum_allowance' => 30,
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 1,
        ]);
        $user = User::factory()->create([
            'employment_start_date' => '2016-01-01',
            'leave_allowance' => 20,
        ]);

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '01-03-2026',
                'end_date' => '31-03-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.form'))
            ->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
        ]);
    }

    public function test_user_cannot_update_leave_request_to_exceed_remaining_allowance(): void
    {
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setCalendarYearRefresh();
        $this->createApprovedLeave($user, '2026-01-02', '2026-01-02');

        $pendingLeave = Leave::create([
            'user_id' => $user->id,
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-10',
            'reason' => 'Annual leave',
            'is_half_day' => false,
        ]);

        $response = $this->actingAs($user)
            ->from(route('leave.edit', $pendingLeave))
            ->put(route('leave.update', $pendingLeave), [
                'start_date' => '10-02-2026',
                'end_date' => '11-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.edit', $pendingLeave))
            ->assertSessionHasErrors('end_date');

        $pendingLeave->refresh();

        $this->assertSame('2026-02-10', $pendingLeave->start_date);
        $this->assertSame('2026-02-10', $pendingLeave->end_date);
    }

    public function test_user_cannot_update_leave_request_to_start_in_the_past(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-14'));
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setCalendarYearRefresh();

        $pendingLeave = Leave::create([
            'user_id' => $user->id,
            'start_date' => '2026-05-20',
            'end_date' => '2026-05-20',
            'reason' => 'Annual leave',
            'is_half_day' => false,
        ]);

        $response = $this->actingAs($user)
            ->from(route('leave.edit', $pendingLeave))
            ->put(route('leave.update', $pendingLeave), [
                'start_date' => '13-05-2026',
                'end_date' => '13-05-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.edit', $pendingLeave))
            ->assertSessionHasErrors('start_date');

        $pendingLeave->refresh();

        $this->assertSame('2026-05-20', $pendingLeave->start_date);
        $this->assertSame('2026-05-20', $pendingLeave->end_date);
    }

    public function test_user_can_create_leave_after_next_refresh_even_if_current_allowance_year_is_exhausted(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-07'));
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setAprilRefresh();
        $this->createApprovedLeave($user, '2026-04-10', '2026-04-11');

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '02-04-2027',
                'end_date' => '02-04-2027',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.view'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leaves', [
            'user_id' => $user->id,
            'start_date' => '2027-04-02',
            'end_date' => '2027-04-02',
            'status' => 'pending',
        ]);
    }

    public function test_cross_refresh_leave_is_validated_against_each_allowance_year_segment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15'));
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setAprilRefresh();
        $this->createApprovedLeave($user, '2025-04-10', '2025-04-10');

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '31-03-2026',
                'end_date' => '01-04-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.view'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-03-31',
            'end_date' => '2026-04-01',
            'status' => 'pending',
        ]);
    }

    public function test_cross_refresh_leave_is_rejected_when_one_allowance_year_segment_exceeds_remaining_allowance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15'));
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setAprilRefresh();
        $this->createApprovedLeave($user, '2025-04-10', '2025-04-11');

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '31-03-2026',
                'end_date' => '01-04-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.form'))
            ->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-03-31',
            'end_date' => '2026-04-01',
        ]);
    }

    public function test_pending_leave_reserves_allowance_against_new_requests(): void
    {
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setCalendarYearRefresh();
        $this->createPendingLeave($user, '2026-02-10', '2026-02-11');

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '12-02-2026',
                'end_date' => '12-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.form'))
            ->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-02-12',
            'end_date' => '2026-02-12',
        ]);
    }

    public function test_deleting_pending_leave_releases_reserved_allowance(): void
    {
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setCalendarYearRefresh();
        $pendingLeave = $this->createPendingLeave($user, '2026-02-10', '2026-02-11');

        $blockedResponse = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '12-02-2026',
                'end_date' => '12-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $blockedResponse->assertSessionHasErrors('end_date');

        $this->actingAs($user)
            ->delete(route('leave.delete', $pendingLeave))
            ->assertRedirect(route('leave.view'));

        $allowedResponse = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '12-02-2026',
                'end_date' => '12-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $allowedResponse
            ->assertRedirect(route('leave.view'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-02-12',
            'end_date' => '2026-02-12',
            'status' => 'pending',
        ]);
    }

    public function test_rejecting_pending_leave_releases_reserved_allowance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setCalendarYearRefresh();
        $pendingLeave = $this->createPendingLeave($user, '2026-02-10', '2026-02-11');

        $blockedResponse = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '12-02-2026',
                'end_date' => '12-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $blockedResponse->assertSessionHasErrors('end_date');

        $this->actingAs($admin)
            ->post(route('admin.leave-requests.response', $pendingLeave), [
                'response' => 'rejected',
            ])
            ->assertRedirect(route('admin.leave-requests'));

        $this->assertSame('rejected', $pendingLeave->refresh()->status);

        $allowedResponse = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '12-02-2026',
                'end_date' => '12-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $allowedResponse
            ->assertRedirect(route('leave.view'))
            ->assertSessionHasNoErrors();
    }

    public function test_admin_cannot_approve_pending_leave_when_approved_usage_would_exceed_allowance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['leave_allowance' => 2]);
        $this->setCalendarYearRefresh();
        $firstPendingLeave = $this->createPendingLeave($user, '2026-02-10', '2026-02-11');
        $secondPendingLeave = $this->createPendingLeave($user, '2026-02-12', '2026-02-13');

        $this->actingAs($admin)
            ->post(route('admin.leave-requests.response', $firstPendingLeave), [
                'response' => 'approved',
            ])
            ->assertRedirect(route('admin.leave-requests'));

        $this->assertSame('approved', $firstPendingLeave->refresh()->status);

        $response = $this->actingAs($admin)
            ->post(route('admin.leave-requests.response', $secondPendingLeave), [
                'response' => 'approved',
            ]);

        $response
            ->assertRedirect(route('admin.leave-requests'))
            ->assertSessionHas('error', 'This leave request would exceed the employee\'s remaining allowance.');

        $this->assertSame('pending', $secondPendingLeave->refresh()->status);
    }

    public function test_user_cannot_create_leave_when_it_would_leave_department_uncovered(): void
    {
        $department = UserDepartment::create(['department' => 'Finance']);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'leave_allowance' => 20,
        ]);
        $colleague = User::factory()->create([
            'department_id' => $department->id,
            'leave_allowance' => 20,
        ]);
        $this->setCalendarYearRefresh();
        $this->createApprovedLeave($colleague, '2026-02-10', '2026-02-10');

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '10-02-2026',
                'end_date' => '10-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.form'))
            ->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-10',
        ]);
    }

    public function test_single_person_department_can_request_leave(): void
    {
        $department = UserDepartment::create(['department' => 'Finance']);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'leave_allowance' => 20,
        ]);
        $this->setCalendarYearRefresh();

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '10-02-2026',
                'end_date' => '10-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.view'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leaves', [
            'user_id' => $user->id,
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-10',
            'status' => 'pending',
        ]);
    }

    public function test_department_leave_is_allowed_when_another_department_member_remains_available(): void
    {
        $department = UserDepartment::create(['department' => 'Finance']);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'leave_allowance' => 20,
        ]);
        $awayColleague = User::factory()->create([
            'department_id' => $department->id,
            'leave_allowance' => 20,
        ]);
        User::factory()->create([
            'department_id' => $department->id,
            'leave_allowance' => 20,
        ]);
        $this->setCalendarYearRefresh();
        $this->createApprovedLeave($awayColleague, '2026-02-10', '2026-02-10');

        $response = $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => '10-02-2026',
                'end_date' => '10-02-2026',
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response
            ->assertRedirect(route('leave.view'))
            ->assertSessionHasNoErrors();
    }

    public function test_admin_cannot_approve_leave_when_it_would_leave_department_uncovered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $department = UserDepartment::create(['department' => 'Finance']);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'leave_allowance' => 20,
        ]);
        $colleague = User::factory()->create([
            'department_id' => $department->id,
            'leave_allowance' => 20,
        ]);
        $this->setCalendarYearRefresh();
        $pendingLeave = $this->createPendingLeave($user, '2026-02-10', '2026-02-10');
        $this->createApprovedLeave($colleague, '2026-02-10', '2026-02-10');

        $response = $this->actingAs($admin)
            ->post(route('admin.leave-requests.response', $pendingLeave), [
                'response' => 'approved',
            ]);

        $response
            ->assertRedirect(route('admin.leave-requests'))
            ->assertSessionHas('error', 'This leave request would leave the employee\'s department without cover.');

        $this->assertSame('pending', $pendingLeave->refresh()->status);
    }

    private function setCalendarYearRefresh(): void
    {
        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 1,
        ]);
    }

    private function setAprilRefresh(): void
    {
        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 4,
        ]);
    }

    private function createApprovedLeave(User $user, string $startDate, string $endDate): Leave
    {
        $leave = Leave::create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Annual leave',
            'is_half_day' => false,
        ]);

        $leave->forceFill(['status' => 'approved'])->save();

        return $leave;
    }

    private function createPendingLeave(User $user, string $startDate, string $endDate): Leave
    {
        return Leave::create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Annual leave',
            'is_half_day' => false,
        ]);
    }
}
