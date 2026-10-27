<x-mail::message>
# No pudimos validar tu comprobante

El comprobante del pedido **#{{ $order->id }}** fue rechazado.

<x-mail::panel>
**Motivo:** {{ $reason }}  
**Total:** {{ \App\Support\Money::format($order->total) }}  
**Método:** {{ $order->paymentMethodLabel() }}
</x-mail::panel>

Puedes volver a subir tu comprobante desde la página del pedido. Tu reserva sigue activa hasta la fecha indicada allí.

<x-mail::button :url="route('orders.show', $order)">
Subir un nuevo comprobante
</x-mail::button>

Si crees que es un error, responde a este correo y lo revisamos.

Gracias,  
{{ config('app.name') }}
</x-mail::message>
