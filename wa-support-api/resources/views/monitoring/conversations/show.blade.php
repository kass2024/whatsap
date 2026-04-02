<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between w-full">
            <div class="min-w-0">
                <a href="{{ route('conversations.index') }}" class="text-sm font-medium text-[#427431] hover:underline">← {{ __('Inbox') }}</a>
                <h1 class="text-lg font-bold text-slate-900 mt-1 truncate">
                    {{ $conversation->customer_name ?: '+'.$conversation->phone }}
                </h1>
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
        <div class="max-w-4xl mx-auto space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm">{{ session('status') }}</div>
            @endif

            @if(auth()->user()->isAdmin() && $isAdminOnlyPhone)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    {{ __('This number is on the restricted list. Agents cannot see this thread in the inbox or via the API.') }}
                    <a href="{{ route('settings.admin-phones') }}" class="font-semibold text-[#427431] hover:underline ml-1">{{ __('Edit list') }}</a>
                </div>
            @endif

            @if(auth()->user()->isAdmin())
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <form method="POST" action="{{ route('conversations.assign', $conversation) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="block text-xs font-medium text-gray-700">{{ __('Assign to agent') }}</label>
                            <select name="assigned_to" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Unassigned') }}</option>
                                @foreach ($agents as $a)
                                    <option value="{{ $a->id }}" @selected($conversation->assigned_to == $a->id)>{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                    </form>
                </div>
            @endif

            <div class="flex items-center gap-2 text-sm text-gray-500">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" x-model="auto" checked class="rounded border-gray-300">
                    {{ __('Auto-refresh every 10s') }}
                </label>
                <button type="button" @click="window.location.reload()" class="text-indigo-600 hover:underline">{{ __('Refresh now') }}</button>
            </div>

            <div class="bg-gray-100 rounded-lg p-4 min-h-[320px] max-h-[60vh] overflow-y-auto space-y-3" id="thread">
                @foreach ($messages as $m)
                    <div class="flex {{ $m->sender_type === 'agent' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] rounded-lg px-4 py-2 shadow-sm {{ $m->sender_type === 'agent' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-900' }}">
                            @if($m->message_type === 'image' && $m->media_path)
                                <img src="{{ Storage::disk($m->media_disk)->url($m->media_path) }}" alt="" class="max-w-full rounded max-h-48 object-contain">
                            @elseif($m->message_type === 'audio' && $m->media_path)
                                <audio controls class="max-w-full" src="{{ Storage::disk($m->media_disk)->url($m->media_path) }}"></audio>
                            @elseif(in_array($m->message_type, ['video','document']) && $m->media_path)
                                <a href="{{ Storage::disk($m->media_disk)->url($m->media_path) }}" class="underline" target="_blank">{{ $m->file_name ?: __('Download') }}</a>
                            @endif
                            @if($m->content)
                                <p class="text-sm whitespace-pre-wrap">{{ $m->content }}</p>
                            @endif
                            @if($m->message_type === 'template')
                                <p class="text-xs opacity-90">{{ __('Template') }}: {{ $m->template_name }} ({{ $m->template_language }})</p>
                            @endif
                            <p class="text-xs mt-1 {{ $m->sender_type === 'agent' ? 'text-indigo-200' : 'text-gray-400' }}">{{ $m->created_at->format('Y-m-d H:i') }} · {{ $m->sender_type }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div>{{ $messages->links() }}</div>

            @if($session['active'])
                <div class="bg-white shadow-sm sm:rounded-lg p-4 space-y-4">
                    <h3 class="font-medium text-gray-900">{{ __('Send message') }}</h3>
                    <form method="POST" action="{{ route('conversations.send-text', $conversation) }}" class="space-y-2">
                        @csrf
                        <textarea name="text" rows="3" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="{{ __('Type a message…') }}" required>{{ old('text') }}</textarea>
                        <x-input-error :messages="$errors->get('text')" />
                        <x-primary-button type="submit">{{ __('Send text') }}</x-primary-button>
                    </form>
                    <form method="POST" action="{{ route('conversations.send-media', $conversation) }}" enctype="multipart/form-data" class="space-y-2 border-t pt-4">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700">{{ __('Image / audio / video / document') }}</label>
                        <input type="file" name="file" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700">
                        <input type="text" name="caption" value="{{ old('caption') }}" placeholder="{{ __('Caption (optional)') }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <x-input-error :messages="$errors->get('media')" />
                        <x-secondary-button type="submit">{{ __('Send file') }}</x-secondary-button>
                    </form>
                </div>
            @else
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <p class="text-sm text-amber-900 mb-3">{{ __('Outside the 24-hour window you can only send approved WhatsApp template messages.') }}</p>
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
</x-app-layout>
