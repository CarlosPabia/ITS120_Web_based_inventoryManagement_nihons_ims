<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - Nihon Cafe</title>

    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('login.css') }}">
</head>
<body class="login-page">
    <div class="login-background" aria-hidden="true">
        <div class="login-overlay"></div>
    </div>

    <main class="login-container" role="main">
        <section class="login-card" aria-labelledby="app-title">
            <header class="login-header">
                <img src="{{ asset('image/logo.png') }}" alt="Nihon Cafe Logo" class="login-logo">
                <h1 id="app-title" class="login-title">Nihon Cafe</h1>
                <p class="login-subtitle">Inventory Management System</p>
            </header>

            <form method="POST" action="{{ url('/login') }}" class="login-form" novalidate>
                @csrf

                <label class="login-field">
                    <span class="login-label">Email</span>
                    <input
                        type="email"
                        name="email"
                        class="login-input"
                        placeholder="you@nihoncafe.ph"
                        required
                        autofocus
                        value="{{ old('email') }}"
                    >
                </label>

                <label class="login-field">
                    <span class="login-label">Password</span>
                    <input
                        type="password"
                        name="password"
                        class="login-input"
                        placeholder="••••••••"
                        required
                    >
                </label>

                @error('email')
                    <p class="login-error" role="alert">{{ $message }}</p>
                @enderror

                <button type="submit" class="login-submit">Sign in</button>
            </form>

            <footer class="login-footer">
                <p class="security-note">
                    <span class="security-icon" aria-hidden="true">🔐</span>
                    AES-256 encrypted records for every transaction.
                </p>
            </footer>
        </section>
    </main>

    <script src="{{ asset('js/script.js') }}" defer></script>
</body>
</html>
