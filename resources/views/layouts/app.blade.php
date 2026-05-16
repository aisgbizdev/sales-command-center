<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('brand/Logo SG-WEB111.png') }}">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="h-full font-outfit text-[#fff2a2]" x-data="{
    sidebarOpen: false,
    sidebarExpand: true,
    init() {
        this.$watch('sidebarOpen', (value) => {
            document.documentElement.style.overflow = value ? 'hidden' : '';
            document.body.style.overflow = value ? 'hidden' : '';
        });
    }
}" @keydown.escape.window="sidebarOpen = false">
    <div
        class="min-h-screen bg-transparent px-4 py-4 md:px-6 xl:grid xl:grid-cols-[auto_minmax(0,1fr)] xl:items-start xl:gap-4">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity
            class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm xl:hidden" @click="sidebarOpen = false"></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[82vw] max-w-[340px] -translate-x-full flex-col border-r border-white/10 bg-[rgba(11,14,19,0.94)] p-4 transition-transform duration-300 backdrop-blur-xl sm:w-[360px] xl:sticky xl:inset-y-auto xl:left-auto xl:top-4 xl:h-[calc(100vh-2rem)] xl:w-[290px] xl:max-w-none xl:rounded-[28px] xl:border xl:border-white/10 xl:border-r xl:bg-[rgba(11,14,19,0.88)] xl:shadow-[0_24px_70px_rgba(0,0,0,0.35)] xl:translate-x-0"
            :class="{ 'translate-x-0': sidebarOpen, 'xl:w-[90px]': !sidebarExpand, 'xl:w-[290px]': sidebarExpand }">
            <div class="rounded-[24px] border border-white/10 bg-white/5 p-4"
                :class="sidebarExpand ? '' : 'xl:flex xl:justify-center xl:p-3'">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <img x-cloak x-show="!sidebarExpand" src="{{ asset('brand/Logo SG-WEB111.png') }}" alt="Logo"
                        class="h-9 w-9 rounded-2xl">
                    <img x-cloak x-show="sidebarExpand" src="{{ asset('brand/Logo SG-WEB111.png') }}"
                        alt="{{ config('app.name') }}" class="h-9 w-auto">
                </a>
                <div x-show="sidebarExpand" class="mt-4">
                    <p class="text-xl font-semibold tracking-tight text-[#fff2a2]">{{ auth()->user()->name }}</p>
                    <p class="mt-1 text-sm text-[#d9c995]">{{ auth()->user()->roleLabel() }}</p>
                </div>
            </div>

            <nav class="mt-6 flex-1 space-y-2 overflow-y-auto pr-1">
                <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#d9c995]/70"
                    x-show="sidebarExpand">Menu</p>

                <a href="{{ route('dashboard') }}"
                    class="menu-item group {{ request()->routeIs('dashboard') ? 'menu-item-active' : 'menu-item-inactive' }}"
                    :class="{ 'xl:justify-center': !sidebarExpand }">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"
                                fill="currentColor" />
                        </svg>
                    </span>
                    <span x-show="sidebarExpand">Dashboard</span>
                </a>

                <a href="{{ route('prospects.index') }}"
                    class="menu-item group {{ request()->routeIs('prospects.index', 'prospects.create', 'prospects.store', 'prospects.show', 'prospects.edit', 'prospects.update') ? 'menu-item-active' : 'menu-item-inactive' }}"
                    :class="{ 'xl:justify-center': !sidebarExpand }">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zM4 21a8 8 0 0 1 16 0H4z"
                                fill="currentColor" />
                        </svg>
                    </span>
                    <span x-show="sidebarExpand">Prospek</span>
                </a>

                <a href="{{ route('prospects.pipeline') }}"
                    class="menu-item group {{ request()->routeIs('prospects.pipeline') ? 'menu-item-active' : 'menu-item-inactive' }}"
                    :class="{ 'xl:justify-center': !sidebarExpand }">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 4h16v4H4V4zm0 6h10v4H4v-4zm0 6h16v4H4v-4z" fill="currentColor" />
                        </svg>
                    </span>
                    <span x-show="sidebarExpand">Pipeline</span>
                </a>

                @can('access-performance')
                    <a href="{{ route('prospects.performance') }}"
                        class="menu-item group {{ request()->routeIs('prospects.performance') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        :class="{ 'xl:justify-center': !sidebarExpand }">
                        <span class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 19h16v2H4v-2zM7 10h3v7H7v-7zm7-4h3v11h-3V6z" fill="currentColor" />
                            </svg>
                        </span>
                        <span x-show="sidebarExpand">Kinerja</span>
                    </a>
                @endcan

                @can('access-chat-reviews')
                    <a href="{{ route('chat-reviews.index') }}"
                        class="menu-item group {{ request()->routeIs('chat-reviews.*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        :class="{ 'xl:justify-center': !sidebarExpand }">
                        <span class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 4h16v12H7l-3 3V4z" fill="currentColor" />
                            </svg>
                        </span>
                        <span x-show="sidebarExpand">Tinjauan Obrolan</span>
                    </a>
                @endcan

                @can('access-knowledge-queue')
                    <a href="{{ route('knowledge-queue.index') }}"
                        class="menu-item group {{ request()->routeIs('knowledge-queue.*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        :class="{ 'xl:justify-center': !sidebarExpand }">
                        <span class="menu-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 2 3 7l9 5 9-5-9-5zm0 8-9-5v11l9 5 9-5V5l-9 5z" fill="currentColor" />
                            </svg>
                        </span>
                        <span x-show="sidebarExpand">Antrian Pengetahuan</span>
                    </a>
                @endcan
            </nav>
            <div x-show="sidebarExpand" class="mt-6 rounded-[24px] border border-white/10 bg-white/5 p-4">
                <p class="text-xs uppercase tracking-[0.24em] text-[#d9c995]/70">Frontend Stack</p>
                <p class="mt-2 text-sm leading-6 text-[#d9c995]">
                    React, TypeScript, Wouter, TanStack Query, dan komponen shadcn-style di atas backend Laravel yang
                    sama.
                </p>
            </div>
        </aside>

        <div class="min-w-0 flex-1 space-y-4">
            <header
                class="sticky top-4 z-30 rounded-[28px] border border-white/10 bg-[rgba(10,13,18,0.72)] px-5 py-4 backdrop-blur-xl">
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button"
                        class="mobile-only-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-[#d9c995]"
                        @click="sidebarOpen = !sidebarOpen">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none">
                            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" />
                        </svg>
                    </button>

                    <button type="button"
                        class="desktop-only-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-[#d9c995]"
                        @click="sidebarExpand = !sidebarExpand">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none">
                            <path d="M4 7h16M4 12h10M4 17h16" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" />
                        </svg>
                    </button>

                    <div class="hidden items-center gap-3 md:flex">
                        <img src="{{ asset('brand/Logo SG-WEB111.png') }}" alt="SGB" class="h-9 w-9 rounded-2xl" />
                        <p class="text-xs uppercase tracking-[0.24em] text-[#d9c995]/70">Sales Command Center</p>
                    </div>

                    {{-- <div class="desktop-only-block relative min-w-[260px] flex-1">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#d9c995]/70">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none">
                                <path d="m21 21-4.3-4.3M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14z" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </span>
                        <input type="text" placeholder="Search or type command..."
                            class="h-11 w-full rounded-2xl border border-white/10 bg-white/[0.04] pl-12 pr-24 text-sm text-[#fff2a2] placeholder:text-[#d9c995]/60 focus:border-white/20 focus:outline-none focus:ring-4 focus:ring-white/5" />
                        <kbd
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl border border-white/10 bg-white/[0.06] px-2 py-1 text-xs text-[#d9c995]">Ctrl
                            K</kbd>
                    </div> --}}

                    <div class="ml-auto flex items-center gap-2">

                        <div
                            class="flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.06] px-2 py-1">
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold text-slate-900">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span
                                class="desktop-only-inline pr-1 text-sm font-medium text-[#fff2a2]">{{ auth()->user()->name }}</span>
                        </div>

                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex h-10 items-center rounded-2xl border border-white/10 bg-white/[0.06] px-3 text-sm font-medium text-[#fff2a2] hover:bg-white/10">
                                <span class="hidden sm:inline">Logout</span>
                                <span class="sm:hidden">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none">
                                        <path d="M10 17l5-5-5-5" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M15 12H3" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                        <path d="M21 3v18" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                    </svg>
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main>
                @if (session('status'))
                    <div class="alert">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>
