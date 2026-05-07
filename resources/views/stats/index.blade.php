<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visit statistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="stats-body">
@php
    $socketIo = [
        'enabled' => (bool) config('services.socket_io.enabled'),
        'url' => config('services.socket_io.client_url'),
        'path' => '/socket.io',
    ];
@endphp
<script>
    window.__VISIT_STATS__ = @json([
        'stats' => $stats,
        'hours' => $hours,
    ]);
    window.__SOCKET_IO__ = @json($socketIo);
</script>
<div id="stats-app" data-page-title="Статистика посещений"></div>
</body>
</html>
