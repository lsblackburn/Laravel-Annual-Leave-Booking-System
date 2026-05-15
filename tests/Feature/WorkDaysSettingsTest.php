<?php

namespace Tests\Feature;

use App\Models\NonWorkDay;
use App\Models\User;
use App\Models\WorkDay;
use App\Models\LeaveSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkDaysSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_working_days(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $monday = WorkDay::where('day', 'Monday')->firstOrFail();
        $wednesday = WorkDay::where('day', 'Wednesday')->firstOrFail();

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.work-days.update'), [
                'work_days' => [$monday->id, $wednesday->id],
            ]);

        $response->assertRedirect(route('admin.view-leave-rules'));

        $this->assertTrue($monday->refresh()->active);
        $this->assertTrue($wednesday->refresh()->active);
        $this->assertFalse(WorkDay::where('day', 'Tuesday')->firstOrFail()->active);
    }

    public function test_admin_must_select_at_least_one_working_day(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.view-leave-rules'))
            ->patch(route('admin.work-days.update'), [
                'work_days' => [],
            ]);

        $response
            ->assertRedirect(route('admin.view-leave-rules'))
            ->assertSessionHasErrors('work_days');
    }

    public function test_admin_cannot_add_duplicate_non_work_day_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        NonWorkDay::create([
            'name' => 'Existing bank holiday',
            'date' => '2026-05-15',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.view-leave-rules'))
            ->post(route('admin.non-work-days.create'), [
                'name' => 'Duplicate bank holiday',
                'date' => '15-05-2026',
            ]);

        $response
            ->assertRedirect(route('admin.view-leave-rules'))
            ->assertSessionHasErrors('date');

        $this->assertSame(1, NonWorkDay::whereDate('date', '2026-05-15')->count());
    }

    public function test_non_work_days_list_only_shows_future_dates(): void
    {
        $this->travelTo('2026-05-15');

        $admin = User::factory()->create(['role' => 'admin']);

        LeaveSetting::first()->update([
            'leave_refresh_day' => 1,
            'leave_refresh_month' => 4,
        ]);

        NonWorkDay::create([
            'name' => 'Past closure',
            'date' => '2026-05-14',
        ]);

        NonWorkDay::create([
            'name' => 'Today closure',
            'date' => '2026-05-15',
        ]);

        NonWorkDay::create([
            'name' => 'Future closure',
            'date' => '2026-05-16',
        ]);

        NonWorkDay::create([
            'name' => 'Next allowance year closure',
            'date' => '2027-04-01',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.view-leave-rules'));

        $response
            ->assertOk()
            ->assertSee('Today closure')
            ->assertSee('Future closure')
            ->assertSee('Next allowance year closure')
            ->assertDontSee('Past closure');
    }
}
