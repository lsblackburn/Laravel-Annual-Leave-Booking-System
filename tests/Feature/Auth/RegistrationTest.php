<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\User;
use App\Models\UserDepartment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/users/create');

        $response->assertStatus(200);
    }

    public function test_registered_user_controller_create_builds_registration_view(): void
    {
        $department = UserDepartment::create(['department' => 'Accounts']);

        $view = app(RegisteredUserController::class)->create();

        $this->assertSame('auth.register', $view->name());
        $this->assertTrue($view->getData()['departments']->contains($department));
        $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $view->getData()['suggestedColour']);
    }

    public function test_admin_can_create_new_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $department = UserDepartment::create(['department' => 'Finance']);

        $response = $this
            ->actingAs($admin)
            ->post('/admin/users/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'employment_start_date' => now()->subYear()->format('d-m-Y'),
                'department_id' => $department->id,
            ]);

        $createdUser = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertSame($department->id, $createdUser->department_id);
        $this->assertTrue(Hash::check('password', $createdUser->password));
        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.users', absolute: false));
    }

    public function test_admin_can_reuse_email_from_soft_deleted_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deletedUser = User::factory()->create(['email' => 'replacement@example.com']);

        $deletedUser->delete();

        $response = $this
            ->actingAs($admin)
            ->post('/admin/users/register', [
                'name' => 'Replacement User',
                'email' => 'replacement@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'employment_start_date' => now()->subYear()->format('d-m-Y'),
            ]);

        $response->assertRedirect(route('admin.users', absolute: false));

        $this->assertSame(
            1,
            User::where('email', 'replacement@example.com')->count()
        );
        $this->assertStringStartsWith(
            'deleted-user-'.$deletedUser->id.'-',
            User::withTrashed()->find($deletedUser->id)->email
        );
    }

    public function test_admin_can_create_new_user_with_iso_employment_start_date_and_colour(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->post('/admin/users/register', [
                'name' => 'ISO Date User',
                'email' => 'iso-date@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'employment_start_date' => now()->subYear()->format('Y-m-d'),
                'colour' => '#123ABC',
            ]);

        $response->assertRedirect(route('admin.users', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'iso-date@example.com',
            'employment_start_date' => now()->subYear()->format('Y-m-d'),
            'colour' => '#123ABC',
        ]);
    }

    public function test_admin_cannot_create_user_with_future_employment_start_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->post('/admin/users/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'employment_start_date' => now()->addDay()->format('d-m-Y'),
            ]);

        $response->assertSessionHasErrors('employment_start_date');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_non_admin_users_cannot_access_registration_screen(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/admin/users/create');

        $response->assertForbidden();
    }
}
