<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between w-full">
            <div class="min-w-0">
                <a href="{{ route('conversations.index') }}" class="text-sm font-medium text-[#427431] hover:underline">← {{ __('Inbox') }}</a>
                <h1 class="text-lg font-bold text-slate-900 mt-1 truncate">
                    {{ $conversation->customer_name ?: '+'.$conversation->phone }}
                </h1>
                @if($conversation->is_emergency)
                    <div class="inline-flex mt-2 bg-red-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        {{ __('EMERGENCY CONVERSATION') }}
                    </div>
                @endif
                @if(auth()->user()->isAdmin() && $isAdminOnlyPhone)
                    <span class="inline-flex mt-2 pcv-badge pcv-badge-admin"><i class="fas fa-shield-halved text-[10px]" aria-hidden="true"></i> {{ __('Admin-only conversation — hidden from agents') }}</span>
                @endif
            </div>
            <p class="text-sm text-slate-600 shrink-0">
                @if($session['active'])
                    <span class="text-emerald-700 font-semibold">{{ __('24h session active') }}</span>
                @else
                    <span class="text-amber-700 font-semibold">{{ __('Session inactive — use templates only') }}</span>
                @endif
            </p>
        </div>
    </x-slot>

    <div class="py-2" x-data="{ auto: true }" x-init="setInterval(() => { if (auto) window.location.reload(); }, 10000)">
        <div class="max-w-6xl mx-auto">
            @if (session('status'))
                <div class="p-4 rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm">{{ session('status') }}</div>
            @endif

            @if(auth()->user()->isAdmin() && $isAdminOnlyPhone)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    {{ __('This number is on the restricted list. Agents cannot see this thread in inbox or via API.') }}
                    <a href="{{ route('settings.admin-phones') }}" class="font-semibold text-[#427431] hover:underline ml-1">{{ __('Edit list') }}</a>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Chat Messages Area -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <!-- Chat Header -->
                        <div class="bg-[#f0f2f5] px-4 py-3 border-b border-gray-200 rounded-t-lg flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-gray-600"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">
                                        {{ $conversation->customer_name ?: '+'.$conversation->phone }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        @if($conversation->is_emergency)
                                            <span class="text-red-600 font-semibold">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                {{ __('Emergency Priority') }}
                                            </span>
                                        @else
                                            {{ __('Normal Priority') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" x-model="auto" checked class="rounded border-gray-300">
                                    {{ __('Auto-refresh every 10s') }}
                                </label>
                                <button type="button" @click="window.location.reload()" class="text-indigo-600 hover:underline">{{ __('Refresh now') }}</button>
                            </div>
                        </div>

                        <!-- Messages Area -->
                        <div class="p-4 space-y-3 overflow-y-auto" style="max-height: calc(100vh - 300px);" id="thread">
                            @foreach ($messages as $m)
                                <div class="flex {{ $m->sender_type === 'agent' ? 'justify-end' : 'justify-start' }} mb-4">
                                    <div class="flex {{ $m->sender_type === 'agent' ? 'flex-row-reverse' : 'flex-row' }} items-end max-w-[85%]">
                                        <!-- Avatar -->
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $m->sender_type === 'agent' ? 'ml-3' : 'mr-3' }} {{ $m->sender_type === 'agent' ? 'bg-indigo-600' : 'bg-gray-400' }}">
                                            <i class="fas fa-user text-white text-sm"></i>
                                        </div>
                                        
                                        <!-- Message Bubble -->
                                        <div class="max-w-full {{ $m->sender_type === 'agent' ? 'mr-3' : 'ml-3' }}">
                                            <div class="rounded-lg px-4 py-2 {{ $m->sender_type === 'agent' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900' }} shadow-sm">
                                                @if($m->message_type === 'image' && $m->media_path)
                                                    <img src="{{ Storage::disk($m->media_disk)->url($m->media_path) }}" alt="" class="max-w-full rounded max-h-48 object-contain mb-2">
                                                @elseif($m->message_type === 'audio' && $m->media_path)
                                                    <audio controls class="max-w-full mb-2" src="{{ Storage::disk($m->media_disk)->url($m->media_path) }}"></audio>
                                                @elseif(in_array($m->message_type, ['video','document']) && $m->media_path)
                                                    <a href="{{ Storage::disk($m->media_disk)->url($m->media_path) }}" class="underline mb-2 block" target="_blank">{{ $m->file_name ?: __('Download') }}</a>
                                                @endif
                                                @if($m->content)
                                                    <p class="text-sm whitespace-pre-wrap">{{ $m->content }}</p>
                                                @endif
                                                @if($m->message_type === 'template')
                                                    <p class="text-xs opacity-90 mt-1">{{ __('Template') }}: {{ $m->template_name }} ({{ $m->template_language }})</p>
                                                @endif
                                                <p class="text-xs mt-1 {{ $m->sender_type === 'agent' ? 'text-indigo-200' : 'text-gray-400' }}">
                                                    {{ $m->created_at->format('Y-m-d H:i') }} · {{ $m->sender_type }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Message Input Area -->
                        @if($session['active'])
                            <div class="bg-white border-t border-gray-200 p-4">
                                <h3 class="font-medium text-gray-900 mb-4">{{ __('Send message') }}</h3>
                                <form method="POST" action="{{ route('conversations.send-text', $conversation) }}" class="space-y-3">
                                    @csrf
                                    <div class="flex gap-3">
                                        <textarea name="text" rows="2" class="flex-1 rounded-md border-gray-300 shadow-sm" placeholder="{{ __('Type a message…') }}" required>{{ old('text') }}</textarea>
                                        <x-primary-button type="submit" class="flex-shrink-0">{{ __('Send') }}</x-primary-button>
                                    </div>
                                    <x-input-error :messages="$errors->get('text')" />
                                </form>
                                <form method="POST" action="{{ route('conversations.send-media', $conversation) }}" enctype="multipart/form-data" class="space-y-3 border-t pt-4">
                                    <div class="flex gap-3 items-end">
                                        <div class="flex-1">
                                            <label class="block text-sm font-medium text-gray-700">{{ __('Image / audio / video / document') }}</label>
                                            <input type="file" name="file" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700">
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-sm font-medium text-gray-700">{{ __('Caption (optional)') }}</label>
                                            <input type="text" name="caption" value="{{ old('caption') }}" placeholder="{{ __('Caption (optional)') }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                        </div>
                                        <x-secondary-button type="submit" class="flex-shrink-0">{{ __('Send file') }}</x-secondary-button>
                                    </div>
                                    <x-input-error :messages="$errors->get('media')" />
                                </form>
                            </div>
                        @else
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <p class="text-sm text-amber-900 mb-3">{{ __('Outside of 24-hour window you can only send approved WhatsApp template messages.') }}</p>
                                <form method="POST" action="{{ route('conversations.send-template', $conversation) }}" class="flex flex-wrap gap-3 items-end">
                                    @csrf
                                    <div>
                                        <label class="text-xs font-medium text-gray-700">{{ __('Template name') }}</label>
                                        <input type="text" name="template_name" value="{{ old('template_name') }}" required class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-700">{{ __('Language') }}</label>
                                        <input type="text" name="template_language" value="{{ old('template_language', 'en') }}" required class="mt-1 rounded-md border-gray-300 shadow-sm text-sm w-24">
                                    </div>
                                    <x-primary-button type="submit">{{ __('Send template') }}</x-primary-button>
                                </form>
                                <x-input-error :messages="$errors->get('template')" />
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Side Panel -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Assignment Panel -->
                    @if(auth()->user()->isAdmin())
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <h3 class="font-medium text-gray-900 mb-3">{{ __('Assign Agent') }}</h3>
                            <form method="POST" action="{{ route('conversations.assign', $conversation) }}" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Assign to agent') }}</label>
                                    <select name="assigned_to" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">{{ __('Unassigned') }}</option>
                                        @foreach ($agents as $a)
                                            <option value="{{ $a->id }}" @selected($conversation->assigned_to == $a->id)>{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-primary-button type="submit">{{ __('Save Assignment') }}</x-primary-button>
                            </form>
                        </div>
                    @endif

                    <!-- Conversation Info -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="font-medium text-gray-900 mb-3">{{ __('Conversation Details') }}</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ __('Status') }}:</span>
                                <span class="font-medium">{{ $conversation->status }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ __('Phone') }}:</span>
                                <span class="font-medium">{{ $conversation->phone }}</span>
                            </div>
                            @if($conversation->customer_name)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ __('Customer') }}:</span>
                                    <span class="font-medium">{{ $conversation->customer_name }}</span>
                                </div>
                            @endif
                            @if($conversation->assignedAgent)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ __('Assigned to') }}:</span>
                                    <span class="font-medium">{{ $conversation->assignedAgent->name }}</span>
                                </div>
                            @endif
                            @if($conversation->is_emergency)
                                <div class="bg-red-50 border border-red-200 rounded p-2 mt-3">
                                    <div class="text-red-800 font-semibold text-sm">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        {{ __('Emergency Priority Detected') }}
                                    </div>
                                    <div class="text-xs text-red-600 mt-1">
                                        {{ __('Reason') }}: {{ $conversation->emergency_reason }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
