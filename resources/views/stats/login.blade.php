<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stats login</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
<main class="mx-auto flex min-h-screen w-full max-w-sm items-center px-4">
    <form method="post" action="{{ route('stats.login.store') }}" class="w-full space-y-4">
        @csrf
        <h1 class="text-xl font-semibold">Статистика</h1>
        <label class="block space-y-1">
            <span class="text-sm text-slate-300">Логин</span>
            <input name="login" value="{{ old('login') }}" autocomplete="username" class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2" required>
        </label>
        <label class="block space-y-1">
            <span class="text-sm text-slate-300">Пароль</span>
            <input name="password" type="password" autocomplete="current-password" class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2" required>
        </label>
        @error('login')
        <p class="text-sm text-red-300">{{ $message }}</p>
        @enderror
        <button class="w-full rounded bg-emerald-500 px-3 py-2 font-medium text-slate-950" type="submit">Войти</button>
    </form>
</main>
</body>
</html>
