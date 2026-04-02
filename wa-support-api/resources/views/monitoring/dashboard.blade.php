<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">{{ __('Operations dashboard') }}</h1>
            <p class="pcv-topbar-meta mt-0.5">{{ __('WhatsApp support — at-a-glance metrics and recent threads.') }}</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        <div class="pcv-kpi-grid">
            <div class="pcv-kpi">
                <div class="pcv-kpi-label">{{ __('Visible conversations') }}</div>
                <div class="pcv-kpi-value">{{ number_format($total) }}</div>
                <p class="pcv-kpi-hint">{{ __('Threads you can access in the inbox') }}</p>
            </div>
            <div class="pcv-kpi">
                <div class="pcv-kpi-label">{{ __('Open') }}</div>
                <div class="pcv-kpi-value">{{ number_format($openCount) }}</div>
                <p class="pcv-kpi-hint">{{ __('Status: open') }}</p>
            </div>
            <div class="pcv-kpi">
                <div class="pcv-kpi-label">{{ __('Unread (approx.)') }}</div>
                <div class="pcv-kpi-value">{{ number_format($unreadSum) }}</div>
                <p class="pcv-kpi-hint">{{ __('Across visible conversations') }}</p>
            </div>
            <div class="pcv-kpi">
                <div class="pcv-kpi-label">{{ __('Messages today') }}</div>
                <div class="pcv-kpi-value">{{ number_format($messagesToday) }}</div>
                <p class="pcv-kpi-hint">{{ __('All directions, stored in system') }}</p>
            </div>
            @if(auth()->user()->isAdmin() && count($restrictedPhones ?? []) > 0)
                <div class="pcv-kpi">
                    <div class="pcv-kpi-label">{{ __('Admin-only threads') }}</div>
                    <div class="pcv-kpi-value">{{ number_format($restrictedConversationsCount) }}</div>
                    <p class="pcv-kpi-hint">
                        <a href="{{ route('settings.admin-phones') }}" class="font-medium text-[#427431] hover:underline">{{ __('Manage numbers') }}</a>
                    </p>
                </div>
            @endif
        </div>

        <div class="pcv-panel">
            <div class="pcv-panel-head">
                <h2>{{ __('Recent activity') }}</h2>
                <a href="{{ route('conversations.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#427431] hover:text-[#356a2a]">
                    {{ __('Open inbox') }}
                    <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                </a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($recent as $c)
                    <li class="px-4 py-3.5 hover:bg-slate-50/80 flex justify-between gap-4 transition-colors">
                        <a href="{{ route('conversations.show', $c) }}" class="flex-1 min-w-0 group">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-slate-900 group-hover:text-[#427431]">{{ $c->customer_name ?: '+'.$c->phone }}</span>
                                @if(auth()->user()->isAdmin() && isset($restrictedPhones) && in_array($c->phone, $restrictedPhones, true))
                                    <span class="pcv-badge pcv-badge-admin"><i class="fas fa-shield-halved text-[10px]" aria-hidden="true"></i> {{ __('Admin only') }}</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-500 truncate mt-0.5">{{ $c->last_message_preview ?: '—' }}</p>
                            @if($c->assignedAgent)
                                <p class="text-xs text-slate-400 mt-1">{{ __('Assigned') }}: {{ $c->assignedAgent->name }}</p>
                            @endif
                        </a>
                        @if($c->unread_count > 0)
                            <span class="shrink-0 inline-flex items-center justify-center min-w-[1.75rem] h-7 px-2 rounded-full text-xs font-bold bg-[#427431] text-white">{{ $c->unread_count }}</span>
                        @endif
                    </li>
                @empty
                    <li class="px-4 py-12 text-center text-slate-500">
                        <p class="font-medium text-slate-700">{{ __('No conversations yet') }}</p>
                        <p class="text-sm mt-1">{{ __('Incoming WhatsApp messages will appear here once your webhook is configured.') }}</p>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
