# Tienda

Tienda online en Laravel 12 con panel de administración en Filament 5. Pensada para Perú:
pagos manuales por Yape/Plin con comprobante, precios en soles y teléfonos +51.

## Qué incluye

- **Tienda pública** (`/`): catálogo con búsqueda, carrito en sesión, registro e inicio de
  sesión de clientes, recuperación de contraseña, checkout, "Mis pedidos" y subida del
  comprobante de pago.
- **Panel** (`/admin`): productos (con galería), categorías, clientes, pedidos, usuarios y
  roles (Filament Shield), registro de auditoría, destinatarios de avisos internos y un
  dashboard con métricas. Exportación a Excel.
- **Stock con reservas**: un pedido pendiente reserva las unidades 24 h; al pagarse se
  descuentan del stock físico. `orders:expire` libera cada 5 minutos las reservas vencidas.
- **Datos de clientes cifrados**, con índice ciego (HMAC) para poder buscarlos.
- **Backups diarios** (spatie/laravel-backup) y un zip cifrado de la base de datos por correo.

Un mismo formulario (`/ingresar`) sirve a los dos tipos de cuenta: el equipo (`users`, guard
`web`) va al panel y los clientes (`customers`, guard `customer`) a la tienda.

## Desarrollo local

```bash
composer setup        # instala dependencias, crea .env, migra y compila assets
php artisan app:create-admin
composer dev          # servidor, cola, logs y Vite a la vez
```

Por defecto usa SQLite. Para cargar productos de ejemplo: `php artisan db:seed`.

### Calidad

```bash
composer test                       # PHPUnit (SQLite en memoria)
vendor/bin/phpstan analyse          # Larastan
vendor/bin/pint                     # formato
```

El test del backup por correo necesita el binario `sqlite3` en el PATH; si no está, se omite.

## Producción (Hostinger)

1. **Código**: clona el repositorio en el servidor. Lo ideal es que el dominio apunte a
   `public/`. Si apunta a la raíz del proyecto (`public_html`), el `.htaccess` de la raíz
   redirige todo a `public/` para que `.env`, `vendor/` y `storage/` no queden expuestos.
2. **Entorno**: copia [`deploy/env.production.example`](deploy/env.production.example) como
   `.env`, completa los valores `CAMBIAR` y ejecuta `php artisan key:generate` **una sola vez**.
   Guarda `APP_KEY` y `BACKUP_ARCHIVE_PASSWORD` fuera del servidor: sin la primera no se pueden
   leer los datos de los clientes y sin la segunda no se pueden abrir los backups.
3. **Despliegue**: `bash deploy/hostinger.sh`. Pone la app en mantenimiento, hace `git pull`,
   instala dependencias, migra, sincroniza permisos, compila assets, cachea la configuración y
   ejecuta `app:production-check`. Si algo falla, la app queda en mantenimiento.
4. **Primer administrador**: `php artisan app:create-admin`.
5. **Cron**: pega los dos trabajos de [`deploy/hostinger-cron.txt`](deploy/hostinger-cron.txt)
   en hPanel (scheduler y cola, cada minuto). Sin ellos no expiran reservas, no salen correos
   ni se hacen backups.
6. **Comprobación**: `php artisan app:production-check` debe salir todo en OK. Después, haz una
   compra de prueba completa y ejecuta `php artisan app:send-backup-emails` para confirmar que
   llega el backup.

### Comandos propios

| Comando | Para qué |
| --- | --- |
| `app:create-admin` | Crea un usuario con el rol `super_admin`. |
| `app:sync-permissions` | Genera los permisos del panel y se los da todos a `super_admin`. Se ejecuta en cada despliegue. |
| `app:production-check` | Verifica la configuración de producción. |
| `orders:expire` | Libera las reservas de pedidos no pagados a tiempo (cada 5 min). |
| `app:send-backup-emails` | Envía el zip cifrado de la base de datos (diario, 03:15). |
