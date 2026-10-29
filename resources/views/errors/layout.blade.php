{{-- Página de error autónoma: no usa Vite ni la base de datos para no fallar junto con la app. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #e2e8f0;
            background: radial-gradient(1200px 600px at 85% -10%, rgba(103, 232, 249, 0.16), transparent 60%),
                        linear-gradient(135deg, #0a1c21 0%, #0d2f36 45%, #061014 100%);
        }
        main { max-width: 440px; text-align: center; }
        .code { font-size: 64px; font-weight: 800; color: #a5f3fc; margin: 0; line-height: 1; }
        h1 { font-size: 22px; margin: 16px 0 8px; color: #fff; }
        p { margin: 0 0 24px; color: #94a3b8; line-height: 1.5; }
        a {
            display: inline-block; padding: 10px 22px; border-radius: 999px; text-decoration: none;
            color: #a5f3fc; border: 1px solid rgba(103, 232, 249, 0.35);
        }
        a:hover { border-color: rgba(103, 232, 249, 0.7); }
    </style>
</head>
<body>
    <main>
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ url('/') }}">Volver a la tienda</a>
    </main>
</body>
</html>
