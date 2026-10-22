<x-mail::message>
# Actualización de tu pedido

Tu pedido **#{{ $order->id }}** ha cambiado de estado.

<x-mail::panel>
**Estado anterior:** {{ $order->statusLabelFor($previousStatus) }}  
**Estado actual:** {{ $order->statusLabel() }}  
**Total:** {{ \App\Support\Money::format($order->total) }}
</x-mail::panel>

Puedes revisar los detalles y el seguimiento de tu pedido en el siguiente enlace:

<x-mail::button :url="route('orders.show', $order)">
Ver mi pedido
</x-mail::button>

Si tienes alguna pregunta, no dudes en contactarnos.

Gracias,  
{{ config('app.name') }}
</x-mail::message>
