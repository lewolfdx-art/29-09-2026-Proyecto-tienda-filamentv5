<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        return view('store.cart', [
            'lines' => Cart::lines(),
            'total' => Cart::total(),
        ]);
    }

    public function add(Request $request, int $productId)
    {
        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $quantity = (int) ($data['quantity'] ?? 1);
        $product = Product::visibleInStore()->withReserved()->where('products.id', $productId)->firstOrFail();
        $available = $product->available_stock;

        if ($available <= 0) {
            return back()->with('error', "«{$product->name}» está agotado.");
        }

        $newQuantity = Cart::quantityOf($product->id) + $quantity;

        if ($newQuantity > $available) {
            Cart::set($product->id, $available);

            return back()->with('error', "Solo hay {$available} unidades de «{$product->name}». Ajustamos tu carrito.");
        }

        Cart::set($product->id, $newQuantity);

        return back()->with('status', "«{$product->name}» se agregó al carrito.");
    }

    public function update(Request $request, int $productId)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $quantity = (int) $data['quantity'];
        $product = Product::visibleInStore()->withReserved()->where('products.id', $productId)->firstOrFail();
        $available = $product->available_stock;

        if ($quantity > 0 && $available <= 0) {
            Cart::remove($product->id);

            return back()->with('error', "«{$product->name}» se agotó y lo quitamos de tu carrito.");
        }

        if ($quantity > $available) {
            Cart::set($product->id, $available);

            return back()->with('error', "Solo hay {$available} unidades de «{$product->name}». Ajustamos tu carrito.");
        }

        Cart::set($product->id, $quantity);

        return back()->with('status', 'Carrito actualizado.');
    }

    public function remove(int $productId)
    {
        Cart::remove($productId);

        return back()->with('status', 'Producto quitado del carrito.');
    }
}