<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SyncPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_gives_super_admin_the_permissions_of_every_panel_resource(): void
    {
        $this->artisan('app:sync-permissions')->assertSuccessful();

        $role = Role::findByName('super_admin', 'web');

        foreach (['Product', 'Order', 'Customer', 'Category', 'User', 'EmailRecipient'] as $model) {
            $this->assertTrue($role->hasPermissionTo("ViewAny:{$model}"), "Falta ViewAny:{$model}");
        }

        $admin = User::factory()->create();
        $admin->assignRole($role);

        $this->assertTrue($admin->can('ViewAny:Order'));
    }

    public function test_running_it_twice_does_not_duplicate_permissions(): void
    {
        $this->artisan('app:sync-permissions')->assertSuccessful();
        $count = Role::findByName('super_admin', 'web')->permissions()->count();

        $this->artisan('app:sync-permissions')->assertSuccessful();

        $this->assertSame($count, Role::findByName('super_admin', 'web')->permissions()->count());
    }
}
