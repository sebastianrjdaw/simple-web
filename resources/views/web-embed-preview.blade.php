<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Prueba AIMHARDER · Simple View</title>
    <style>
        html,body{margin:0;width:100%;height:100%;background:#000;color:#fff;font-family:system-ui,sans-serif;overflow:hidden}
        .bar{position:fixed;z-index:3;top:0;left:0;right:0;background:#111c;padding:8px 12px;font-size:13px}
        iframe{position:absolute;inset:0;width:100%;height:100%;border:0;background:#000;padding-top:36px;box-sizing:border-box}
        .overlay{position:absolute;inset:36px 0 0;background:transparent;z-index:2}
    </style>
</head>
<body>
    <div class="bar">Prueba de visualización: {{ $name }} · si queda en blanco, AIMHARDER puede estar bloqueando el iframe.</div>
    <iframe src="{{ $url }}" title="{{ $name }}" loading="eager" referrerpolicy="strict-origin-when-cross-origin" sandbox="allow-scripts allow-same-origin allow-forms"></iframe>
    @unless($interaction)<div class="overlay"></div>@endunless
</body>
</html>
