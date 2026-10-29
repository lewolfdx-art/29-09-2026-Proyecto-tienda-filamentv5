<?php

namespace Tests\Feature;

use App\Models\EmailRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmailRecipientTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_the_email_from_the_selected_user(): void
    {
        $user = User::factory()->create(['email' => 'operaciones@example.com']);

        $recipient = EmailRecipient::factory()->create([
            'user_id' => $user->id,
            'email' => 'otro@example.com',
        ]);

        $this->assertSame('operaciones@example.com', $recipient->fresh()->email);
    }

    public function test_creating_and_updating_a_recipient_is_logged(): void
    {
        $recipient = EmailRecipient::factory()->create([
            'tipos' => [EmailRecipient::TYPE_NEW_ORDER],
        ]);

        $created = $this->activityFor($recipient, 'created');

        $this->assertNotNull($created);
        $this->assertSame('email_recipients', $created->log_name);
        $this->assertStringContainsString('destinatario de correo', $created->description);

        $recipient->update(['activo' => false]);

        $updated = $this->activityFor($recipient, 'updated');

        $this->assertNotNull($updated);
        $this->assertTrue($updated->properties['attributes']['activo'] === false);
    }

    public function test_super_admin_and_administrador_can_access_the_resource(): void
    {
        foreach (['super_admin', 'administrador'] as $roleName) {
            $user = $this->userWithRole($roleName, true);

            $this->actingAs($user)
                ->get(route('filament.admin.resources.email-recipients.index'))
                ->assertOk()
                ->assertSee('Destinatarios de correo');
        }
    }

    public function test_vendedor_and_almacen_cannot_access_the_resource(): void
    {
        foreach (['vendedor', 'almacén', 'almacen'] as $roleName) {
            $user = $this->userWithRole($roleName, false);

            $this->actingAs($user)
                ->get(route('filament.admin.resources.email-recipients.index'))
                ->assertForbidden();
        }
    }

    private function userWithRole(string $roleName, bool $withPermissions): User
    {
        $role = Role::findOrCreate($roleName, 'web');

        if ($withPermissions) {
            $permissions = collect([
                'ViewAny:EmailRecipient',
                'View:EmailRecipient',
                'Create:EmailRecipient',
                'Update:EmailRecipient',
                'Delete:EmailRecipient',
                'DeleteAny:EmailRecipient',
            ])->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function activityFor(EmailRecipient $recipient, string $event): ?Activity
    {
        return Activity::query()
            ->where('subject_type', $recipient::class)
            ->where('subject_id', $recipient->getKey())
            ->where('event', $event)
            ->latest('id')
            ->first();
    }
}
