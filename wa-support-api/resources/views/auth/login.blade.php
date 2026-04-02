<x-guest-layout>
    <h2>{{ __('Sign in to your account') }}</h2>
    <p class="parrot-subtitle">{{ __('Access the WhatsApp monitoring dashboard and inbox.') }}</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" id="loginForm" autocomplete="off">
        @csrf

        <div class="parrot-form-group">
            <label for="email">{{ __('Email address') }}</label>
            <div class="parrot-input-shell">
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    autocomplete="username" placeholder="{{ __('you@company.com') }}">
            </div>
            @error('email')
                <p class="mt-2 text-sm" style="color:var(--pcv-danger)">{{ $message }}</p>
            @enderror
        </div>

        <div class="parrot-form-group">
            <label for="password">{{ __('Password') }}</label>
            <div class="parrot-input-shell">
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    placeholder="••••••••">
                <button type="button" class="parrot-pw-toggle" id="passwordToggle" aria-label="{{ __('Toggle password visibility') }}">
                    <i class="fas fa-eye" id="passwordIcon" aria-hidden="true"></i>
                </button>
            </div>
            @error('password')
                <p class="mt-2 text-sm" style="color:var(--pcv-danger)">{{ $message }}</p>
            @enderror
        </div>

        <div class="parrot-row-options">
            <label>
                <input type="checkbox" name="remember" id="remember_me">
                <span>{{ __('Remember me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
            @endif
        </div>

        <button type="submit" class="parrot-btn-login" id="submitBtn">
            <i class="fas fa-right-to-bracket" aria-hidden="true"></i>{{ __('Log in') }}
        </button>
    </form>

    <div class="parrot-footer-note">
        {{ __('Secure access for agents and administrators') }} · © {{ date('Y') }} {{ config('app.name') }}
    </div>

    <script>
        (function () {
            var passwordInput = document.getElementById('password');
            var passwordToggle = document.getElementById('passwordToggle');
            var passwordIcon = document.getElementById('passwordIcon');
            var submitBtn = document.getElementById('submitBtn');
            var form = document.getElementById('loginForm');
            if (!passwordToggle || !passwordInput) return;
            passwordToggle.addEventListener('click', function () {
                var isPw = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPw ? 'text' : 'password');
                passwordIcon.classList.toggle('fa-eye', !isPw);
                passwordIcon.classList.toggle('fa-eye-slash', isPw);
            });
            if (form && submitBtn) {
                form.addEventListener('submit', function () { submitBtn.disabled = true; });
            }
        })();
    </script>
</x-guest-layout>
