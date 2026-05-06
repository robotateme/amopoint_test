<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stats login</title>
    @vite(['resources/css/app.css'])
</head>
<body class="stats-body">
<main class="auth-layout">
    <section class="auth-card" aria-labelledby="stats-login-title">
        <header class="auth-header">
            <p class="eyebrow">Command relay // secure access</p>
            <h1 id="stats-login-title">Центр статистики</h1>
            <p class="auth-copy">
                Вход в тактическую панель мониторинга с почасовой активностью, секторами и сводкой по визитам.
            </p>
        </header>

        <form method="post" action="{{ route('stats.login.store') }}" class="auth-form">
            @csrf
            <label class="auth-field">
                <span class="auth-label">Логин</span>
                <input
                    name="login"
                    value="{{ old('login') }}"
                    autocomplete="username"
                    class="auth-input"
                    required
                >
            </label>
            <label class="auth-field">
                <span class="auth-label">Пароль</span>
                <input
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    class="auth-input"
                    required
                >
            </label>
            @error('login')
            <p class="auth-error">{{ $message }}</p>
            @enderror
            <button class="auth-button" type="submit">
                <span>Войти в сектор</span>
            </button>
        </form>

        <footer class="auth-footer">
            <span>uplink secured</span>
            <span>jwt channel active</span>
        </footer>
    </section>
</main>
</body>
</html>
