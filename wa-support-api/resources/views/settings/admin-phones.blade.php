<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">{{ __('Admin-only numbers') }}</h1>
            <p class="pcv-topbar-meta mt-0.5">{{ __('Conversations for these WhatsApp numbers are hidden from agents and visible only to administrators.') }}</p>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <div class="pcv-panel mb-6">
            <div class="pcv-panel-head">
                <h2>{{ __('Restricted phone list') }}</h2>
                <span class="text-xs font-medium text-slate-500">{{ trans_choice(':count number|:count numbers', $count) }}</span>
            </div>
            <div class="p-5 space-y-4">
                <p class="text-sm text-slate-600 leading-relaxed">
                    {{ __('Enter one number per line. Use E.164 format (e.g. 447911123456). Optional label after a comma:') }}
                    <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">447911123456, VIP customer</code>
                </p>
                <form method="POST" action="{{ route('settings.admin-phones.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="phones" class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('Numbers') }}</label>
                        <textarea
                            id="phones"
                            name="phones"
                            rows="14"
                            class="w-full rounded-lg border border-slate-300 shadow-sm focus:border-[#427431] focus:ring-[#427431] font-mono text-sm p-3"
                            placeholder="{{ __('447700900001') }}&#10;447700900002, {{ __('Finance') }}"
                        >{{ old('phones', $phoneLines) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('phones')" />
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-primary-button type="submit">{{ __('Save list') }}</x-primary-button>
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-[#427431]">{{ __('Back to dashboard') }}</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <p class="font-medium">{{ __('How it works') }}</p>
            <ul class="mt-2 list-disc list-inside space-y-1 text-amber-900/90">
                <li>{{ __('Agents will not see these chats in the inbox or API list.') }}</li>
                <li>{{ __('Admins can still open, assign, and reply as usual.') }}</li>
                <li>{{ __('Lines starting with # are treated as comments.') }}</li>
            </ul>
        </div>
    </div>
</x-app-layout>
