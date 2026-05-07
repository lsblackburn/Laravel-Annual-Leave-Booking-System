<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_employee_profile_update_preserves_hidden_employment_start_date(): void
    {
        $user = User::factory()->create([
            'employment_start_date' => '2024-05-01',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('2024-05-01', $user->employment_start_date);
    }

    public function test_employee_cannot_update_their_own_employment_start_date(): void
    {
        $user = User::factory()->create([
            'employment_start_date' => '2024-05-01',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'employment_start_date' => '01-05-2020',
            ]);

        $response->assertSessionHasErrors('employment_start_date');

        $user->refresh();

        $this->assertSame('2024-05-01', $user->employment_start_date);
    }

    public function test_profile_information_cannot_be_updated_with_future_employment_start_date(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'employment_start_date' => now()->addDay()->format('d-m-Y'),
            ]);

        $response->assertSessionHasErrors('employment_start_date');
    }

    public function test_admin_edit_page_does_not_default_missing_employment_start_date_to_today(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['employment_start_date' => null]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.users.edit', $user));

        $response
            ->assertOk()
            ->assertDontSee('value="'.now()->format('d-m-Y').'"', false);
    }

    public function test_admin_can_update_user_with_missing_employment_start_date_without_backfilling_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['employment_start_date' => null]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Updated User',
                'email' => 'updated@example.com',
                'employment_start_date' => '',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users'));

        $user->refresh();

        $this->assertSame('Updated User', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertNull($user->employment_start_date);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
