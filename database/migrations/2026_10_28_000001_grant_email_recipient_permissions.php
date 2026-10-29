<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private const PERMISSIONS = [
        'ViewAny:EmailRecipient',
        'View:EmailRecipient',
        'Create:EmailRecipient',
        'Update:EmailRecipient',
        'Delete:EmailRecipient',
        'DeleteAny:EmailRecipient',
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['super_admin', 'administrador'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo(self::PERMISSIONS));
    }

    public function down(): void
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->get();

        foreach ($permissions as $permission) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }
};
