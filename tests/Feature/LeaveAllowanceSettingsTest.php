<?php

namespace Tests\Feature;

use App\Models\LeaveSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveAllowanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_numeric_allowance_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('admin.view-leave-rules'))
            ->patch(route('admin.leave-allowance.update'), [
                'base_allowance' => 20,
                'increase_after_years' => 2,
                'increase_by_days' => 1,
                'maximum_allowance' => 30,
            ]);

        $response
            ->assertRedirect(route('admin.view-leave-rules'))
            ->assertSessionHasNoErrors();

        $settings = LeaveSetting::first();

        $this->assertSame(20.0, (float) $settings->base_allowance);
        $this->assertSame(2, (int) $settings->increase_after_years);
        $this->assertSame(1.0, (float) $settings->increase_by_days);
        $this->assertSame(30.0, (float) $settings->maximum_allowance);
    }

    public function test_admin_cannot_set_maximum_allowance_below_base_allowance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('admin.view-leave-rules'))
            ->patch(route('admin.leave-allowance.update'), [
                'base_allowance' => 20,
                'increase_after_years' => 2,
                'increase_by_days' => 1,
                'maximum_allowance' => 19,
            ]);

        $response
            ->assertRedirect(route('admin.view-leave-rules'))
            ->assertSessionHasErrors('maximum_allowance');

        $this->assertSame(30.0, (float) LeaveSetting::first()->maximum_allowance);
    }
}
