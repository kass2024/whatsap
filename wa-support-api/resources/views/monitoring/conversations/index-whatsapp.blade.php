<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">{{ __('Inbox') }}</h1>
            <p class="pcv-topbar-meta mt-0.5">{{ __('All conversations visible to your role.') }}</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto" x-data="{ 
        search: '', 
        conversations: {!! $conversations->toJson() !!},
        totalConversations: {{ $conversations->total() }},
        currentPage: {{ $conversations->currentPage() }},
        lastUpdate: '{{ now()->toISOString() }}',
        filteredConversations: {!! $conversations->toJson() !!}
    }" x-init="
        // Debug: Log data
        console.log('Conversations loaded:', conversations.length);
        console.log('Total conversations:', totalConversations);
        console.log('Conversations data:', conversations);
        
        // Initialize filteredConversations
        if (search === '') {
            filteredConversations = conversations;
        }
        
        // Watch for search changes
        $watch('search', (value) => {
            if (value === '') {
                filteredConversations = conversations;
            } else {
                const searchTerm = value.toLowerCase();
                filteredConversations = conversations.filter(c => 
                    (c.customer_name && c.customer_name.toLowerCase().includes(searchTerm)) ||
                    (c.phone && c.phone.includes(searchTerm)) ||
                    (c.last_message_preview && c.last_message_preview.toLowerCase().includes(searchTerm))
                );
            }
        });
        
        // Auto-refresh every 30 seconds (reduced from 10 seconds for better performance)
        setInterval(() => {
            const lastInteraction = localStorage.getItem('lastChatInteraction');
            if (!lastInteraction || Date.now() - new Date(lastInteraction) > 30000) {
                window.location.reload();
            }
        }, 30000);
    ">
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
                            x-model="search" 
                            @click="localStorage.setItem('lastChatInteraction', Date.now().toString())"
                            placeholder="{{ __('Search by name, phone, or message...') }}" 
                            class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#427431] focus:border-transparent"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">
                        {{ __('Found') }}: <span x-text="filteredConversations.length" class="font-semibold text-[#427431]"></span> / <span x-text="totalConversations"></span>
                    </span>
                    <button 
                        @click="search = ''" 
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
                        <template x-for="c in filteredConversations">
                            <a :href="'/conversations/' + c.id" 
                               @click="localStorage.setItem('lastChatInteraction', Date.now().toString())"
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
                                                        <span x-text="c.customer_name ? c.customer_name : '+' + c.phone"></span>
                                                    </div>
                                                    <div class="text-sm text-gray-500 truncate">
                                                        <span x-text="c.last_message_preview ? c.last_message_preview : '—'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between mt-1">
                                                <div class="text-xs text-gray-400">
                                                    <span x-text="new Date(c.last_message_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                                                </div>
                                                <div x-show="c.unread_count > 0" class="bg-[#427431] text-white text-xs px-2 py-1 rounded-full">
                                                    <span x-text="c.unread_count"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div x-show="c.is_emergency" class="mt-2 bg-red-100 border border-red-200 text-red-800 px-2 py-1 rounded text-xs font-semibold">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        {{ __('EMERGENCY') }}
                                    </div>
                                </div>
                            </a>
                        </template>
                        
                        <div x-show="filteredConversations.length === 0" class="p-8 text-center text-gray-500">
                            <i class="fas fa-search text-4xl mb-4"></i>
                            <p class="text-lg font-medium">{{ __('No conversations found') }}</p>
                            <p class="text-sm mt-2">{{ __('Try adjusting your search terms') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Preview/Content -->
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden h-full">
                    <div class="bg-[#f0f2f5] px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">{{ __('Conversation Preview') }}</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">{{ __('Total') }}: <span x-text="totalConversations"></span></span>
                            <div x-show="totalConversations > 0" class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold">
                                <span x-text="filteredConversations.filter(c => c.unread_count > 0).reduce((sum, c) => sum + c.unread_count, 0)"></span> {{ __('unread') }}
                            </div>
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
        function performSearch(searchTerm) {
            if (window.Alpine && window.Alpine.store) {
                window.Alpine.store('search', searchTerm);
                // Trigger a re-render by updating the conversations data
                // In a real implementation, this would make an API call
                console.log('Searching for:', searchTerm);
            }
        }
    </script>
</x-app-layout>
