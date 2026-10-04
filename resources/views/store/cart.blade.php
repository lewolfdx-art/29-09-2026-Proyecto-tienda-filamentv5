@extends('layouts.store')

@use('App\Support\Money')

@section('title', 'Carrito — ' . config('app.name'))

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-white">Tu carrito</h1>

    @if ($lines->isEmpty())
        <div class="rounded-2xl border border-cyan-300/10 bg-slate-900/60 p-12 text-center text-slate-400">
            <p class="mb-4">Tu carrito está vacío.</p>
            <a href="{{ route('store.index') }}" class="text-cyan-200 hover:underline">Ir al catálogo</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($lines as $line)
                @php($product = $line['product'])
                <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-4">
                    <a href="{{ route('store.show', $product->slug) }}" class="shrink-0">
                        @if ($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                 class="h-20 w-20 rounded-xl object-cover">
                        @else
                            <div class="flex h-20 w-20 items-center justify-center rounded-xl bg-white/5 text-3xl">📦</div>
                        @endif
                    </a>

                    <div class="min-w-0 flex-1">
                        <a href="{{ route('store.show', $product->slug) }}"
                           class="font-semibold text-white hover:text-cyan-200">{{ $product->name }}</a>
                        <p class="text-sm text-slate-400">{{ Money::format($product->price) }} c/u</p>
                        @unless ($line['in_stock'])
                            <p class="text-sm text-amber-300">Solo quedan {{ $product->available_stock }}. Actualiza la cantidad.</p>
                        @endunless
                    </div>

                    <form method="POST" action="{{ route('cart.update', $product->id) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="0" max="{{ $product->available_stock }}"
                               class="w-20 rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
                        <button type="submit"
                                class="rounded-xl border border-white/10 px-3 py-2 text-sm hover:border-cyan-300/40">Actualizar</button>
                    </form>

                    <p class="w-28 text-right font-bold text-cyan-200">{{ Money::format($line['subtotal']) }}</p>

                    <form method="POST" action="{{ route('cart.remove', $product->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-300 hover:underline">Quitar</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-6">
            <div>
                <p class="text-sm text-slate-400">Total</p>
                <p class="text-3xl font-bold text-cyan-200">{{ Money::format($total) }}</p>
            </div>

            @if ($lines->contains(fn ($line) => ! $line['in_stock']))
                <span class="rounded-xl border border-amber-300/30 px-6 py-3 text-sm text-amber-200">
                    Ajusta las cantidades para continuar
                </span>
            @else
                <a href="{{ route('checkout.show') }}"
                   class="rounded-xl bg-cyan-300 px-6 py-3 font-semibold text-slate-900 transition hover:bg-cyan-200">
                    Finalizar compra
                </a>
            @endif
        </div>
    @endif
@endsection