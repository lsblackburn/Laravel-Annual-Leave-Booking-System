<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\LeaveSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestAllowanceTest extends TestCase
{
    use RefreshDatabase;

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
}
