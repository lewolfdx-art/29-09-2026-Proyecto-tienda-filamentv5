<?php

use App\Http\Controllers\Admin\BackupEmailDownloadController;
use App\Http\Controllers\Store\AuthController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CatalogController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\OrderController;
use App\Http\Controllers\Store\PasswordResetController;
use App\Http\Controllers\Store\PaymentProofController;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('store.index');
Route::get('/producto/{slug}', [CatalogController::class, 'show'])->name('store.show');

Route::get('/admin/backup-emails/{file}', BackupEmailDownloadController::class)
    ->middleware(['signed', FilamentAuthenticate::class])
    ->name('admin.backup-email.download');

Route::prefix('carrito')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/agregar/{productId}', [CartController::class, 'add'])->middleware('throttle:cart-write')->name('add');
    Route::patch('/{productId}', [CartController::class, 'update'])->middleware('throttle:cart-write')->name('update');
    Route::delete('/{productId}', [CartController::class, 'remove'])->middleware('throttle:cart-write')->name('remove');
});

Route::middleware('guest:customer')->group(function () {
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/ingresar', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::get('/recuperar', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/recuperar', [PasswordResetController::class, 'email'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/restablecer/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/restablecer', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

Route::middleware('auth:customer')->group(function () {
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');

    Route::get('/mis-pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/mis-pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/mis-pedidos/{order}/comprobante', [PaymentProofController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('orders.payment-proof.store');
});
