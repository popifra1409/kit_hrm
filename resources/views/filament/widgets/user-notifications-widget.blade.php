<x-filament-widgets::widget>
    @php
        $notifications = $this->getUnreadNotifications();
        $count = $notifications->count();
    @endphp

    @if($count > 0)
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                    </span>
                    <span class="text-lg font-semibold">
                        🔔 Notifications
                    </span>
                    <span class="ml-2 inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">
                        {{ $count }} nouvelle{{ $count > 1 ? 's' : '' }}
                    </span>
                </div>
                
                <button 
                    wire:click="markAllAsRead"
                    class="text-sm text-gray-600 hover:text-gray-900 underline">
                    Tout marquer comme lu
                </button>
            </div>
        </x-slot>

        <div class="space-y-3">
            @foreach($notifications as $notification)
            <div class="flex items-start gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                <div class="flex-shrink-0 mt-1">
                    @if($notification->icon)
                        <x-filament::icon 
                            :icon="$notification->icon" 
                            class="w-5 h-5 text-{{ $notification->color }}-600"
                        />
                    @else
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                    @endif
                </div>
                
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">
                        {{ $notification->title }}
                    </p>
                    <p class="text-sm text-gray-700 mt-1">
                        {{ $notification->message }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $notification->created_at->diffForHumans() }}
                    </p>
                    
                    @if($notification->action_url)
                    <div class="mt-2">
                        <a href="{{ $notification->action_url }}" 
                           class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            {{ $notification->action_label ?? 'Voir plus' }} →
                        </a>
                    </div>
                    @endif
                </div>
                
                <button 
                    wire:click="markAsRead({{ $notification->id }})"
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            @endforeach
        </div>
    </x-filament::section>
    @endif
</x-filament-widgets::widget>