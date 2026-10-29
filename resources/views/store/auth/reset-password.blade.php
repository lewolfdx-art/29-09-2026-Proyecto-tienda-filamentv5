@extends('layouts.store')

@section('title', 'Nueva contraseña — ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-8">
        <h1 class="mb-2 text-2xl font-bold text-white">Nueva contraseña</h1>
        <p class="mb-6 text-sm text-slate-400">Elige una contraseña de al menos 8 caracteres.</p>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="mb-1 block text-sm text-slate-300">Correo</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm text-slate-300">Nueva contraseña</label>
                <input id="password" type="password" name="password" required autofocus autocomplete="new-password"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm text-slate-300">Repite la contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-cyan-300 px-4 py-2 font-semibold text-slate-900 transition hover:bg-cyan-200">
                Guardar contraseña
            </button>
        </form>
    </div>
@endsection
