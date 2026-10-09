@extends('layouts.store')

@section('title', 'Ingresar — ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-8">
        <h1 class="mb-2 text-2xl font-bold text-white">Ingresar</h1>
        <p class="mb-6 text-sm text-slate-400">
            
        </p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm text-slate-300">Correo</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm text-slate-300">Contraseña</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" name="remember" value="1" class="rounded border-white/20 bg-slate-900">
                Mantener la sesión iniciada
            </label>

            <button type="submit"
                    class="w-full rounded-xl bg-cyan-300 px-4 py-2 font-semibold text-slate-900 transition hover:bg-cyan-200">
                Ingresar
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}" class="text-cyan-200 hover:underline">Regístrate</a>
        </p>
    </div>
@endsection