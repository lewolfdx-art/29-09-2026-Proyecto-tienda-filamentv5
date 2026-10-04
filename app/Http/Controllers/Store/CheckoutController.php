<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
class CheckoutController extends Controller
{
    /** Horas que se reservan las unidades de un pedido sin pagar. */
    private const HOLD_HOURS = 24;

    public function show()
    {
        $lines = Cart::lines();

        if ($lines->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Tu carrito está vacío.');
        }

        if ($lines->contains(fn ($line) => ! $line['in_stock'])) {
            return redirect()->route('cart.index')->with('error', 'Ajusta las cantidades de tu carrito antes de continuar.');
        }

        return view('store.checkout', [
            'lines' => $lines,
            'total' => Cart::total(),
            'customer' => Auth::guard('customer')->user(),
        ]);
    }

    public function store(Request $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'phone.required' => 'Escribe tu teléfono.',
            'phone.regex' => 'Escribe un teléfono válido (solo números, espacios, + y guiones).',
            'address.required' => 'Escribe tu dirección.',
            'city.required' => 'Escribe tu ciudad.',
        ]);

        $items = Cart::items();

        if (empty($items)) {
            return redirect()->route('cart.index')->with('error', 'Tu carrito está vacío.');
        }

        $result = DB::transaction(function () use ($customer, $data, $items) {
            $ids = array_keys($items);

            // 1) Lectura con bloqueo (sin subconsultas) y en orden fijo. Quien compre el
            //    mismo producto a la vez espera aquí hasta que esta compra termine.
            Product::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();

            // 2) Con el bloqueo obtenido: productos vendibles y su disponibilidad real.
            $products = Product::visibleInStore()->whereIn('products.id', $ids)->get()->keyBy('id');

            $problems = [];

            foreach ($items as $id => $quantity) {
                $product = $products->get($id);

                if (! $product) {
                    $problems[] = 'Uno de los productos de tu carrito ya no está disponible.';

                    continue;
                }

                $available = $product->available_stock;

                if ($quantity > $available) {
                    $problems[] = $available > 0
                        ? "«{$product->name}»: solo quedan {$available} unidades."
                        : "«{$product->name}» se agotó.";
                }
            }

            if ($problems) {
                return ['problems' => $problems];
            }

            $customer->update([
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'],
            ]);

            $order = Order::create([
                'customer_id' => $customer->id,
                'status' => 'pending',
                'total' => 0,
                'notes' => $data['notes'] ?? null,
                'expires_at' => now()->addHours(self::HOLD_HOURS),
            ]);

            foreach ($items as $id => $quantity) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $id,
                    'quantity' => $quantity,
                    'unit_price' => $products[$id]->price,
                ]);
            }

            return ['order' => $order->refresh()];
        });

        if (isset($result['problems'])) {
            return redirect()->route('cart.index')->with('error', implode(' ', $result['problems']));
        }

        Cart::clear();

        return redirect()
            ->route('orders.show', $result['order']->id)
            ->with('status', "¡Pedido #{$result['order']->id} recibido! Tus productos quedan reservados.");
    }
}