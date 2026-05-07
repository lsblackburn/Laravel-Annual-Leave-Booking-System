<?php

namespace Tests\Feature\Auth;

use App\Models\User;
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

    public function test_admin_can_create_new_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->post('/admin/users/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'employment_start_date' => now()->subYear()->format('d-m-Y'),
            ]);

        $createdUser = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertTrue(Hash::check('password', $createdUser->password));
        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.users', absolute: false));
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
