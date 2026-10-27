<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\StoreNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentProofController extends Controller
{
    public function store(Request $request, int $order)
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->findOrFail($order);

        if ($order->status !== 'pending' || ($order->expires_at && $order->expires_at->isPast())) {
            return back()->with('error', 'Este pedido ya no acepta comprobantes. La reserva venció o el pedido fue procesado.');
        }

        $method = $request->input('payment_method');

        $data = $request->validate([
            'payment_method' => [
                'required',
                'string',
                Rule::in(collect(config('payments.methods'))
                    ->filter(fn (array $item): bool => (bool) ($item['enabled'] ?? false))
                    ->keys()
                    ->all()),
            ],
            'payment_reference' => ['required', 'string', 'max:100'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.config('payments.max_proof_kb')],
        ], [
            'payment_method.required' => 'Elige cómo realizaste el pago.',
            'payment_reference.required' => 'Escribe el número de operación del pago.',
            'proof.required' => 'Adjunta la captura o el PDF del comprobante.',
            'proof.mimes' => 'El comprobante debe ser una imagen JPG, PNG, WEBP o un PDF.',
            'proof.max' => 'El comprobante no puede superar los 4 MB.',
        ]);

        $disk = config('payments.proof_disk');
        $directory = config('payments.proof_directory');

        if (filled($order->payment_proof_path)) {
            Storage::disk($disk)->delete($order->payment_proof_path);
        }

        $path = $request->file('proof')->store($directory, $disk);

        $order->update([
            'payment_method' => $method,
            'payment_reference' => $data['payment_reference'],
            'payment_proof_path' => $path,
            'payment_submitted_at' => now(),
            'payment_verified_at' => null,
            'payment_verified_by' => null,
            'payment_rejection_reason' => null,
        ]);

        StoreNotifier::paymentSubmitted($order->refresh());

        return back()->with('status', 'Comprobante recibido. Lo revisaremos y confirmaremos tu pedido lo antes posible.');
    }
}
