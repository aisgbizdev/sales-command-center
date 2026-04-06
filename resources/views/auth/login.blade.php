<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/logo-mark.svg') }}">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body>
    <main class="auth">
        <section class="card">
            <div class="hero-copy">
                <img src="{{ asset('brand/logo-word.svg') }}" alt="{{ config('app.name') }}" class="brand-lockup">
                <h1 style="margin-top:0;">{{ config('app.name') }}</h1>
                <p>Masuk untuk mulai input prospek, pantau follow up, dan lihat dashboard harian.</p>
            </div>

            @if ($errors->any())
                <ul class="error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form method="post" action="{{ route('login.attempt') }}" class="grid">
                @csrf
                <label>Email
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email kerja" required>
                </label>
                <label>Password
                    <input type="password" name="password" placeholder="Masukkan password" required>
                </label>
                <label class="checkbox-inline">
                    <input type="checkbox" name="remember" value="1"> Ingat saya
                </label>
                <button type="submit" class="btn">Masuk ke Dashboard</button>
            </form>

            <div class="soft-panel" style="margin-top:12px;">
                <strong>Akun demo</strong><br>
                <span class="helper-text"><code>admin@sgbcc.test</code> / <code>password123</code> (Super Admin)</span>
            </div>
        </section>
    </main>
</body>
</html>
