@extends('layouts.store')

@use('App\Support\Money')

@section('title', config('app.name') . ' — Catálogo')

@section('content')
    @include('store.partials.featured', ['featured' => $featured])
    @php
        $baseQuery = array_filter([
            'q' => $search,
            'orden' => $sort !== 'recientes' ? $sort : null,
        ]);
        $chip = 'rounded-full border px-4 py-1.5 text-sm transition';
        $chipOn = 'border-cyan-300/40 bg-cyan-300/20 text-cyan-200';
        $chipOff = 'border-white/10 text-slate-300 hover:border-cyan-300/30';
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('store.index', $baseQuery) }}"
               class="{{ $chip }} {{ $categorySlug === '' ? $chipOn : $chipOff }}">Todas</a>

            @foreach ($categories as $category)
                <a href="{{ route('store.index', $baseQuery + ['categoria' => $category->slug]) }}"
                   class="{{ $chip }} {{ $categorySlug === $category->slug ? $chipOn : $chipOff }}">{{ $category->name }}</a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('store.index') }}" class="flex items-center gap-2 text-sm">
            @if ($search !== '')
                <input type="hidden" name="q" value="{{ $search }}">
            @endif
            @if ($categorySlug !== '')
                <input type="hidden" name="categoria" value="{{ $categorySlug }}">
            @endif
            <label for="orden" class="text-slate-400">Ordenar por</label>
            <select id="orden" name="orden" onchange="this.form.submit()"
                    class="rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-slate-200 focus:border-cyan-300/50 focus:outline-none">
                <option value="recientes" @selected($sort === 'recientes')>Más recientes</option>
                <option value="precio_asc" @selected($sort === 'precio_asc')>Precio: menor a mayor</option>
                <option value="precio_desc" @selected($sort === 'precio_desc')>Precio: mayor a menor</option>
                <option value="nombre" @selected($sort === 'nombre')>Nombre</option>
            </select>
        </form>
    </div>

    @if ($search !== '')
        <p class="mb-4 text-sm text-slate-400">Resultados para «{{ $search }}»</p>
    @endif

    @if ($products->isEmpty())
        <div class="rounded-2xl border border-cyan-300/10 bg-slate-900/60 p-12 text-center text-slate-400">
            No encontramos productos con esos filtros.
        </div>
    @else
        <div class="grid grid-cols-2 gap-5 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <article class="group flex flex-col overflow-hidden rounded-2xl border border-cyan-300/10 bg-slate-900/70 shadow-lg shadow-black/20 transition hover:border-cyan-300/30">
                    <a href="{{ route('store.show', $product->slug) }}" class="block overflow-hidden">
                        @if ($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy"
                                 class="aspect-square w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <div class="flex aspect-square w-full items-center justify-center bg-white/5 text-5xl">📦</div>
                        @endif
                    </a>

                    <div class="flex flex-1 flex-col gap-2 p-4">
                        @if ($product->category)
                            <span class="text-xs uppercase tracking-wide text-cyan-300/80">{{ $product->category->name }}</span>
                        @endif

                        <a href="{{ route('store.show', $product->slug) }}"
                           class="font-semibold leading-snug text-white hover:text-cyan-200">{{ $product->name }}</a>

                        <div class="mt-auto flex items-end justify-between pt-2">
                            <span class="text-lg font-bold text-cyan-200">{{ Money::format($product->price) }}</span>

                            @if ($product->available_stock <= 0)
                                <span class="text-xs text-red-300">Agotado</span>
                            @elseif ($product->available_stock <= $product->min_stock)
                                <span class="text-xs text-amber-300">Pocas unidades</span>
                            @else
                                <span class="text-xs text-emerald-300">En stock</span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('cart.add', $product->id) }}">
                            @csrf
                            <button type="submit" @disabled($product->available_stock <= 0)
                                    class="w-full rounded-xl bg-cyan-300 px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-cyan-200 disabled:cursor-not-allowed disabled:opacity-40">
                                Agregar al carrito
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <nav class="mt-10 flex items-center justify-between gap-3" aria-label="Paginación">
            @if ($products->onFirstPage())
                <span aria-disabled="true"
                      class="cursor-not-allowed rounded-xl border border-white/10 px-5 py-2 text-sm font-semibold text-slate-600">
                    Anterior
                </span>
            @else
                <a href="{{ $products->previousPageUrl() }}" rel="prev"
                   class="rounded-xl border border-cyan-300/30 px-5 py-2 text-sm font-semibold text-cyan-200 transition hover:border-cyan-300/60 hover:bg-cyan-300/10">
                    Anterior
                </a>
            @endif

            <span class="rounded-full border border-white/10 px-4 py-1 text-sm text-slate-300">
                Página {{ $products->currentPage() }}
            </span>

            @if ($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}" rel="next"
                   class="rounded-xl bg-cyan-300 px-5 py-2 text-sm font-semibold text-slate-900 transition hover:bg-cyan-200">
                    Siguiente
                </a>
            @else
                <span aria-disabled="true"
                      class="cursor-not-allowed rounded-xl border border-white/10 px-5 py-2 text-sm font-semibold text-slate-600">
                    Siguiente
                </span>
            @endif
        </nav>
    @endif
@endsection