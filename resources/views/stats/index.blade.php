<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visit statistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<script>
    window.__VISIT_STATS__ = @json([
        'stats' => $stats,
        'hours' => $hours,
    ]);
</script>
<div id="stats-app" data-page-title="Статистика посещений"></div>
</body>
</html>
