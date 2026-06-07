@if($record->qr_code_path)
    <div class="flex flex-col items-center gap-4">
        <img src="{{ Storage::url($record->qr_code_path) }}" alt="QR Code"
            class="w-80 h-80 border-2 border-gray-300 rounded-lg shadow-lg bg-white p-3 object-contain">

        <div class="text-sm text-gray-600 text-center space-y-1">
            <p><strong>Matricule:</strong> {{ $record->matricule }}</p>
            <p><strong>Nom:</strong> {{ $record->full_name }}</p>
            <p class="text-xs text-gray-400 mt-2">Généré le {{ $record->updated_at->format('d/m/Y à H:i') }}</p>
        </div>
    </div>
@else
    <div class="text-center text-gray-500">
        QR Code non disponible
    </div>
@endif