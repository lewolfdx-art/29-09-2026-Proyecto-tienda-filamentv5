@extends('layouts.store')

@use('App\Support\Money')

@section('title', $product->name . ' — ' . config('app.name'))

@section('content')
    <a href="{{ route('store.index') }}" class="mb-6 inline-block text-sm text-cyan-200 hover:underline">← Volver al catálogo</a>

    <div class="grid gap-8 md:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-cyan-300/10 bg-slate-900/70">
            @if ($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                     class="aspect-square w-full object-cover">
            @else
                <div class="flex aspect-square w-full items-center justify-center bg-white/5 text-8xl">📦</div>
            @endif
        </div>

        <div class="flex flex-col gap-4">
            @if ($product->category)
                <a href="{{ route('store.index', ['categoria' => $product->category->slug]) }}"
                   class="text-xs uppercase tracking-wide text-cyan-300/80 hover:underline">{{ $product->category->name }}</a>
            @endif

            <h1 class="text-3xl font-bold text-white">{{ $product->name }}</h1>

            <p class="text-3xl font-bold text-cyan-200">{{ Money::format($product->price) }}</p>

            @if ($product->available_stock <= 0)
                <p class="text-sm text-red-300">Agotado por ahora</p>
            @elseif ($product->available_stock <= $product->min_stock)
                <p class="text-sm text-amber-300">¡Quedan pocas unidades!</p>
            @else
                <p class="text-sm text-emerald-300">Disponible</p>
            @endif

            @if ($product->description)
                <div class="leading-relaxed text-slate-300">{!! nl2br(e($product->description)) !!}</div>
            @endif

            <form method="POST" action="{{ route('cart.add', $product->id) }}" class="mt-2 flex items-center gap-3">
                @csrf
                <input type="number" name="quantity" value="1" min="1" max="{{ max($product->available_stock, 1) }}"
                       @disabled($product->available_stock <= 0)
                       class="w-24 rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-white focus:border-cyan-300/50 focus:outline-none disabled:opacity-40">
                <button type="submit" @disabled($product->available_stock <= 0)
                        class="rounded-xl bg-cyan-300 px-6 py-2 font-semibold text-slate-900 transition hover:bg-cyan-200 disabled:cursor-not-allowed disabled:opacity-40">
                    Agregar al carrito
                </button>
            </form>
        </div>
    </div>
@endsection