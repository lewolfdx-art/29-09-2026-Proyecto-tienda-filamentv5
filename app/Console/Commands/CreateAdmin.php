<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin
                            {--name= : Nombre del administrador}
                            {--email= : Correo del administrador}';

    protected $description = 'Crea un usuario administrador con el rol super_admin';

    public function handle(): int
    {
        $name = (string) ($this->option('name') ?: $this->ask('Nombre'));
        $email = (string) ($this->option('email') ?: $this->ask('Correo'));
        $password = (string) $this->secret('Contraseña (mínimo 12 caracteres)');

        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->call(SyncPermissions::class);

        $user->assignRole(Role::findByName('super_admin', 'web'));

        $this->info("Administrador creado: {$user->email}");

        return self::SUCCESS;
    }
}
