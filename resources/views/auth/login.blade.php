<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('brand/Logo SG-WEB111.png') }}">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body>
    <main class="auth">
        <section class="card">
            <div class="hero-copy">
                <img src="{{ asset('brand/Logo SG-WEB111.png') }}" alt="{{ config('app.name') }}" class="brand-lockup">
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
                <label>Filter user
                    <select id="login-user-filter">
                        <option value="all">Semua user</option>
                        @foreach ($loginUsers->pluck('role')->unique()->values() as $role)
                            <option value="role:{{ $role }}">{{ \App\Models\User::ROLE_LABELS[$role] ?? ucfirst(str_replace('_', ' ', $role)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Nama / Jabatan
                    <select name="email" id="login-user-select" required>
                        <option value="">Pilih user</option>
                        @foreach ($loginUsers as $loginUser)
                            <option
                                value="{{ $loginUser->email }}"
                                data-role="{{ $loginUser->role }}"
                                data-unit="{{ $loginUser->unit_id }}"
                                data-team="{{ $loginUser->team_id }}"
                                @selected(old('email') === $loginUser->email)
                            >
                                {{ $loginUser->name }} - {{ $loginUser->roleLabel() }}
                            </option>
                        @endforeach
                    </select>
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
    <script>
        const userFilter = document.getElementById('login-user-filter');
        const userSelect = document.getElementById('login-user-select');

        function applyUserFilter() {
            const [type, value] = userFilter.value.split(':');
            let selectedStillVisible = false;

            [...userSelect.options].forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                const visible =
                    type === 'all' ||
                    (type === 'role' && option.dataset.role === value);

                option.hidden = !visible;
                if (visible && option.selected) selectedStillVisible = true;
            });

            if (!selectedStillVisible) userSelect.value = '';
        }

        userFilter.addEventListener('change', applyUserFilter);
        applyUserFilter();
    </script>
</body>
</html>
