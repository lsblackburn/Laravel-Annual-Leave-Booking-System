<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\NonWorkDay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveCalendarEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_events_are_filtered_to_the_visible_date_range(): void
    {
        $viewer = User::factory()->create();
        $employee = User::factory()->create(['name' => 'Alex Example']);

        $this->createLeave($employee, '2026-03-20', '2026-03-25');
        $this->createLeave($employee, '2026-03-28', '2026-04-02');
        $this->createLeave($employee, '2026-04-10', '2026-04-12');
        $this->createLeave($employee, '2026-04-30', '2026-05-05');
        $this->createLeave($employee, '2026-04-15', '2026-04-16', 'rejected');

        $response = $this->actingAs($viewer)->getJson(route('leave-requests.calendar-events', [
            'start' => '2026-04-01T00:00:00+01:00',
            'end' => '2026-04-30T00:00:00+01:00',
        ]));

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment([
            'start' => '2026-03-28',
            'end' => '2026-04-03',
        ]);
        $response->assertJsonFragment([
            'start' => '2026-04-10',
            'end' => '2026-04-13',
        ]);
        $response->assertJsonMissing(['start' => '2026-03-20']);
        $response->assertJsonMissing(['start' => '2026-04-30']);
        $response->assertJsonMissing(['start' => '2026-04-15']);
    }

    public function test_calendar_events_reject_invalid_range_dates(): void
    {
        $viewer = User::factory()->create();

        $response = $this->actingAs($viewer)->getJson(route('leave-requests.calendar-events', [
            'start' => 'not-a-date',
            'end' => '2026-04-30T00:00:00+01:00',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['start']);
    }

    public function test_calendar_events_preserve_client_date_boundaries_with_timezone_offsets(): void
    {
        $viewer = User::factory()->create();
        $employee = User::factory()->create(['name' => 'Alex Example']);

        $this->createLeave($employee, '2026-03-31', '2026-03-31');
        $this->createLeave($employee, '2026-04-01', '2026-04-01');
        $this->createLeave($employee, '2026-04-30', '2026-04-30');

        $response = $this->actingAs($viewer)->getJson(route('leave-requests.calendar-events', [
            'start' => '2026-04-01T00:00:00+01:00',
            'end' => '2026-04-30T00:00:00+01:00',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['start' => '2026-04-01']);
        $response->assertJsonMissing(['start' => '2026-03-31']);
        $response->assertJsonMissing(['start' => '2026-04-30']);
    }

    public function test_calendar_events_include_non_work_days_in_visible_range(): void
    {
        $viewer = User::factory()->create();

        NonWorkDay::create([
            'name' => 'Early May Bank Holiday',
            'date' => '2026-05-04',
        ]);

        NonWorkDay::create([
            'name' => 'Outside Range Closure',
            'date' => '2026-06-01',
        ]);

        $response = $this->actingAs($viewer)->getJson(route('leave-requests.calendar-events', [
            'start' => '2026-05-01T00:00:00+01:00',
            'end' => '2026-06-01T00:00:00+01:00',
        ]));

        $response->assertOk();
        $response->assertJsonFragment([
            'title' => 'Early May Bank Holiday - Non-work day',
            'start' => '2026-05-04',
            'end' => '2026-05-05',
            'allDay' => true,
        ]);
        $response->assertJsonFragment([
            'type' => 'non_work_day',
        ]);
        $response->assertJsonMissing([
            'title' => 'Outside Range Closure - Non-work day',
        ]);
    }

    public function test_calendar_events_can_be_requested_without_date_filters(): void
    {
        $viewer = User::factory()->create();
        $employee = User::factory()->create(['name' => 'Alex Example']);

        $this->createLeave($employee, '2026-04-10', '2026-04-10');

        NonWorkDay::create([
            'name' => 'Company Closure',
            'date' => '2026-04-13',
        ]);

        $response = $this->actingAs($viewer)->getJson(route('leave-requests.calendar-events'));

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['start' => '2026-04-10']);
        $response->assertJsonFragment(['start' => '2026-04-13']);
    }

    public function test_calendar_events_include_leave_for_soft_deleted_users(): void
    {
        $viewer = User::factory()->create();
        $employee = User::factory()->create([
            'name' => 'Former Employee',
            'colour' => '#123456',
        ]);

        $this->createLeave($employee, '2026-04-10', '2026-04-10');
        $employee->delete();

        $response = $this->actingAs($viewer)->getJson(route('leave-requests.calendar-events'));

        $response->assertOk();
        $response->assertJsonFragment([
            'title' => 'Former Employee - Annual Leave',
            'start' => '2026-04-10',
            'backgroundColor' => '#123456',
        ]);
    }

    private function createLeave(User $user, string $startDate, string $endDate, string $status = 'approved'): Leave
    {
        $leave = Leave::create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Annual leave',
            'is_half_day' => false,
        ]);

        $leave->status = $status;
        $leave->save();

        return $leave;
    }
}
