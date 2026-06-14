<div class="flex items-center justify-center p-6">
    @if($record->photo)
        <img src="{{ Storage::url($record->photo) }}" alt="Photo de {{ $record->full_name }}"
            class="w-80 h-80 rounded-lg border-4 border-gray-300 shadow-2xl object-cover">
    @else
        <img src="{{ url('/images/default-avatar.png') }}" alt="Avatar par défaut"
            class="w-80 h-80 rounded-lg border-4 border-gray-300 shadow-2xl object-cover bg-gray-200">
    @endif
</div>