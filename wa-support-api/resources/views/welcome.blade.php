<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ config('app.name') }} — WhatsApp Cloud API monitoring for customer support teams.">
    <title>{{ config('app.name', 'WA Support') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/parrot-guest.css') }}?v=1">
</head>
<body class="parrot-welcome">
    <div class="parrot-welcome-inner">
        <div class="parrot-welcome-hero">
            <div class="parrot-welcome-brand">
                <h1>{{ __('WhatsApp support operations') }}</h1>
                <p>
                    {{ __('Monitor customer chats, enforce Meta’s 24-hour messaging window, send approved templates when needed, and assign agents — from the web dashboard or the mobile app. Same visual language as Parrot admin tools: green brand, glass cards, split-panel clarity.') }}
                </p>
                <div class="parrot-welcome-actions">
                    @if (Route::has('login'))
                        <a class="parrot-btn-primary" href="{{ route('login') }}">
                            <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
                            {{ __('Agent / admin login') }}
                        </a>
                    @endif
                    <a class="parrot-btn-ghost" href="#endpoints">
                        <i class="fas fa-network-wired" aria-hidden="true"></i>
                        {{ __('See endpoints') }}
                    </a>
                </div>
            </div>
            <div class="parrot-welcome-side" id="endpoints">
                <div>
                    <h2>{{ __('Endpoints') }}</h2>
                    <div class="parrot-feature">
                        <div class="parrot-feature-icon"><i class="fas fa-plug" aria-hidden="true"></i></div>
                        <div>
                            <h3>{{ __('REST API (Sanctum)') }}</h3>
                            <p><span class="parrot-code">{{ url('/api') }}</span> — {{ __('Flutter & integrations') }}</p>
                        </div>
                    </div>
                    <div class="parrot-feature">
                        <div class="parrot-feature-icon"><i class="fas fa-cloud-arrow-down" aria-hidden="true"></i></div>
                        <div>
                            <h3>{{ __('Meta webhook') }}</h3>
                            <p><span class="parrot-code">{{ url('/webhook/whatsapp') }}</span> — {{ __('WhatsApp Cloud incoming events') }}</p>
                        </div>
                    </div>
                    <div class="parrot-feature">
                        <div class="parrot-feature-icon"><i class="fas fa-inbox" aria-hidden="true"></i></div>
                        <div>
                            <h3>{{ __('Web inbox') }}</h3>
                            <p>{{ __('After login: dashboard, conversations, templates, and media — aligned with Parrot-style monitoring.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="parrot-welcome-footer">
            <span class="parrot-status-dot">
                <span class="dot" aria-hidden="true"></span>
                {{ __('API server is running') }}
            </span>
            <span aria-hidden="true"> · </span>
            {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
