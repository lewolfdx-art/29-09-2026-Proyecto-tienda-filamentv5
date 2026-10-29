@component('mail::message')
# {{ $noticeSubject }}

{!! nl2br(e($bodyText)) !!}

@if ($actionUrl)
@component('mail::button', ['url' => $actionUrl])
{{ $actionLabel ?: 'Ver en el panel' }}
@endcomponent
@endif

Este aviso se envió automáticamente desde {{ config('app.name') }}.
@endcomponent
