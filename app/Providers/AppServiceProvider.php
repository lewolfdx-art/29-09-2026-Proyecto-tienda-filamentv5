<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use App\Policies\RolePolicy;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);

        // Al cerrar sesión en el panel, el equipo vuelve a la tienda.
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }
}