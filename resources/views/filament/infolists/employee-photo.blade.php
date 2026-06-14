<div class="flex items-center justify-center">
    @if($record->photo)
        <img src="{{ Storage::url($record->photo) }}" alt="Photo de {{ $record->full_name }}"
            class="w-32 h-32 rounded-full border-4 border-gray-300 shadow-lg object-cover">
    @else
        <img src="{{ url('/images/default-avatar.png') }}" alt="Avatar par défaut"
            class="w-32 h-32 rounded-full border-4 border-gray-300 shadow-lg object-cover bg-gray-200">
    @endif
</div>