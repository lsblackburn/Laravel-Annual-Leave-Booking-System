<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\User;
use App\Notifications\LeaveRequestResponded;
use App\Notifications\LeaveRequestRespondedEmail;
use App\Notifications\LeaveRequestSubmitted;
use App\Notifications\LeaveRequestSubmittedEmail;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_are_notified_when_employee_submits_leave_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee', 'leave_allowance' => 20]);
        $leaveDate = Carbon::now()->addMonth();

        $response = $this
            ->actingAs($employee)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => $leaveDate->format('d-m-Y'),
                'end_date' => $leaveDate->format('d-m-Y'),
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $response->assertRedirect(route('leave.view'));

        $notification = $admin->refresh()->unreadNotifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('New leave request', $notification->data['title']);
        $this->assertStringContainsString($employee->name, $notification->data['body']);
        $this->assertSame(route('admin.leave-requests'), $notification->data['action_url']);
    }

    public function test_employee_is_notified_when_admin_responds_to_leave_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee', 'leave_allowance' => 20]);
        $leaveDate = Carbon::now()->addMonth()->toDateString();
        $leave = Leave::create([
            'user_id' => $employee->id,
            'start_date' => $leaveDate,
            'end_date' => $leaveDate,
            'reason' => 'Annual leave',
            'is_half_day' => false,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.leave-requests.response', $leave), [
                'response' => 'approved',
                'manager_comment' => 'Enjoy your day off.',
            ]);

        $response->assertRedirect(route('admin.leave-requests'));

        $notification = $employee->refresh()->unreadNotifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('Leave request Approved', $notification->data['title']);
        $this->assertStringContainsString('approved', $notification->data['body']);
        $this->assertSame(route('leave.view'), $notification->data['action_url']);
    }

    public function test_opening_notification_marks_it_as_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee', 'leave_allowance' => 20]);
        $leaveDate = Carbon::now()->addMonth();

        $this
            ->actingAs($employee)
            ->from(route('leave.form'))
            ->post(route('leave.create'), [
                'start_date' => $leaveDate->format('d-m-Y'),
                'end_date' => $leaveDate->format('d-m-Y'),
                'is_half_day' => '0',
                'reason' => 'Annual leave',
            ]);

        $notification = $admin->refresh()->unreadNotifications()->firstOrFail();

        $response = $this
            ->actingAs($admin)
            ->patch(route('notifications.open', $notification));

        $response->assertRedirect(route('admin.leave-requests'));
        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_leave_submission_notification_is_written_to_database_and_email_is_queued(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee', 'name' => 'Alex Example']);
        $leave = Leave::create([
            'user_id' => $employee->id,
            'start_date' => Carbon::now()->addMonth()->toDateString(),
            'end_date' => Carbon::now()->addMonth()->toDateString(),
            'reason' => 'Annual leave',
            'is_half_day' => false,
        ]);
        $databaseNotification = new LeaveRequestSubmitted($leave);
        $mailNotification = new LeaveRequestSubmittedEmail($leave);

        $this->assertSame(['database'], $databaseNotification->via($admin));
        $this->assertSame(['mail'], $mailNotification->via($admin));
        $this->assertInstanceOf(ShouldQueue::class, $mailNotification);

        $mail = $mailNotification->toMail($admin);

        $this->assertSame('New leave request', $mail->subject);
        $this->assertStringContainsString('Alex Example requested leave', implode(' ', $mail->introLines));
    }

    public function test_leave_response_notification_is_written_to_database_and_email_is_queued(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $leaveDate = Carbon::now()->addMonth()->toDateString();
        $leave = Leave::create([
            'user_id' => $employee->id,
            'start_date' => $leaveDate,
            'end_date' => $leaveDate,
            'reason' => 'Annual leave',
            'is_half_day' => false,
        ]);
        $leave->forceFill(['status' => 'approved']);
        $databaseNotification = new LeaveRequestResponded($leave);
        $mailNotification = new LeaveRequestRespondedEmail($leave);

        $this->assertSame(['database'], $databaseNotification->via($employee));
        $this->assertSame(['mail'], $mailNotification->via($employee));
        $this->assertInstanceOf(ShouldQueue::class, $mailNotification);

        $mail = $mailNotification->toMail($employee);

        $this->assertSame('Leave request Approved', $mail->subject);
        $this->assertStringContainsString('was approved', implode(' ', $mail->introLines));
    }
}
