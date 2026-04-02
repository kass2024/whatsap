<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'WA Support') }}@isset($title) — {{ $title }}@endisset</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('css/parrot-app.css') }}?v=2">
    @stack('head')
</head>
<body class="font-sans antialiased text-slate-800" style="font-family: Inter, ui-sans-serif, system-ui, sans-serif;">
    <div class="pcv-app-shell" x-data="{ sidebarOpen: false }">
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
            @click="sidebarOpen = false"
        ></div>
        @include('layouts.sidebar')

        <div class="pcv-main-wrap">
            <header class="pcv-topbar">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" class="pcv-mobile-nav-btn lg:hidden" @click="sidebarOpen = !sidebarOpen" aria-label="{{ __('Menu') }}">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="min-w-0">
                        @isset($header)
                            {{ $header }}
                        @else
                            <h1 class="truncate">{{ $pageTitle ?? config('app.name') }}</h1>
                        @endisset
                    </div>
                </div>
                @isset($headerActions)
                    <div class="flex items-center gap-2 shrink-0">{{ $headerActions }}</div>
                @endisset
            </header>

            <main class="pcv-content">
                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                        {{ session('status') }}
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
