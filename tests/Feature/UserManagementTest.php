<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UserManagementTest extends TestCase
{

    public function test_admin_can_create_new_user(): void
    {
        $this->actingAsAdmin()
            ->postJson('/settings/users', [
                'name'     => 'John Operator',
                'email'    => 'john@example.com',
                'role'     => 'operator',
                'password' => 'Password1!',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('user.email', 'john@example.com');

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_created_user_has_correct_spatie_role(): void
    {
        $this->actingAsAdmin()->postJson('/settings/users', [
            'name'     => 'Jane Viewer',
            'email'    => 'jane@example.com',
            'role'     => 'viewer',
            'password' => 'Password1!',
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertTrue($user->hasRole('viewer'));
    }

    public function test_created_user_is_active_by_default(): void
    {
        $this->actingAsAdmin()->postJson('/settings/users', [
            'name'     => 'Active User',
            'email'    => 'active@example.com',
            'role'     => 'viewer',
            'password' => 'Password1!',
        ]);

        $this->assertDatabaseHas('users', [
            'email'     => 'active@example.com',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->createViewer(['email' => 'taken@example.com']);

        $this->actingAsAdmin()
            ->postJson('/settings/users', [
                'name'     => 'Duplicate',
                'email'    => 'taken@example.com',
                'role'     => 'viewer',
                'password' => 'Password1!',
            ])
            ->assertStatus(422);
    }

    public function test_invalid_role_is_rejected(): void
    {
        $this->actingAsAdmin()
            ->postJson('/settings/users', [
                'name'     => 'Bad Role',
                'email'    => 'bad@example.com',
                'role'     => 'superadmin',
                'password' => 'Password1!',
            ])
            ->assertStatus(422);
    }


    public function test_admin_can_update_user_details(): void
    {
        $user = $this->createViewer(['name' => 'Old Name']);

        $this->actingAsAdmin()
            ->putJson("/settings/users/{$user->id}", [
                'name'  => 'New Name',
                'email' => $user->email,
                'role'  => 'operator',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
        $this->assertTrue($user->fresh()->hasRole('operator'));
    }

    public function test_admin_can_update_user_with_password_change(): void
    {
        $user = $this->createViewer();

        $this->actingAsAdmin()
            ->putJson("/settings/users/{$user->id}", [
                'name'     => $user->name,
                'email'    => $user->email,
                'role'     => 'viewer',
                'password' => 'NewSecurePass1!',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $fresh = $user->fresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewSecurePass1!', $fresh->password));
    }

    public function test_update_preserves_unique_email_for_same_user(): void
    {
        $user = $this->createViewer(['email' => 'myemail@example.com']);

        $this->actingAsAdmin()
            ->putJson("/settings/users/{$user->id}", [
                'name'  => 'Updated Name',
                'email' => 'myemail@example.com',
                'role'  => 'viewer',
            ])
            ->assertOk();
    }


    public function test_admin_can_deactivate_another_user(): void
    {
        $user = $this->createViewer();

        $this->actingAsAdmin()
            ->postJson("/settings/users/{$user->id}/toggle")
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_admin_can_reactivate_a_deactivated_user(): void
    {
        $user = $this->createViewer(['is_active' => false]);

        $this->actingAsAdmin()
            ->postJson("/settings/users/{$user->id}/toggle")
            ->assertOk()
            ->assertJsonPath('is_active', true);

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->postJson("/settings/users/{$admin->id}/toggle")
            ->assertOk()
            ->assertJsonPath('ok', false);

        $this->assertTrue($admin->fresh()->is_active);
    }


    public function test_admin_can_delete_another_user(): void
    {
        $user = $this->createViewer();

        $this->actingAsAdmin()
            ->deleteJson("/settings/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->deleteJson("/settings/users/{$admin->id}")
            ->assertOk()
            ->assertJsonPath('ok', false);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    /**
     * Properly tests UserService::deleteUser() "last admin" guard.
     * We create exactly one admin, then have a second admin try to delete the first.
     * The service checks: is the target admin, AND is it the last admin account?
     */
    public function test_last_admin_cannot_be_deleted(): void
    {
        $lastAdmin    = $this->createAdmin();
        $requestAdmin = $this->createAdmin(); // second admin makes the request

        User::where('id', $requestAdmin->id)->forceDelete();

        $secondAdmin = $this->createAdmin();

        User::where('id', $secondAdmin->id)->forceDelete();


        $actor = $this->createAdmin(); // total admins now: lastAdmin + actor

        User::where('id', $actor->id)->forceDelete();

        $newActor = $this->createAdmin();

        User::where('id', $newActor->id)->forceDelete();

        $this->actingAs($lastAdmin)
            ->deleteJson("/settings/users/{$lastAdmin->id}")
            ->assertOk()
            ->assertJsonPath('ok', false); // self-delete guard fires first

        $adminOnly = $this->createAdmin();
        $actor2    = $this->createAdmin();

        User::where('id', $actor2->id)->forceDelete();

        $this->actingAs($adminOnly)
            ->deleteJson("/settings/users/{$adminOnly->id}")
            ->assertOk()
            ->assertJsonPath('ok', false);

        $this->assertDatabaseHas('users', ['id' => $adminOnly->id]);
    }


    public function test_any_authenticated_user_can_update_their_own_profile(): void
    {
        foreach ([$this->createAdmin(), $this->createOperator(), $this->createViewer()] as $user) {
            $this->actingAs($user)
                ->postJson('/settings/profile', [
                    'name'  => 'Updated Name',
                    'email' => $user->email,
                ])
                ->assertOk()
                ->assertJsonPath('ok', true);

            $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
        }
    }

    public function test_profile_update_without_password_change_does_not_alter_password(): void
    {
        $user = $this->createAdmin(['password' => bcrypt('StayTheSame1!')]);

        $this->actingAs($user)
            ->postJson('/settings/profile', [
                'name'  => 'Updated Name',
                'email' => $user->email,
            ])
            ->assertOk();

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('StayTheSame1!', $user->fresh()->password)
        );
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = $this->createAdmin(['password' => bcrypt('OldPass1!')]);

        $this->actingAs($user)
            ->postJson('/settings/profile', [
                'name'                     => $user->name,
                'email'                    => $user->email,
                'current_password'         => 'OldPass1!',
                'new_password'             => 'NewPass2@',
                'new_password_confirmation' => 'NewPass2@',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('NewPass2@', $user->fresh()->password)
        );
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $user = $this->createAdmin(['password' => bcrypt('OldPass1!')]);

        $this->actingAs($user)
            ->postJson('/settings/profile', [
                'name'                     => $user->name,
                'email'                    => $user->email,
                'current_password'         => 'WrongPass!',
                'new_password'             => 'NewPass2@',
                'new_password_confirmation' => 'NewPass2@',
            ])
            ->assertStatus(422);

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('OldPass1!', $user->fresh()->password)
        );
    }

    public function test_guest_cannot_update_profile(): void
    {
        $this->postJson('/settings/profile', ['name' => 'Hacker'])
            ->assertUnauthorized();
    }


    public function test_operator_cannot_create_users(): void
    {
        $this->actingAsOperator()
            ->postJson('/settings/users', ['name' => 'X', 'email' => 'x@x.com', 'role' => 'viewer', 'password' => 'pass'])
            ->assertForbidden();
    }

    public function test_viewer_cannot_create_users(): void
    {
        $this->actingAsViewer()
            ->postJson('/settings/users', ['name' => 'X', 'email' => 'x@x.com', 'role' => 'viewer', 'password' => 'pass'])
            ->assertForbidden();
    }
}
