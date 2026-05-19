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

    <div class="py-2" x-data="{ 
        auto: true,
        isRecording: false,
        recordingTime: 0,
        mediaPreview: null,
        showEmojiPicker: false,
        messageText: ''
    }" x-init="
        $el.app = $data;
        // Real-time refresh with WebSocket simulation
        setInterval(() => { 
            if (auto) {
                // Check for new messages
                fetch(window.location.href + '/check-new')
                    .then(response => response.json())
                    .then(data => {
                        if (data.new_messages) {
                            window.location.reload();
                        }
                    })
                    .catch(() => {}); // Silent fail
            }
        }, 5000);
        
        // Voice recording timer
        setInterval(() => {
            if (isRecording) {
                recordingTime++;
            }
        }, 1000);
        
        // Ensure recording is not active on load
        isRecording = false;
        recordingTime = 0;
    ">
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
                        <div class="bg-[#f0f2f5] px-4 py-3 border-b border-gray-200 flex items-center justify-between">
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
                                                Emergency Priority
                                            </span>
                                        @else
                                            Online
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" class="text-gray-600 hover:text-gray-800">
                                    <i class="fas fa-video text-xl"></i>
                                </button>
                                <button type="button" class="text-gray-600 hover:text-gray-800">
                                    <i class="fas fa-phone text-xl"></i>
                                </button>
                                <button type="button" class="text-gray-600 hover:text-gray-800">
                                    <i class="fas fa-search text-xl"></i>
                                </button>
                                <button type="button" class="text-gray-600 hover:text-gray-800">
                                    <i class="fas fa-ellipsis-v text-xl"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Messages Area -->
                        <div class="p-4 space-y-1 overflow-y-auto bg-[#e5ddd5]" style="max-height: calc(100vh - 350px);" id="thread">
                            @foreach ($messages as $m)
                                <div class="flex {{ $m->sender_type === 'agent' ? 'justify-end' : 'justify-start' }} mb-2 message-container" data-message-id="{{ $m->id }}">
                                    <div class="flex {{ $m->sender_type === 'agent' ? 'flex-row-reverse' : 'flex-row' }} items-end max-w-[75%] relative group">
                                        <!-- Message Bubble -->
                                        <div class="max-w-full {{ $m->sender_type === 'agent' ? 'mr-3' : 'ml-3' }}">
                                            <div class="rounded-lg px-3 py-2 {{ $m->sender_type === 'agent' ? 'bg-[#dcf8c6] text-gray-900' : 'bg-white text-gray-900' }} shadow-sm relative">
                                                <!-- Three Dot Menu -->
                                                <div class="absolute {{ $m->sender_type === 'agent' ? 'left-1 top-1' : 'right-1 top-1' }} opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <div class="relative">
                                                        <button @click.stop="$refs.menu{{ $m->id }}.classList.toggle('hidden')" type="button" class="text-gray-500 hover:text-gray-700 p-1">
                                                            <i class="fas fa-ellipsis-v text-xs"></i>
                                                        </button>
                                                        <div x-ref="menu{{ $m->id }}" class="hidden absolute {{ $m->sender_type === 'agent' ? 'left-0' : 'right-0' }} mt-1 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-50 min-w-[120px]">
                                                            <button onclick="copyMessage({{ $m->id }})" type="button" class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                <i class="fas fa-copy mr-2"></i>{{ __('Copy') }}
                                                            </button>
                                                            <button onclick="replyToMessage({{ $m->id }})" type="button" class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                <i class="fas fa-reply mr-2"></i>{{ __('Reply') }}
                                                            </button>
                                                            @if(auth()->user()->isAdmin())
                                                                <button onclick="deleteMessage({{ $m->id }})" type="button" class="block w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                                                    <i class="fas fa-trash mr-2"></i>{{ __('Delete') }}
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                @if($m->message_type === 'image' && $m->media_path)
                                                    <img src="{{ Storage::disk($m->media_disk)->url($m->media_path) }}" alt="" class="max-w-full rounded max-h-48 object-contain mb-2 cursor-pointer" onclick="openMediaModal('{{ Storage::disk($m->media_disk)->url($m->media_path) }}', 'image')">
                                                @elseif($m->message_type === 'audio' && $m->media_path)
                                                    <div class="bg-white rounded-lg p-3 mb-2">
                                                        <div class="flex items-center gap-3">
                                                            <button onclick="playAudio('{{ Storage::disk($m->media_disk)->url($m->media_path) }}')" class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white hover:bg-green-600">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                            <div class="flex-1">
                                                                <div class="h-8 bg-gray-200 rounded-full">
                                                                    <div class="h-full bg-green-500 rounded-full" style="width: 30%"></div>
                                                                </div>
                                                            </div>
                                                            <span class="text-xs text-gray-500">0:15</span>
                                                        </div>
                                                    </div>
                                                @elseif(in_array($m->message_type, ['video','document']) && $m->media_path)
                                                    <a href="{{ Storage::disk($m->media_disk)->url($m->media_path) }}" class="flex items-center gap-3 bg-gray-50 rounded-lg p-3 mb-2 hover:bg-gray-100" target="_blank">
                                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white">
                                                            <i class="fas fa-{{ $m->message_type === 'video' ? 'video' : 'file' }}"></i>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium">{{ $m->file_name ?: __('Download') }}</p>
                                                            <p class="text-xs text-gray-500">{{ __('Click to download') }}</p>
                                                        </div>
                                                    </a>
                                                @endif
                                                @if($m->content)
                                                    <p class="text-sm whitespace-pre-wrap">{{ $m->content }}</p>
                                                @endif
                                                @if($m->message_type === 'template')
                                                    <p class="text-xs opacity-90 mt-1">{{ __('Template') }}: {{ $m->template_name }} ({{ $m->template_language }})</p>
                                                @endif
                                                <p class="text-xs mt-1 {{ $m->sender_type === 'agent' ? 'text-gray-600' : 'text-gray-500' }}">
                                                    {{ $m->created_at->format('H:i') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Enhanced Message Input Area -->
                        @if($session['active'])
                            <div class="bg-[#f0f2f5] border-t border-gray-200 px-4 py-3">
                                <!-- Message Input -->
                                <div class="flex items-end gap-2 bg-white rounded-full border border-gray-300 px-3 py-1.5 shadow-sm">
                                    <!-- Emoji Button -->
                                    <button @click="showEmojiPicker = !showEmojiPicker" type="button" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-gray-700">
                                        <i class="far fa-smile text-xl"></i>
                                    </button>

                                    <!-- Text Input -->
                                    <div class="flex-1 relative">
                                        <textarea 
                                            id="messageInput"
                                            name="text" 
                                            rows="1" 
                                            x-ref="messageInput"
                                            x-model="messageText"
                                            @keydown.enter.prevent="sendMessage()"
                                            @input="messageText = $el.value"
                                            class="w-full bg-transparent border-0 resize-none py-1 px-2 text-gray-800 placeholder-gray-500 focus:outline-none"
                                            placeholder="Type a message"
                                            style="min-height: 24px; max-height: 100px;"
                                        ></textarea>
                                    </div>

                                    <!-- Attachment Button -->
                                    <button onclick="document.getElementById('mediaFile').click()" type="button" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-paperclip text-xl"></i>
                                    </button>
                                    <input type="file" id="mediaFile" onchange="handleMediaSelect(event)" accept="image/*,audio/*,video/*,.pdf,.doc,.docx" class="hidden">
                                    
                                    <!-- Voice Record Button (replaces send when empty) -->
                                    <button 
                                        x-show="!messageText || messageText.trim() === ''"
                                        @click="startRecording()"
                                        type="button"
                                        class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-gray-700"
                                    >
                                        <i class="fas fa-microphone text-xl"></i>
                                    </button>
                                    
                                    <!-- Send Button -->
                                    <button 
                                        x-show="messageText && messageText.trim() !== ''"
                                        @click="sendMessage()"
                                        type="button"
                                        class="w-8 h-8 flex items-center justify-center text-[#128C7E] hover:text-[#0D6EFD]"
                                    >
                                        <i class="fas fa-paper-plane text-xl"></i>
                                    </button>
                                </div>

                                <!-- Emoji Picker -->
                                <div x-show="showEmojiPicker" x-transition class="mt-3 p-3 bg-white border border-gray-200 rounded-lg shadow-lg">
                                    <div class="grid grid-cols-8 gap-2">
                                        <button onclick="insertEmoji('😀')" class="text-2xl hover:bg-gray-100 p-2 rounded">😀</button>
                                        <button onclick="insertEmoji('😂')" class="text-2xl hover:bg-gray-100 p-2 rounded">😂</button>
                                        <button onclick="insertEmoji('❤️')" class="text-2xl hover:bg-gray-100 p-2 rounded">❤️</button>
                                        <button onclick="insertEmoji('👍')" class="text-2xl hover:bg-gray-100 p-2 rounded">👍</button>
                                        <button onclick="insertEmoji('🎉')" class="text-2xl hover:bg-gray-100 p-2 rounded">🎉</button>
                                        <button onclick="insertEmoji('🔥')" class="text-2xl hover:bg-gray-100 p-2 rounded">🔥</button>
                                        <button onclick="insertEmoji('😊')" class="text-2xl hover:bg-gray-100 p-2 rounded">😊</button>
                                        <button onclick="insertEmoji('🙏')" class="text-2xl hover:bg-gray-100 p-2 rounded">🙏</button>
                                    </div>
                                </div>
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

    <!-- Media Modal -->
    <div id="mediaModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center">
        <div class="relative max-w-4xl max-h-[90vh] p-4">
            <button onclick="closeMediaModal()" class="absolute top-4 right-4 text-white bg-black bg-opacity-50 rounded-full w-10 h-10 flex items-center justify-center hover:bg-opacity-75">
                <i class="fas fa-times"></i>
            </button>
            <img id="modalImage" src="" alt="" class="max-w-full max-h-full rounded-lg">
        </div>
    </div>

    <script>
        let mediaRecorder;
        let audioChunks = [];
        
        // Initialize Alpine app reference for global access
        document.addEventListener('alpine:initialized', () => {
            const appElement = document.querySelector('[x-data]');
            if (appElement && appElement._x_dataStack) {
                window.Alpine.app = appElement._x_dataStack[0];
            }
        });

        function showNotification(message, type = 'success') {
            // Create a simple notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        function copyMessage(messageId) {
            const messageElement = document.querySelector(`[data-message-id="${messageId}"] p`);
            if (messageElement) {
                navigator.clipboard.writeText(messageElement.textContent);
                showNotification('Message copied to clipboard');
            }
        }

        function replyToMessage(messageId) {
            const messageElement = document.querySelector(`[data-message-id="${messageId}"] p`);
            if (messageElement) {
                const input = document.getElementById('messageInput');
                input.value = 'Replying to: ' + messageElement.textContent.trim() + '\n\n';
                input.focus();
                // Update Alpine data
                if (window.Alpine?.app) {
                    window.Alpine.app.messageText = input.value;
                }
            }
        }

        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message?')) {
                fetch(`/conversations/messages/${messageId}/delete`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('Delete failed');
                    }
                    return response.json();
                }).then(data => {
                    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                    if (messageElement) {
                        messageElement.remove();
                        showNotification('Message deleted');
                    }
                }).catch(error => {
                    showNotification('Failed to delete message', 'error');
                });
            }
        }

        function handleMediaSelect(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file
                const maxSize = 16 * 1024 * 1024; // 16MB
                if (file.size > maxSize) {
                    showNotification('File too large. Maximum size is 16MB', 'error');
                    return;
                }
                
                // Create FormData and upload
                const formData = new FormData();
                formData.append('media', file);
                formData.append('conversation_id', '{{ $conversation->id }}');
                
                showNotification('Uploading file...');
                
                fetch('/conversations/send-media', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('Upload failed');
                    }
                    return response.json();
                }).then(data => {
                    showNotification('File uploaded successfully');
                    setTimeout(() => window.location.reload(), 1000);
                }).catch(error => {
                    showNotification('Failed to upload file', 'error');
                });
                
                // Clear file input
                event.target.value = '';
            }
        }

        function startRecording() {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    mediaRecorder = new MediaRecorder(stream);
                    mediaRecorder.start();
                    audioChunks = [];
                    
                    mediaRecorder.ondataavailable = event => {
                        audioChunks.push(event.data);
                    };
                    
                    const app = window.Alpine?.app || {};
                    app.isRecording = true;
                    app.recordingTime = 0;
                })
                .catch(error => {
                    showNotification('Could not access microphone', 'error');
                });
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    
                    // Send voice note
                    const formData = new FormData();
                    formData.append('audio', audioBlob, 'voice-note.wav');
                    formData.append('conversation_id', '{{ $conversation->id }}');
                    
                    showNotification('Sending voice message...');
                    
                    fetch('/conversations/send-voice', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    }).then(response => {
                        if (!response.ok) {
                            throw new Error('Voice upload failed');
                        }
                        return response.json();
                    }).then(data => {
                        showNotification('Voice message sent');
                        setTimeout(() => window.location.reload(), 1000);
                    }).catch(error => {
                        showNotification('Failed to send voice message', 'error');
                    });
                };
                
                const app = window.Alpine?.app || {};
                app.isRecording = false;
                app.recordingTime = 0;
            }
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (message) {
                // Show loading state
                const sendBtn = document.querySelector('button[x-show="messageText && messageText.trim() !== \'\'"]');
                if (sendBtn) {
                    sendBtn.disabled = true;
                    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-xl"></i>';
                }
                
                const formData = new FormData();
                formData.append('text', message);
                
                fetch('{{ route('conversations.send-text', $conversation) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                }).then(data => {
                    // Clear both the input and the Alpine data
                    input.value = '';
                    const app = window.Alpine?.app || {};
                    app.messageText = '';
                    showNotification('Message sent successfully');
                    setTimeout(() => window.location.reload(), 1000);
                }).catch(error => {
                    showNotification('Failed to send message', 'error');
                    // Restore button
                    if (sendBtn) {
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = '<i class="fas fa-paper-plane text-xl"></i>';
                    }
                });
            }
        }

        function insertEmoji(emoji) {
            const input = document.getElementById('messageInput');
            input.value += emoji;
            input.focus();
            const app = window.Alpine?.app || {};
            app.messageText = input.value;
            app.showEmojiPicker = false;
        }

        function openMediaModal(src, type) {
            if (type === 'image') {
                document.getElementById('modalImage').src = src;
                document.getElementById('mediaModal').classList.remove('hidden');
            }
        }

        function closeMediaModal() {
            document.getElementById('mediaModal').classList.add('hidden');
        }

        function playAudio(src) {
            const audio = new Audio(src);
            audio.play();
        }

        // Auto-resize textarea
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });
            }
        });

        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.relative')) {
                document.querySelectorAll('[x-ref^="menu"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });
    </script>
</x-app-layout>
