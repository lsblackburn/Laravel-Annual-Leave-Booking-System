<?php

namespace Tests\Feature;

use App\Models\LeaveSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRefreshSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_february_twenty_ninth_as_leave_refresh_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('admin.view-leave-rules'))
            ->patch(route('admin.leave-refresh.update'), [
                'leave_refresh_day' => 29,
                'leave_refresh_month' => 2,
            ]);

        $response
            ->assertRedirect(route('admin.view-leave-rules'))
            ->assertSessionHasNoErrors();

        $settings = LeaveSetting::first();

        $this->assertSame(29, (int) $settings->leave_refresh_day);
        $this->assertSame(2, (int) $settings->leave_refresh_month);
    }

    public function test_admin_cannot_set_day_beyond_selected_month_length(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('admin.view-leave-rules'))
            ->patch(route('admin.leave-refresh.update'), [
                'leave_refresh_day' => 30,
                'leave_refresh_month' => 2,
            ]);

        $response
            ->assertRedirect(route('admin.view-leave-rules'))
            ->assertSessionHasErrors('leave_refresh_day');

        $settings = LeaveSetting::first();

        $this->assertSame(1, (int) $settings->leave_refresh_day);
        $this->assertSame(1, (int) $settings->leave_refresh_month);
    }
}
