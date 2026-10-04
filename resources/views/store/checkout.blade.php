@extends('layouts.store')

@use('App\Support\Money')

@section('title', 'Finalizar compra — ' . config('app.name'))

@section('content')
    <h1 class="mb-6 text-2xl font-bold text-white">Finalizar compra</h1>

    <div class="grid gap-8 lg:grid-cols-3">
        <form method="POST" action="{{ route('checkout.store') }}"
              class="space-y-4 rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-6 lg:col-span-2">
            @csrf

            <p class="text-sm text-slate-400">
                Compras como <span class="text-white">{{ $customer->name }}</span> ({{ $customer->email }})
            </p>

            <div>
                <label for="phone" class="mb-1 block text-sm text-slate-300">Teléfono</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone', $customer->phone) }}" required autocomplete="tel"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <div>
                <label for="address" class="mb-1 block text-sm text-slate-300">Dirección de entrega</label>
                <input id="address" type="text" name="address" value="{{ old('address', $customer->address) }}" required autocomplete="street-address"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <div>
                <label for="city" class="mb-1 block text-sm text-slate-300">Ciudad</label>
                <input id="city" type="text" name="city" value="{{ old('city', $customer->city) }}" required autocomplete="address-level2"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <div>
                <label for="notes" class="mb-1 block text-sm text-slate-300">Notas del pedido (opcional)</label>
                <textarea id="notes" name="notes" rows="3" maxlength="500"
                          class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">{{ old('notes') }}</textarea>
            </div>

            <p class="text-xs text-slate-400">Tu teléfono y dirección se guardan cifrados.</p>

            <button type="submit"
                    class="w-full rounded-xl bg-cyan-300 px-6 py-3 font-semibold text-slate-900 transition hover:bg-cyan-200">
                Confirmar pedido
            </button>
        </form>

        <aside class="h-fit rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-6">
            <h2 class="mb-4 font-semibold text-white">Resumen</h2>

            <ul class="space-y-3 text-sm">
                @foreach ($lines as $line)
                    <li class="flex justify-between gap-3">
                        <span class="text-slate-300">{{ $line['quantity'] }} × {{ $line['product']->name }}</span>
                        <span class="shrink-0 text-slate-200">{{ Money::format($line['subtotal']) }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex justify-between border-t border-white/10 pt-4">
                <span class="text-slate-400">Total</span>
                <span class="text-xl font-bold text-cyan-200">{{ Money::format($total) }}</span>
            </div>

            <p class="mt-4 text-xs text-slate-400">
                Al confirmar, reservamos tus productos por 24 horas. Después te contactaremos con las instrucciones de pago.
            </p>
        </aside>
    </div>
@endsection