@extends('layouts.store')

@use('App\Support\Money')

@section('title', 'Mis pedidos — ' . config('app.name'))

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-white">Mis pedidos</h1>

    @if ($orders->isEmpty())
        <div class="rounded-2xl border border-cyan-300/10 bg-slate-900/60 p-12 text-center text-slate-400">
            <p class="mb-4">Todavía no has hecho pedidos.</p>
            <a href="{{ route('store.index') }}" class="text-cyan-200 hover:underline">Ir al catálogo</a>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($orders as $order)
                <a href="{{ route('orders.show', $order->id) }}"
                   class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-4 transition hover:border-cyan-300/30">
                    <div>
                        <p class="font-semibold text-white">Pedido #{{ $order->id }}</p>
                        <p class="text-sm text-slate-400">
                            {{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->items_count }} artículo(s)
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-300">{{ $order->statusLabel() }}</span>
                        <span class="font-bold text-cyan-200">{{ Money::format($order->total) }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <nav class="mt-10 flex items-center justify-between gap-3" aria-label="Paginación">
            @if ($orders->onFirstPage())
                <span aria-disabled="true"
                      class="cursor-not-allowed rounded-xl border border-white/10 px-5 py-2 text-sm font-semibold text-slate-600">
                    Anterior
                </span>
            @else
                <a href="{{ $orders->previousPageUrl() }}" rel="prev"
                   class="rounded-xl border border-cyan-300/30 px-5 py-2 text-sm font-semibold text-cyan-200 transition hover:border-cyan-300/60 hover:bg-cyan-300/10">
                    Anterior
                </a>
            @endif

            <span class="rounded-full border border-white/10 px-4 py-1 text-sm text-slate-300">
                Página {{ $orders->currentPage() }}
            </span>

            @if ($orders->hasMorePages())
                <a href="{{ $orders->nextPageUrl() }}" rel="next"
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