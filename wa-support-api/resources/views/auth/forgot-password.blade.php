<x-guest-layout>
    <h2>{{ __('Reset password') }}</h2>
    <p class="parrot-subtitle">{{ __('Forgot your password? No problem — enter your email and we will send a reset link.') }}</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
        <div class="parrot-error" role="alert">
            <i class="fas fa-circle-exclamation" style="margin-top:2px" aria-hidden="true"></i>
            <div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="parrot-form-group">
            <label for="email">{{ __('Email') }}</label>
            <div class="parrot-input-shell">
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
        </div>
        <button type="submit" class="parrot-btn-login">{{ __('Email password reset link') }}</button>
    </form>

    <div class="parrot-footer-note">
        <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">{{ __('Back to sign in') }}</a>
    </div>
</x-guest-layout>
