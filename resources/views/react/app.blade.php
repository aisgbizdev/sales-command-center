<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/logo-mark.svg') }}">
    @vite('resources/react/main.tsx')
</head>
<body>
    <div id="root"></div>
    <script>
        window.__SGB_BOOT__ = @json($boot);
    </script>
</body>
</html>
