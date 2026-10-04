@extends('layouts.store')

@use('App\Support\Money')

@section('title', 'Pedido #' . $order->id . ' — ' . config('app.name'))

@section('content')
    <a href="{{ route('orders.index') }}" class="mb-6 inline-block text-sm text-cyan-200 hover:underline">← Mis pedidos</a>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-white">Pedido #{{ $order->id }}</h1>
        <span class="rounded-full border border-cyan-300/30 px-4 py-1 text-sm text-cyan-200">{{ $order->statusLabel() }}</span>
    </div>

    @if ($order->status === 'pending')
        @if ($order->expires_at && $order->expires_at->isPast())
            <div class="mb-6 rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm text-amber-200">
                El plazo de reserva de este pedido venció. Los productos pueden haberse liberado.
            </div>
        @else
            <div class="mb-6 rounded-xl border border-cyan-300/30 bg-cyan-300/10 px-4 py-3 text-sm text-cyan-100">
                Tus productos están reservados
                @if ($order->expires_at)
                    hasta el {{ $order->expires_at->format('d/m/Y H:i') }}.
                @endif
                Te contactaremos con las instrucciones de pago.
            </div>
        @endif
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-3 lg:col-span-2">
            @foreach ($order->items as $item)
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-4">
                    <div class="min-w-0">
                        <p class="font-semibold text-white">{{ $item->product?->name ?? 'Producto' }}</p>
                        <p class="text-sm text-slate-400">{{ $item->quantity }} × {{ Money::format($item->unit_price) }}</p>
                    </div>
                    <p class="shrink-0 font-bold text-cyan-200">{{ Money::format($item->quantity * $item->unit_price) }}</p>
                </div>
            @endforeach

            <div class="flex justify-between rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-4">
                <span class="text-slate-400">Total</span>
                <span class="text-xl font-bold text-cyan-200">{{ Money::format($order->total) }}</span>
            </div>
        </div>

        <aside class="h-fit rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-6 text-sm">
            <h2 class="mb-3 font-semibold text-white">Datos de entrega</h2>
            <p class="text-slate-300">{{ $customer->name }}</p>
            <p class="text-slate-400">{{ $customer->phone }}</p>
            <p class="text-slate-400">{{ $customer->address }}</p>
            <p class="text-slate-400">{{ $customer->city }}</p>

            @if ($order->notes)
                <h2 class="mb-1 mt-4 font-semibold text-white">Notas</h2>
                <p class="text-slate-400">{{ $order->notes }}</p>
            @endif
        </aside>
    </div>
@endsection