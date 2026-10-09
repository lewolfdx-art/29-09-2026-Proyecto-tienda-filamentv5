@use('App\Support\Money')

@if ($featured->isNotEmpty())
    @php
        $count = $featured->count();
        // Radio del anillo: crece con la cantidad de productos para que no se amontonen.
        $radius = (int) max(280, round(85 / tan(M_PI / max($count, 3))) + 150);
    @endphp

    <section class="mb-10">
        <div class="mb-2 flex flex-wrap items-end justify-between gap-2">
            <h2 class="text-xl font-bold text-white">Productos destacados</h2>
            <p class="text-xs text-slate-400">Pasa el cursor para detener · haz clic para ver el producto</p>
        </div>

        @if ($count >= 3)
            <div class="fc-stage rounded-2xl border border-cyan-300/10 bg-slate-900/40">
                <div class="fc-slider" style="--quantity: {{ $count }}; --radius: {{ $radius }}px;">
                    @foreach ($featured as $product)
                        <a href="{{ route('store.show', $product->slug) }}" class="fc-item"
                           style="--position: {{ $loop->iteration }}" aria-label="Ver {{ $product->name }}">
                            @if ($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-white/5 text-5xl">📦</div>
                            @endif

                            <span class="fc-label">
                                <span class="fc-name">{{ $product->name }}</span>
                                <span class="fc-price">{{ Money::format($product->price) }}</span>
                                @if ($product->available_stock <= 0)
                                    <span class="fc-tag">Agotado</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <div class="flex flex-wrap justify-center gap-5">
                @foreach ($featured as $product)
                    <a href="{{ route('store.show', $product->slug) }}"
                       class="w-44 overflow-hidden rounded-2xl border border-cyan-300/30 bg-slate-900/70 transition hover:border-cyan-300/60">
                        @if ($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                 class="aspect-square w-full object-cover">
                        @else
                            <div class="flex aspect-square w-full items-center justify-center bg-white/5 text-5xl">📦</div>
                        @endif

                        <div class="p-3">
                            <p class="line-clamp-2 text-sm font-semibold text-white">{{ $product->name }}</p>
                            <p class="font-bold text-cyan-200">{{ Money::format($product->price) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endif