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

        <!-- Search Bar -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="searchInput"
                            placeholder="{{ __('Search by name, phone, or message...') }}" 
                            class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#427431] focus:border-transparent"
                            onkeyup="filterConversations()"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">
                        {{ __('Found') }}: <span id="foundCount" class="font-semibold text-[#427431]">{{ $conversations->count() }}</span> / <span>{{ $conversations->total() }}</span>
                    </span>
                    <button 
                        onclick="clearSearch()" 
                        class="text-sm text-gray-400 hover:text-gray-600 underline"
                    >
                        {{ __('Clear') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- WhatsApp Web Style Chat List -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Chat List -->
            <div class="lg:col-span-1">
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-[#f0f2f5] px-4 py-3 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">{{ __('Chats') }}</h3>
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <span>{{ __('Live') }}: <span class="inline-flex w-2 h-2 bg-green-500 rounded-full animate-pulse"></span></span>
                                <span class="text-xs">{{ __('Auto-updates enabled') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-y-auto" style="max-height: calc(100vh - 200px);">
                        @if($conversations->count() > 0)
                            <div id="conversationList">
                                @foreach($conversations as $c)
                                    <div class="conversation-item" 
                                         data-name="{{ strtolower($c->customer_name ?: '+'.$c->phone) }}" 
                                         data-phone="{{ $c->phone }}"
                                         data-message="{{ strtolower($c->last_message_preview ?: '') }}">
                                        <a href="{{ route('conversations.show', $c) }}" 
                                           class="block hover:bg-[#f5f5f5] transition-colors border-b border-gray-100">
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
                                                                {{ $c->last_message_at ? $c->last_message_at->format('H:i') : '—' }}
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
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-8 text-center text-gray-500">
                                <i class="fas fa-comments text-6xl mb-4"></i>
                                <p class="text-lg font-medium">{{ __('No conversations found') }}</p>
                                <p class="text-sm mt-2">{{ __('Check your database connection and permissions') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Chat Preview/Content -->
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden h-full">
                    <div class="bg-[#f0f2f5] px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">{{ __('Conversation Preview') }}</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">{{ __('Total') }}: {{ $conversations->total() }}</span>
                            @if($conversations->count() > 0)
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold">
                                    {{ $conversations->sum('unread_count') }} {{ __('unread') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-comments text-6xl mb-4"></i>
                        <p class="text-lg font-medium">{{ __('Select a conversation to view details') }}</p>
                        <p class="text-sm mt-2">{{ __('Click on any chat in the list to open conversation') }}</p>
                        <p class="text-xs mt-4 text-gray-400">{{ __('Live updates and search are active') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">{{ $conversations->links() }}</div>
    </div>

    <script>
        function filterConversations() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const conversationItems = document.querySelectorAll('.conversation-item');
            let visibleCount = 0;

            conversationItems.forEach(item => {
                const name = item.dataset.name;
                const phone = item.dataset.phone;
                const message = item.dataset.message;

                const matches = name.includes(searchTerm) || 
                                phone.includes(searchTerm) || 
                                message.includes(searchTerm);

                if (matches) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            document.getElementById('foundCount').textContent = visibleCount;
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            filterConversations();
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            const lastInteraction = localStorage.getItem('lastChatInteraction');
            if (!lastInteraction || Date.now() - new Date(lastInteraction) > 30000) {
                window.location.reload();
            }
        }, 30000);

        // Track user interaction
        document.addEventListener('click', () => {
            localStorage.setItem('lastChatInteraction', Date.now().toString());
        });
    </script>
</x-app-layout>
