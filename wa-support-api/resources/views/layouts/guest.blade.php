<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('app.name') }} — WhatsApp customer support for agents and admins.">
    <title>@yield('title', __('Sign in').' | '.config('app.name', 'WA Support'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('css/parrot-guest.css') }}?v=1">
    @stack('head')
</head>
<body class="parrot-guest">
    <div class="parrot-shell">
        <aside class="parrot-brand-panel" aria-label="{{ __('Product') }}">
            <div class="parrot-logo-wrap" aria-hidden="true">
                @include('partials.wa-brand-mark')
            </div>
            <h1>{{ config('app.name', 'WA Support') }}</h1>
            <p class="lead">
                {!! __('Monitor <strong>WhatsApp</strong> conversations in real time, enforce the <strong>24-hour session</strong> window, send templates when needed, and keep your team aligned — same design language as Parrot admin tools.') !!}
            </p>
            <a class="parrot-cta-pill" href="{{ url('/') }}">
                <i class="fas fa-house" aria-hidden="true"></i> {{ __('Back to home') }}
            </a>
        </aside>

        <section class="parrot-form-panel">
            <div class="parrot-form-panel-inner">
                <div class="parrot-mobile-brand">
                    <div class="logo-sm">
                        @include('partials.wa-brand-mark')
                    </div>
                    <h2>{{ config('app.name', 'WA Support') }}</h2>
                    <p>{{ __('Secure sign in') }}</p>
                </div>

                {{ $slot }}
            </div>
        </section>
    </div>
</body>
</html>
