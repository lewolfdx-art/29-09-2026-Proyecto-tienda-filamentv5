<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissions extends Command
{
    protected $signature = 'app:sync-permissions';

    protected $description = 'Genera los permisos del panel y se los asigna todos al rol super_admin';

    public function handle(): int
    {
        // Solo permisos: las políticas ya están versionadas en app/Policies.
        $this->call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--no-interaction' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate('super_admin', 'web');
        $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

        $this->info("Rol super_admin con {$role->permissions()->count()} permisos.");

        return self::SUCCESS;
    }
}
