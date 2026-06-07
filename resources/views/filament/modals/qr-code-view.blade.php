<div class="flex flex-col items-center gap-4 p-6">
    <h3 class="text-lg font-bold text-gray-900">QR Code - {{ $employee->matricule }}</h3>

    <div class="bg-white p-4 rounded-lg border-2 border-gray-200 shadow-lg">
        @if($employee->qr_code_path)
            <img src="{{ Storage::url($employee->qr_code_path) }}" alt="QR Code" class="w-80 h-80 object-contain">
        @else
            <div class="w-80 h-80 flex items-center justify-center bg-gray-100 rounded">
                <p class="text-gray-500">QR Code non disponible</p>
            </div>
        @endif
    </div>

    <div class="text-center text-sm text-gray-600 w-full">
        <p><strong>Matricule:</strong> {{ $employee->matricule }}</p>
        <p><strong>Nom:</strong> {{ $employee->full_name }}</p>
        <p><strong>ID:</strong> {{ $employee->id }}</p>
        @if($employee->qr_code_path)
            <p class="text-xs text-gray-400 mt-2">Généré le {{ $employee->updated_at->format('d/m/Y à H:i') }}</p>
        @endif
    </div>

    @if($employee->qr_code_path)
        <a href="{{ Storage::download($employee->qr_code_path, 'qrcode-' . $employee->matricule . '.png') }}"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
            📥 Télécharger le QR Code
        </a>
    @endif
</div>