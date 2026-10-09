<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-slate-200 antialiased"
      style="background: radial-gradient(1200px 600px at 85% -10%, rgba(103, 232, 249, 0.16), transparent 60%), linear-gradient(135deg, #0a1c21 0%, #0d2f36 45%, #061014 100%); background-attachment: fixed;">

    @php
        $isStaff = auth('web')->check();
        $isCustomer = auth('customer')->check();
        $whoName = $isCustomer ? auth('customer')->user()->name : ($isStaff ? auth('web')->user()->name : '');
    @endphp

    <header class="sticky top-0 z-20 border-b border-cyan-300/10 bg-slate-950/70 backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-3 px-4 py-3">
            <a href="{{ route('store.index') }}" class="text-lg font-bold text-cyan-200">{{ config('app.name') }}</a>

            <form action="{{ route('store.index') }}" method="GET" class="order-last w-full flex-1 sm:order-0 sm:w-auto">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar productos…"
                       class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-white placeholder-slate-400 focus:border-cyan-300/50 focus:outline-none">
            </form>

            <nav class="ml-auto flex items-center gap-2 text-sm">
                @if ($isStaff)
                    <a href="{{ \Filament\Facades\Filament::getPanel('admin')->getUrl() }}"
                       class="rounded-xl border border-cyan-300/30 px-3 py-2 text-cyan-200 hover:border-cyan-300/60">Panel</a>
                @endif

                @if ($isCustomer)
                    <a href="{{ route('orders.index') }}"
                       class="rounded-xl border border-white/10 px-3 py-2 hover:border-cyan-300/40">Mis pedidos</a>
                @endif

                @if ($isStaff || $isCustomer)
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-white/10 px-3 py-2 hover:border-cyan-300/40">
                            Salir ({{ \Illuminate\Support\Str::of($whoName)->before(' ') }})
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="rounded-xl border border-white/10 px-3 py-2 hover:border-cyan-300/40">Ingresar</a>
                    <a href="{{ route('register') }}"
                       class="rounded-xl border border-cyan-300/30 px-3 py-2 text-cyan-200 hover:border-cyan-300/60">Registrarme</a>
                @endif

                <a href="{{ route('cart.index') }}"
                   class="relative rounded-xl border border-white/10 px-4 py-2 hover:border-cyan-300/40">
                    🛒 Carrito
                    @php($cartCount = \App\Support\Cart::count())
                    @if ($cartCount > 0)
                        <span class="absolute -right-2 -top-2 rounded-full bg-cyan-300 px-2 text-xs font-bold text-slate-900">{{ $cartCount }}</span>
                    @endif
                </a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-300/30 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-xl border border-red-300/30 bg-red-300/10 px-4 py-3 text-sm text-red-200">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-300/30 bg-red-300/10 px-4 py-3 text-sm text-red-200">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-cyan-300/10 py-6 text-center text-xs text-slate-400">
        © {{ date('Y') }} {{ config('app.name') }}
    </footer>
</body>
</html>