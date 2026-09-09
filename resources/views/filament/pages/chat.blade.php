<x-filament-panels::page>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        if (!window.Echo) {
            document.addEventListener('livewire:init', () => {
                window.Pusher = Pusher;
                window.Echo = new Pusher('{{ env('REVERB_APP_KEY') }}', {
                    wsHost: '{{ env('REVERB_HOST', 'localhost') }}',
                    wsPort: {{ env('REVERB_PORT', 8080) }},
                    wssPort: {{ env('REVERB_PORT', 8080) }},
                    forceTLS: '{{ env('REVERB_SCHEME', 'http') }}' === 'https',
                    enabledTransports: ['ws', 'wss'],
                    cluster: '',
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        },
                    },
                });

                window.Echo.private = function (channel) {
                    return window.Echo.subscribe('private-' + channel);
                };
                window.Echo.channel = function (channel) {
                    return window.Echo.subscribe(channel);
                };
            });
        }
    </script>

    <div class="flex h-[75vh] gap-4">
        <div class="w-72 shrink-0 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden flex flex-col">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex gap-2">
                <x-filament::button size="sm" wire:click="openGroupModal" icon="heroicon-o-user-group">
                    Groupe
                </x-filament::button>
            </div>

            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                <select wire:model="newDirectUserId" wire:change="startDirectConversation"
                        class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-600">
                    <option value="">+ Nouvelle discussion...</option>
                    @foreach($this->getAvailableUsers() as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 overflow-y-auto">
                @forelse($this->getConversations() as $conv)
                    <button wire:click="selectConversation({{ $conv['id'] }})"
                            class="w-full text-left p-3 border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition
                                   {{ $selectedConversationId === $conv['id'] ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-sm truncate">
                                {{ $conv['is_group'] ? '👥 ' : '' }}{{ $conv['name'] ?? 'Sans nom' }}
                            </span>
                            @if($conv['unread_count'] > 0)
                                <span class="inline-flex items-center justify-center w-5 h-5 text-xs rounded-full bg-primary-600 text-white">
                                    {{ $conv['unread_count'] }}
                                </span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 truncate mt-0.5">
                            {{ $conv['last_message'] ?? 'Aucun message' }}
                        </div>
                    </button>
                @empty
                    <p class="text-sm text-gray-500 p-4 text-center">Aucune conversation. Démarrez-en une ci-dessus.</p>
                @endforelse
            </div>
        </div>

        <div class="flex-1 border border-gray-200 dark:border-gray-700 rounded-lg flex flex-col overflow-hidden">
            @if($selectedConversationId)
                <div class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-container">
                    @forelse($this->getMessages() as $message)
                        <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[70%] {{ $message->sender_id === auth()->id() ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800' }} rounded-lg px-3 py-2">
                                @if($message->sender_id !== auth()->id())
                                    <div class="text-xs font-semibold opacity-70 mb-0.5">{{ $message->sender?->name }}</div>
                                @endif

                                @if($message->isDeleted())
                                    <em class="text-sm opacity-60">Message supprimé</em>
                                @else
                                    @if($message->body)
                                        <div class="text-sm whitespace-pre-wrap">{{ $message->body }}</div>
                                    @endif

                                    @foreach($message->attachments as $attachment)
                                        <a href="{{ $attachment->url }}" target="_blank"
                                           class="flex items-center gap-1.5 mt-1 text-xs underline opacity-90">
                                            📎 {{ $attachment->file_name }} ({{ $attachment->human_size }})
                                        </a>
                                    @endforeach
                                @endif

                                <div class="text-[10px] opacity-60 mt-1 text-right">
                                    {{ $message->created_at->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center mt-8">Aucun message pour l'instant. Dites bonjour 👋</p>
                    @endforelse
                </div>

                <form wire:submit="sendMessage" class="p-3 border-t border-gray-200 dark:border-gray-700 flex items-end gap-2">
                    <div class="flex-1">
                        <textarea wire:model="newMessageBody" rows="1" placeholder="Écrivez un message..."
                                  class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-600 resize-none"
                                  onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault(); this.form.requestSubmit();}"></textarea>
                    </div>

                    <label class="cursor-pointer p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800" title="Joindre un fichier (25 Mo max)">
                        <input type="file" wire:model="newAttachment" class="hidden">
                        📎
                    </label>

                    <x-filament::button type="submit" size="sm">Envoyer</x-filament::button>
                </form>

                @if($newAttachment)
                    <div class="px-3 pb-2 text-xs text-gray-500">
                        Pièce jointe prête : {{ $newAttachment->getClientOriginalName() }}
                    </div>
                @endif
                @error('newAttachment') <div class="px-3 pb-2 text-xs text-danger-600">{{ $message }}</div> @enderror
            @else
                <div class="flex-1 flex items-center justify-center text-gray-400">
                    Sélectionnez une conversation pour commencer
                </div>
            @endif
        </div>
    </div>

    @if($showGroupModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" wire:click.self="$set('showGroupModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg p-5 w-96">
                <h3 class="font-semibold mb-3">Nouveau groupe</h3>

                <input type="text" wire:model="newGroupName" placeholder="Nom du groupe"
                       class="w-full text-sm rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-600 mb-3">

                <div class="max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-md p-2 mb-3">
                    @foreach($this->getAvailableUsers() as $user)
                        <label class="flex items-center gap-2 py-1 text-sm">
                            <input type="checkbox" wire:model="newGroupUserIds" value="{{ $user->id }}">
                            {{ $user->name }}
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end gap-2">
                    <x-filament::button color="gray" size="sm" wire:click="$set('showGroupModal', false)">Annuler</x-filament::button>
                    <x-filament::button size="sm" wire:click="createGroup">Créer</x-filament::button>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('livewire:navigated', () => {
            const container = document.getElementById('messages-container');
            if (container) container.scrollTop = container.scrollHeight;
        });
    </script>
</x-filament-panels::page>