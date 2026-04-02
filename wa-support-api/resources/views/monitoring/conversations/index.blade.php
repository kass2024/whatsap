<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">{{ __('Inbox') }}</h1>
            <p class="pcv-topbar-meta mt-0.5">{{ __('All conversations visible to your role.') }}</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="pcv-panel overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Last message') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Assigned') }}</th>
                        @if(auth()->user()->isAdmin())
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Access') }}</th>
                        @endif
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($conversations as $c)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-semibold text-slate-900">{{ $c->customer_name ?: '+'.$c->phone }}</span>
                                @if($c->unread_count > 0)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-[#427431] text-white">{{ $c->unread_count }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 max-w-md truncate">{{ $c->last_message_preview ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $c->assignedAgent?->name ?? __('Unassigned') }}</td>
                            @if(auth()->user()->isAdmin())
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if(!empty($restrictedSet[$c->phone]))
                                        <span class="pcv-badge pcv-badge-admin"><i class="fas fa-shield-halved text-[10px]" aria-hidden="true"></i> {{ __('Admin only') }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">{{ __('Standard') }}</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('conversations.show', $c) }}" class="text-sm font-semibold text-[#427431] hover:text-[#356a2a]">{{ __('Open') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $conversations->links() }}</div>
    </div>
</x-app-layout>
