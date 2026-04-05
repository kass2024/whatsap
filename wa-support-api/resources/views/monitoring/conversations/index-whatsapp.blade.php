<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">{{ __('Inbox') }}</h1>
            <p class="pcv-topbar-meta mt-0.5">{{ __('All conversations visible to your role.') }}</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <!-- WhatsApp Web Style Chat List -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Chat List -->
            <div class="lg:col-span-1">
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-[#f0f2f5] px-4 py-3 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800">{{ __('Chats') }}</h3>
                    </div>
                    <div class="overflow-y-auto" style="max-height: calc(100vh - 200px);">
                        @foreach ($conversations as $c)
                            <a href="{{ route('conversations.show', $c) }}" 
                               class="block hover:bg-[#f5f5f5] transition-colors border-b border-gray-100 {{ $c->unread_count > 0 ? 'bg-[#e6f3ff]' : '' }}">
                                <div class="p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center mb-1">
                                                <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                                                    <i class="fas fa-user text-gray-600"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-semibold text-gray-900 truncate">
                                                        {{ $c->customer_name ?: '+'.$c->phone }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 truncate">
                                                        {{ $c->last_message_preview ?: '—' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between mt-1">
                                                <div class="text-xs text-gray-400">
                                                    {{ $c->last_message_at->format('H:i') }}
                                                </div>
                                                @if($c->unread_count > 0)
                                                    <span class="bg-[#427431] text-white text-xs px-2 py-1 rounded-full">
                                                        {{ $c->unread_count }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @if($c->is_emergency)
                                        <div class="mt-2 bg-red-100 border border-red-200 text-red-800 px-2 py-1 rounded text-xs font-semibold">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            {{ __('EMERGENCY') }}
                                        </div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Chat Preview/Content -->
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden h-full">
                    <div class="bg-[#f0f2f5] px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">{{ __('Conversation Preview') }}</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">{{ __('Total') }}: {{ $conversations->count() }}</span>
                            @if($conversations->count() > 0)
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold">
                                    {{ $conversations->where('unread_count', '>', 0)->sum('unread_count') }} {{ __('unread') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-comments text-6xl mb-4"></i>
                        <p class="text-lg font-medium">{{ __('Select a conversation to view details') }}</p>
                        <p class="text-sm mt-2">{{ __('Click on any chat in the list to open the conversation') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">{{ $conversations->links() }}</div>
    </div>
</x-app-layout>
