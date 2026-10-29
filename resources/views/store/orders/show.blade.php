@extends('layouts.store')

@use('App\Support\Money')
@use('Illuminate\Support\Facades\Storage')

@section('title', 'Pedido #' . $order->id . ' — ' . config('app.name'))

@section('content')
    <a href="{{ route('orders.index') }}" class="mb-6 inline-block rounded-xl border border-cyan-300/30 px-5 py-2 text-sm font-semibold text-cyan-200 transition hover:border-cyan-300/60 hover:bg-cyan-300/10">Mis pedidos</a>

    @php
        $selectedMethod = $order->payment_method && $paymentMethods->has($order->payment_method)
            ? $order->payment_method
            : $paymentMethods->keys()->first();
        $payment = $selectedMethod ? $paymentMethods->get($selectedMethod) : null;
        $qrUrl = null;

        if ($payment && filled($payment['qr'] ?? null)) {
            $qrUrl = str_starts_with($payment['qr'], 'http')
                ? $payment['qr']
                : Storage::disk('public')->url($payment['qr']);
        }
    @endphp

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
                Completa el pago y sube tu comprobante para confirmarlo.
            </div>
        @endif
    @elseif ($order->status === 'expired')
        <div class="mb-6 rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm text-amber-200">
            Este pedido expiró: no se completó el pago a tiempo y la reserva de los productos se liberó.
            Si todavía quieres pedirlos, vuelve al catálogo para hacer un nuevo pedido.
        </div>
    @endif

    @if ($order->status === 'pending' && (! $order->expires_at || $order->expires_at->isFuture()))
        @if ($order->paymentIsUnderReview())
            <div class="mb-6 rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-200">
                <p class="font-semibold">Comprobante recibido. Estamos verificando tu pago.</p>
                <p class="mt-1">
                    Método: {{ $order->paymentMethodLabel() }}
                    · Operación: {{ $order->payment_reference }}
                    · Enviado el {{ $order->payment_submitted_at->format('d/m/Y H:i') }}.
                </p>
                @if ($order->paymentProofUrl())
                    <a href="{{ $order->paymentProofUrl() }}" target="_blank" rel="noopener"
                       class="mt-2 inline-block text-emerald-100 underline">Ver comprobante enviado</a>
                @endif
            </div>
        @elseif ($order->paymentWasRejected())
            <div class="mb-6 rounded-xl border border-red-300/30 bg-red-300/10 px-4 py-3 text-sm text-red-200">
                <p class="font-semibold">No pudimos validar tu comprobante.</p>
                <p class="mt-1">{{ $order->payment_rejection_reason }}</p>
                <p class="mt-1">Vuelve a subirlo con la información correcta.</p>
            </div>
        @endif

        @if ($payment)
            <div class="mb-6 rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-6">
                <h2 class="text-lg font-semibold text-white">Paga con {{ $payment['label'] }}</h2>
                <p class="mt-1 text-sm text-slate-400">{{ $payment['instructions'] }}</p>

                <div class="mt-4 grid gap-6 sm:grid-cols-2">
                    <div class="space-y-2 text-sm">
                        <p class="text-slate-400">Monto exacto</p>
                        <p class="text-2xl font-bold text-cyan-200">{{ Money::format($order->total) }}</p>

                        <p class="pt-2 text-slate-400">Número {{ $payment['label'] }}</p>
                        <p class="font-semibold text-white">
                            {{ filled($payment['phone'] ?? null) ? $payment['phone'] : 'Escríbenos para coordinar el número' }}
                        </p>

                        @if (filled($payment['holder'] ?? null))
                            <p class="text-slate-400">Titular: <span class="text-slate-200">{{ $payment['holder'] }}</span></p>
                        @endif

                        <p class="text-xs text-slate-400">
                            Si la app te pide una referencia, usa tu pedido <span class="text-slate-200">#{{ $order->id }}</span>.
                        </p>
                    </div>

                    <div class="flex flex-col items-start gap-3">
                        @if ($qrUrl)
                            <img src="{{ $qrUrl }}" alt="QR de {{ $payment['label'] }}"
                                 class="h-48 w-48 rounded-xl border border-white/10 bg-white p-2">
                        @endif

                        <ol class="space-y-1 text-sm text-slate-300">
                            <li>1. Abre tu app {{ $payment['label'] }}.</li>
                            <li>2. Paga el monto exacto al número indicado.</li>
                            <li>3. Guarda la captura o el PDF del comprobante.</li>
                            <li>4. Súbelo aquí abajo.</li>
                        </ol>
                    </div>
                </div>
            </div>
        @endif

        @php($showUploadForm = $payment && ! $order->paymentIsUnderReview())

        @if ($showUploadForm)
            <form method="POST" action="{{ route('orders.payment-proof.store', $order) }}"
                  enctype="multipart/form-data"
                  class="mb-6 space-y-4 rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-6">
                @csrf

                <h2 class="text-lg font-semibold text-white">Subir comprobante de pago</h2>

                @if ($paymentMethods->isNotEmpty())
                    <div>
                        <label for="payment_method" class="mb-1 block text-sm text-slate-300">Método de pago</label>
                        <select id="payment_method" name="payment_method" required
                                class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
                            @foreach ($paymentMethods as $key => $method)
                                <option value="{{ $key }}" @selected(old('payment_method', $selectedMethod) === $key)>
                                    {{ $method['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label for="payment_reference" class="mb-1 block text-sm text-slate-300">Número de operación</label>
                    <input id="payment_reference" type="text" name="payment_reference" maxlength="100"
                           value="{{ old('payment_reference', $order->payment_reference) }}" required
                           placeholder="Ej. 012345"
                           class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
                    @error('payment_reference')
                        <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="proof" class="mb-1 block text-sm text-slate-300">Comprobante</label>
                    <input id="proof" type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf" required
                           class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm text-slate-300 focus:border-cyan-300/50 focus:outline-none">
                    <p class="mt-1 text-xs text-slate-400">JPG, PNG, WEBP o PDF. Máximo 4 MB.</p>
                    @error('proof')
                        <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full rounded-xl bg-cyan-300 px-6 py-3 font-semibold text-slate-900 transition hover:bg-cyan-200 sm:w-auto">
                    Enviar comprobante
                </button>
            </form>
        @elseif ($payment)
            <details class="mb-6 rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-6">
                <summary class="cursor-pointer text-sm font-semibold text-cyan-200">
                    ¿Necesitas reemplazar el comprobante?
                </summary>

                <form method="POST" action="{{ route('orders.payment-proof.store', $order) }}"
                      enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <label for="payment_method_replace" class="mb-1 block text-sm text-slate-300">Método de pago</label>
                        <select id="payment_method_replace" name="payment_method" required
                                class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
                            @foreach ($paymentMethods as $key => $method)
                                <option value="{{ $key }}" @selected($selectedMethod === $key)>{{ $method['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="payment_reference_replace" class="mb-1 block text-sm text-slate-300">Número de operación</label>
                        <input id="payment_reference_replace" type="text" name="payment_reference" maxlength="100"
                               value="{{ $order->payment_reference }}" required
                               class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
                    </div>

                    <div>
                        <label for="proof_replace" class="mb-1 block text-sm text-slate-300">Nuevo comprobante</label>
                        <input id="proof_replace" type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf" required
                               class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm text-slate-300 focus:border-cyan-300/50 focus:outline-none">
                    </div>

                    <button type="submit"
                            class="rounded-xl bg-cyan-300 px-6 py-3 font-semibold text-slate-900 transition hover:bg-cyan-200">
                        Reemplazar comprobante
                    </button>
                </form>
            </details>
        @else
            <div class="mb-6 rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm text-amber-200">
                El equipo coordinará contigo el pago de este pedido.
            </div>
        @endif
    @endif

    @if ($order->payment_verified_at && $order->payment_reference)
        <div class="mb-6 rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-200">
            Pago verificado con {{ $order->paymentMethodLabel() }}
            · Operación {{ $order->payment_reference }}
            · {{ $order->payment_verified_at->format('d/m/Y H:i') }}.
        </div>
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
            <p class="text-slate-400">{{ $order->phone ?? $customer->phone }}</p>
            <p class="text-slate-400">{{ $order->address ?? $customer->address }}</p>
            <p class="text-slate-400">{{ $order->city ?? $customer->city }}</p>

            @if ($order->notes)
                <h2 class="mb-1 mt-4 font-semibold text-white">Notas</h2>
                <p class="text-slate-400">{{ $order->notes }}</p>
            @endif
        </aside>
    </div>
@endsection
