<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('brand/Logo SG-WEB111.png') }}">
    @php
        $manifestPath = public_path('build/manifest.json');
    @endphp

    @if (file_exists($manifestPath))
        @php
            $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
            $entry = $manifest['resources/react/main.tsx'] ?? null;
            $entryJs = $entry['file'] ?? null;
            $entryCss = $entry['css'] ?? [];
        @endphp

        @foreach ($entryCss as $cssFile)
            <link rel="stylesheet" href="{{ asset('build/' . $cssFile) }}">
        @endforeach

        @if ($entryJs)
            <script type="module" src="{{ asset('build/' . $entryJs) }}"></script>
        @endif
    @elseif (file_exists(public_path('hot')))
        @viteReactRefresh
        @vite('resources/react/main.tsx')
    @endif
</head>
<body>
    <div id="root"></div>
    <script>
        window.__SGB_BOOT__ = @json($boot);
    </script>
</body>
</html>
