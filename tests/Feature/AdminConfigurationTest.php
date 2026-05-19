<?php

namespace Tests\Feature;

use App\Models\NonWorkDay;
use App\Models\LeaveSetting;
use App\Models\User;
use App\Models\UserDepartment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_configuration_pages_are_rendered_for_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee', 'name' => 'Alice Employee']);

        $this->actingAs($admin)
            ->get(route('admin.view-config'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.leave-requests'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Alice Employee');

        $this->actingAs($admin)
            ->get(route('admin.view-leave-rules'))
            ->assertOk()
            ->assertSee('Leave');

        $this->actingAs($admin)
            ->get(route('admin.view-company-departments'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $employee))
            ->assertOk();
    }

    public function test_admin_cannot_edit_themselves_from_admin_user_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $admin))
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('error', 'You cannot edit yourself in the Admin panel.');
    }

    public function test_employee_cannot_access_admin_configuration_pages(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->get(route('admin.view-config'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('admin.view-leave-rules'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('admin.view-company-departments'))
            ->assertForbidden();
    }

    public function test_leave_rules_page_falls_back_to_today_when_refresh_settings_are_missing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        $admin = User::factory()->create(['role' => 'admin']);
        LeaveSetting::query()->delete();

        NonWorkDay::create([
            'name' => 'Past Closure',
            'date' => '2026-05-19',
        ]);
        NonWorkDay::create([
            'name' => 'Future Closure',
            'date' => '2026-05-21',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.view-leave-rules'));

        $response->assertOk();
        $response->assertDontSee('Past Closure');
        $response->assertSee('Future Closure');

        Carbon::setTestNow();
    }

    public function test_admin_can_create_and_delete_company_department(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.company-departments.create'), [
                'department' => 'Design',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.view-company-departments'));

        $department = UserDepartment::where('department', 'Design')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.company-departments.delete', $department))
            ->assertRedirect(route('admin.view-company-departments'));

        $this->assertDatabaseMissing('user_departments', [
            'id' => $department->id,
        ]);
    }

    public function test_admin_can_delete_non_work_day(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $nonWorkDay = NonWorkDay::create([
            'name' => 'Company closure',
            'date' => '2026-12-24',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.non-work-days.delete', $nonWorkDay))
            ->assertRedirect(route('admin.view-leave-rules'));

        $this->assertDatabaseMissing('non_work_days', [
            'id' => $nonWorkDay->id,
        ]);
    }

    public function test_admin_can_promote_and_demote_another_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($admin)
            ->post(route('admin.users.promote', $employee))
            ->assertRedirect(route('admin.users'));

        $this->assertSame('admin', $employee->refresh()->role);

        $this->actingAs($admin)
            ->post(route('admin.users.demote', $employee))
            ->assertRedirect(route('admin.users'));

        $this->assertSame('employee', $employee->refresh()->role);
    }

    public function test_admin_cannot_promote_or_demote_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.users.promote', $admin))
            ->assertRedirect(route('admin.users'));

        $this->assertSame('admin', $admin->refresh()->role);

        $this->actingAs($admin)
            ->post(route('admin.users.demote', $admin))
            ->assertRedirect(route('admin.users'));

        $this->assertSame('admin', $admin->refresh()->role);
    }

    public function test_admin_cannot_update_their_own_profile_from_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $admin), [
                'name' => 'Changed Admin',
                'email' => 'changed@example.com',
                'employment_start_date' => '',
            ])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('error', 'You cannot update your own profile in the Admin panel.');

        $this->assertNotSame('Changed Admin', $admin->refresh()->name);
    }
}
