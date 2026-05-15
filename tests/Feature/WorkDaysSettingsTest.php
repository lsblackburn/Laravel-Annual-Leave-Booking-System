<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkDays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkDaysSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_working_days(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $monday = WorkDays::where('day', 'Monday')->firstOrFail();
        $wednesday = WorkDays::where('day', 'Wednesday')->firstOrFail();

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.work-days.update'), [
                'work_days' => [$monday->id, $wednesday->id],
            ]);

        $response->assertRedirect(route('admin.view-leave-rules'));

        $this->assertTrue($monday->refresh()->active);
        $this->assertTrue($wednesday->refresh()->active);
        $this->assertFalse(WorkDays::where('day', 'Tuesday')->firstOrFail()->active);
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
}
