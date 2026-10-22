<x-mail::message>
# ¡Gracias por tu compra!

Hemos recibido tu pedido **#{{ $order->id }}**.

<x-mail::panel>
**Total:** {{ \App\Support\Money::format($order->total) }}  
**Estado:** {{ $order->statusLabel() }}  
**Fecha:** {{ $order->created_at->format('d/m/Y H:i') }}
</x-mail::panel>

## Productos

@foreach($order->items as $item)
- {{ $item->quantity }} x {{ $item->product?->name }} — {{ \App\Support\Money::format($item->quantity * $item->unit_price) }}
@endforeach

Tus productos quedan reservados por {{ config('app.name') }} hasta que se confirme el pago. Si tienes alguna pregunta, responde a este correo.

<x-mail::button :url="route('orders.show', $order)">
Ver mi pedido
</x-mail::button>

Gracias,  
{{ config('app.name') }}
</x-mail::message>
