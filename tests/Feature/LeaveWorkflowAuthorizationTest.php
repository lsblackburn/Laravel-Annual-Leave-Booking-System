<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveController;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LeaveWorkflowAuthorizationTest extends TestCase
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

    public function test_user_can_view_leave_form_and_leave_list(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($user)
            ->get(route('leave.form'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('leave.view'))
            ->assertOk();
    }

    public function test_user_cannot_edit_another_users_leave_request(): void
    {
        $owner = User::factory()->create(['role' => 'employee']);
        $otherUser = User::factory()->create(['role' => 'employee']);
        $leave = $this->leaveFor($owner);

        $this->actingAs($otherUser)
            ->get(route('leave.edit', $leave))
            ->assertForbidden();
    }

    public function test_user_can_edit_their_pending_leave_request(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'leave_allowance' => 20]);
        $leave = $this->leaveFor($user);

        $this->actingAs($user)
            ->get(route('leave.edit', $leave))
            ->assertOk();

        $this->actingAs($user)
            ->put(route('leave.update', $leave), [
                'start_date' => '03-02-2026',
                'end_date' => '03-02-2026',
                'is_half_day' => '0',
                'reason' => 'Updated leave',
            ])
            ->assertRedirect(route('leave.view'));

        $leave->refresh();

        $this->assertSame('2026-02-03', $leave->start_date);
        $this->assertSame('Updated leave', $leave->reason);
    }

    public function test_user_cannot_update_another_users_leave_request(): void
    {
        $owner = User::factory()->create(['role' => 'employee']);
        $otherUser = User::factory()->create(['role' => 'employee']);
        $leave = $this->leaveFor($owner);

        $this->actingAs($otherUser)
            ->put(route('leave.update', $leave), [
                'start_date' => '03-02-2026',
                'end_date' => '03-02-2026',
                'is_half_day' => '0',
                'reason' => 'Updated leave',
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_edit_or_delete_processed_leave_request(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $leave = $this->leaveFor($user, ['status' => 'approved']);

        $this->actingAs($user)
            ->get(route('leave.edit', $leave))
            ->assertRedirect(route('leave.view'));

        $this->actingAs($user)
            ->delete(route('leave.delete', $leave))
            ->assertRedirect(route('leave.view'));

        $this->assertNotNull($leave->fresh());
    }

    public function test_user_cannot_update_processed_leave_request(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $leave = $this->leaveFor($user, ['status' => 'approved']);

        $this->actingAs($user)
            ->put(route('leave.update', $leave), [
                'start_date' => '03-02-2026',
                'end_date' => '03-02-2026',
                'is_half_day' => '0',
                'reason' => 'Updated leave',
            ])
            ->assertRedirect(route('leave.view'))
            ->assertSessionHas('error', 'Only pending leave requests can be modified.');

        $this->assertSame('Annual leave', $leave->refresh()->reason);
    }

    public function test_user_cannot_delete_another_users_leave_request(): void
    {
        $owner = User::factory()->create(['role' => 'employee']);
        $otherUser = User::factory()->create(['role' => 'employee']);
        $leave = $this->leaveFor($owner);

        $this->actingAs($otherUser)
            ->delete(route('leave.delete', $leave))
            ->assertForbidden();

        $this->assertNotNull($leave->fresh());
    }

    public function test_half_day_create_requires_matching_start_and_end_dates(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'leave_allowance' => 20]);

        $this->actingAs($user)
            ->post(route('leave.create'), [
                'start_date' => '02-02-2026',
                'end_date' => '03-02-2026',
                'is_half_day' => '1',
                'reason' => 'Half day',
            ])
            ->assertRedirect(route('leave.view'))
            ->assertSessionHas('error', 'A half day must have the start date equal to the end date');

        $this->assertDatabaseMissing('leaves', [
            'user_id' => $user->id,
            'reason' => 'Half day',
        ]);
    }

    public function test_invalid_start_date_format_returns_validation_error_without_crashing(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'leave_allowance' => 20]);

        $this->actingAs($user)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => 'not-a-date',
                'end_date' => '03-02-2026',
                'reason' => 'Invalid date',
            ])
            ->assertRedirect(route('leave.form'))
            ->assertSessionHasErrors('start_date');
    }

    public function test_half_day_update_requires_matching_start_and_end_dates(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'leave_allowance' => 20]);
        $leave = $this->leaveFor($user);

        $this->actingAs($user)
            ->put(route('leave.update', $leave), [
                'start_date' => '02-02-2026',
                'end_date' => '03-02-2026',
                'is_half_day' => '1',
                'reason' => 'Half day',
            ])
            ->assertRedirect(route('leave.view'))
            ->assertSessionHas('error', 'A half day must have the start date equal to the end date');

        $this->assertSame(0, $leave->refresh()->is_half_day);
    }

    public function test_admin_response_rejects_invalid_response_value(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);
        $leave = $this->leaveFor($employee);

        $this->actingAs($admin)
            ->post(route('admin.leave-requests.response', $leave), [
                'response' => 'maybe',
            ])
            ->assertRedirect(route('admin.leave-requests'));

        $this->assertSame('pending', $leave->refresh()->status);
    }

    public function test_admin_cannot_respond_to_already_processed_leave_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);
        $leave = $this->leaveFor($employee, ['status' => 'approved']);

        $this->actingAs($admin)
            ->post(route('admin.leave-requests.response', $leave), [
                'response' => 'rejected',
                'manager_comment' => 'Changing decision',
            ])
            ->assertRedirect(route('admin.leave-requests'));

        $this->assertSame('approved', $leave->refresh()->status);
        $this->assertNull($leave->manager_comment);
    }

    public function test_controller_admin_guards_reject_non_admin_users_when_called_directly(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $controller = app(LeaveController::class);

        $this->actingAs($employee);

        foreach ([
            fn () => $controller->leave_response(new Request(['response' => 'approved']), 1),
            fn () => $controller->update_leave_refresh(new Request()),
            fn () => $controller->update_leave_allowance(new Request(), \App\Models\LeaveSetting::first()),
            fn () => $controller->update_work_days(new Request()),
        ] as $action) {
            try {
                $action();
                $this->fail('Expected the controller guard to abort.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    private function leaveFor(User $user, array $overrides = []): Leave
    {
        $status = $overrides['status'] ?? 'pending';
        unset($overrides['status']);

        $leave = Leave::create(array_merge([
            'user_id' => $user->id,
            'start_date' => '2026-02-02',
            'end_date' => '2026-02-02',
            'reason' => 'Annual leave',
            'is_half_day' => false,
        ], $overrides));

        $leave->forceFill(['status' => $status])->save();

        return $leave;
    }
}
