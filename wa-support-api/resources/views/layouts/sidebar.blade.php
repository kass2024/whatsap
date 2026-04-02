@php
    $isAdmin = auth()->user()->isAdmin();
@endphp

<aside class="pcv-sidebar" :class="{ 'is-open': sidebarOpen }">
    <div class="pcv-sidebar-brand">
        <a href="{{ route('dashboard') }}" @click="sidebarOpen = false">
            <div class="pcv-sidebar-logo">
                @include('partials.wa-brand-mark')
            </div>
            <div>
                <div class="pcv-sidebar-title">{{ config('app.name', 'WA Support') }}</div>
                <div class="pcv-sidebar-sub">{{ __('Operations') }}</div>
            </div>
        </a>
    </div>

    <nav class="pcv-nav" aria-label="{{ __('Main navigation') }}">
        <div class="pcv-nav-section">{{ __('Overview') }}</div>
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-chart-pie" aria-hidden="true"></i>
            {{ __('Dashboard') }}
        </a>
        <a href="{{ route('conversations.index') }}" class="{{ request()->routeIs('conversations.*') ? 'active' : '' }}">
            <i class="fas fa-inbox" aria-hidden="true"></i>
            {{ __('Inbox') }}
        </a>

        @if($isAdmin)
            <div class="pcv-nav-section mt-4">{{ __('Administration') }}</div>
            <a href="{{ route('settings.admin-phones') }}" class="{{ request()->routeIs('settings.admin-phones') ? 'active' : '' }}">
                <i class="fas fa-shield-halved" aria-hidden="true"></i>
                {{ __('Admin-only numbers') }}
            </a>
        @endif

        <div class="pcv-nav-section mt-4">{{ __('Account') }}</div>
        <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <i class="fas fa-user-gear" aria-hidden="true"></i>
            {{ __('Profile') }}
        </a>
    </nav>

    <div class="pcv-sidebar-footer">
        <div class="pcv-sidebar-user">{{ auth()->user()->name }}</div>
        <div>{{ auth()->user()->email }}</div>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-left w-full text-sm text-white/70 hover:text-white underline-offset-2 hover:underline bg-transparent border-0 cursor-pointer p-0">
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</aside>
