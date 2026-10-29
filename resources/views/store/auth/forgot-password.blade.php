@extends('layouts.store')

@section('title', 'Recuperar contraseña — ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-cyan-300/10 bg-slate-900/70 p-8">
        <h1 class="mb-2 text-2xl font-bold text-white">Recuperar contraseña</h1>
        <p class="mb-6 text-sm text-slate-400">
            Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva contraseña.
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm text-slate-300">Correo</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-white focus:border-cyan-300/50 focus:outline-none">
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-cyan-300 px-4 py-2 font-semibold text-slate-900 transition hover:bg-cyan-200">
                Enviar enlace
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            <a href="{{ route('login') }}" class="text-cyan-200 hover:underline">Volver a ingresar</a>
        </p>
    </div>
@endsection
