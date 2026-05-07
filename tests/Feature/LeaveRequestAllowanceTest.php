<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\LeaveSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestAllowanceTest extends TestCase
{
    use RefreshDatabase;

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

    private function setCalendarYearRefresh(): void
    {
        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 1,
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
