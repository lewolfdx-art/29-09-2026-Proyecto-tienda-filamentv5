<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->withCount('items')
            ->latest()
            ->simplePaginate(10);

        return view('store.orders.index', compact('orders'));
    }

    public function show(int $order)
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->with('items.product')
            ->findOrFail($order);

        return view('store.orders.show', [
            'order' => $order,
            'customer' => $customer,
        ]);
    }
}