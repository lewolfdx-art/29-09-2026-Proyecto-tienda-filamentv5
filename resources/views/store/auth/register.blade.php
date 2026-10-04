@extends('layouts.store')

@section('title', 'Crear cuenta — ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-8">
        <h1 class="mb-6 text-2xl font-bold text-white">Crear cuenta</h1>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="mb-1 block text-sm text-slate-300">Nombre completo</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm text-slate-300">Correo</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm text-slate-300">Contraseña (mínimo 8 caracteres)</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm text-slate-300">Repite la contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-cyan-300 px-4 py-2 font-semibold text-slate-900 transition hover:bg-cyan-200">
                Crear cuenta
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-cyan-200 hover:underline">Ingresa</a>
        </p>
    </div>
@endsection