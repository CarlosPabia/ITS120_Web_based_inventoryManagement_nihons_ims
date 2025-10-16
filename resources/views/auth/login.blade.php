<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - Nihon Café</title>

    <!-- External CSS files in public/css/ -->
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('login.css') }}">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <h1 class="title">Nihon Café</h1>
            <p class="subtitle">Inventory Management System</p>

            <form method="POST" action="{{ url('/login') }}">
                @csrf

                <input type="email" name="email" placeholder="Email" class="input" required value="{{ old('email') }}">

                <input type="password" name="password" placeholder="Password" class="input" required>

                @error('email')
                    <div class="input-error">{{ $message }}</div>
                @enderror

                <button type="submit" class="login-btn">Login</button>
            </form>

            <a href="#" class="forgot-password">Forgot password?</a>

            <div class="security-message">
                <span>🔒 All Records are AES-256 Encrypted.</span>
            </div>
        </div>
    </div>

    <!-- External JS file -->
    <script src="{{ asset('js/script.js') }}" defer></script>
</body>
</html>
