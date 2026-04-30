<?php

namespace Tests\Feature;

use App\Models\Leave;
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
