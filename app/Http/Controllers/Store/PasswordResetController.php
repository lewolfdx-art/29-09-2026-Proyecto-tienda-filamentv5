<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('store.auth.forgot-password');
    }

    public function email(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
        ], [
            'email.required' => 'Escribe tu correo.',
            'email.email' => 'Escribe un correo válido.',
        ]);

        $status = PasswordBroker::broker('customers')->sendResetLink($data);

        return $status === PasswordBroker::RESET_LINK_SENT
            ? back()->with('status', 'Te enviamos un enlace para restablecer tu contraseña.')
            : back()->withErrors(['email' => 'No pudimos enviar el enlace. Revisa el correo e inténtalo de nuevo.']);
    }

    public function resetForm(Request $request, string $token)
    {
        return view('store.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.required' => 'Escribe tu correo.',
            'email.email' => 'Escribe un correo válido.',
            'password.required' => 'Escribe una nueva contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $status = PasswordBroker::broker('customers')->reset($data, function (Customer $customer, string $password): void {
            $customer->password = $password;
            $customer->save();
        });

        if ($status === PasswordBroker::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Tu contraseña se actualizó. Ya puedes ingresar.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'El enlace no es válido o ya venció. Solicita uno nuevo.']);
    }
}
