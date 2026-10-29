<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Policies\RolePolicy;
use App\Support\InternalNotifier;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\CleanupHasFailed;
use Spatie\Backup\Events\UnhealthyBackupWasFound;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Detrás de HTTPS todas las URLs generadas deben usar el esquema seguro.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Product::observe(ProductObserver::class);

        Gate::policy(Role::class, RolePolicy::class);

        // Al cerrar sesión en el panel, el equipo vuelve a la tienda.
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);

        RateLimiter::for('cart-write', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->user('customer')?->id ?: $request->ip()));

        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(10)
            ->by($request->user('customer')?->id ?: $request->ip()));

        Event::listen(BackupHasFailed::class, function (BackupHasFailed $event): void {
            self::backupFailure('backup', $event->exception->getMessage());
        });

        Event::listen(CleanupHasFailed::class, function (CleanupHasFailed $event): void {
            self::backupFailure('limpieza', $event->exception->getMessage());
        });

        Event::listen(UnhealthyBackupWasFound::class, function (UnhealthyBackupWasFound $event): void {
            $failure = $event->backupDestinationStatus->getHealthCheckFailure();

            self::backupFailure(
                'monitoreo',
                $failure?->exception()->getMessage() ?? 'El backup no pasó las comprobaciones de salud.',
            );
        });
    }

    private static function backupFailure(string $context, string $message): void
    {
        Log::error('Fallo de backup detectado.', [
            'contexto' => $context,
            'mensaje' => $message,
        ]);

        InternalNotifier::backupFailed("Contexto: {$context}\nDetalle: {$message}");
    }
}
