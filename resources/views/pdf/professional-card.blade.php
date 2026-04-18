<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte Professionnelle - {{ $employee->full_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            width: 85.6mm;
            height: 53.98mm;
            margin: 0;
            padding: 0;
        }

        .card {
            width: 100%;
            height: 100%;
            border: 2px solid #004d00;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            position: relative;
            overflow: hidden;
        }

        /* En-tête */
        .header {
            background: linear-gradient(90deg, #006633 0%, #ce1126 50%, #fcd116 100%);
            padding: 3px 0;
            text-align: center;
            position: relative;
        }

        .header-content {
            background: rgba(255, 255, 255, 0.95);
            margin: 0 2px;
            padding: 2px;
        }

        .hospital-name {
            font-size: 8px;
            font-weight: bold;
            color: #004d00;
            text-transform: uppercase;
        }

        .card-title {
            font-size: 7px;
            color: #666;
            margin-top: 1px;
        }

        /* Drapeaux et armoiries */
        .flags {
            position: absolute;
            top: 15px;
        }

        .flag-left {
            left: 3px;
        }

        .flag-right {
            right: 3px;
        }

        .flag-img {
            width: 12mm;
            height: auto;
            opacity: 0.8;
        }

        /* Corps de la carte */
        .card-body {
            padding: 15px 5px 5px 5px;
            display: table;
            width: 100%;
        }

        /* Photo */
        .photo-section {
            display: table-cell;
            width: 25mm;
            vertical-align: top;
            padding-right: 3px;
        }

        .photo {
            width: 22mm;
            height: 28mm;
            border: 1.5px solid #004d00;
            object-fit: cover;
            display: block;
        }

        /* Informations */
        .info-section {
            display: table-cell;
            vertical-align: top;
            padding-left: 2px;
        }

        .info-row {
            margin-bottom: 1.5mm;
            font-size: 7px;
            line-height: 1.2;
        }

        .info-label {
            font-weight: bold;
            color: #004d00;
            display: inline-block;
            width: 20mm;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        /* QR Code */
        .qr-section {
            display: table-cell;
            width: 18mm;
            vertical-align: top;
            text-align: center;
            padding-left: 2px;
        }

        .qr-code {
            width: 15mm;
            height: 15mm;
            border: 1px solid #ddd;
        }

        .card-number {
            font-size: 5px;
            color: #666;
            margin-top: 1mm;
            font-weight: bold;
        }

        /* Pied de carte */
        .footer {
            position: absolute;
            bottom: 2px;
            left: 5px;
            right: 5px;
            display: table;
            width: calc(100% - 10px);
        }

        .signature-section {
            display: table-cell;
            width: 50%;
            text-align: left;
        }

        .validity-section {
            display: table-cell;
            width: 50%;
            text-align: right;
        }

        .signature-label,
        .validity-label {
            font-size: 5px;
            color: #666;
            margin-bottom: 1px;
        }

        .signature-img {
            width: 15mm;
            height: auto;
            max-height: 6mm;
        }

        .validity-date {
            font-size: 6px;
            font-weight: bold;
            color: #004d00;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 25px;
            color: rgba(0, 77, 0, 0.05);
            font-weight: bold;
            z-index: 0;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="card">
        <!-- Watermark -->
        <div class="watermark">{{ $hospital_acronym }}</div>

        <!-- En-tête -->
        <div class="header">
            <div class="header-content">
                <div class="hospital-name">{{ $hospital_name }}</div>
                <div class="card-title">CARTE PROFESSIONNELLE</div>
            </div>
        </div>

        <!-- Drapeaux -->
        @if(file_exists($drapeau_path))
        <div class="flags flag-left">
            <img src="{{ $drapeau_path }}" alt="Drapeau" class="flag-img">
        </div>
        @endif

        @if(file_exists($armoiries_path))
        <div class="flags flag-right">
            <img src="{{ $armoiries_path }}" alt="Armoiries" class="flag-img">
        </div>
        @endif

        <!-- Corps -->
        <div class="card-body">
            <!-- Photo -->
            <div class="photo-section">
                <img src="{{ $photo_path }}" alt="Photo" class="photo">
            </div>

            <!-- Informations -->
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">NOM :</span>
                    <span class="info-value">{{ strtoupper($employee->last_name) }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">PRÉNOM(S) :</span>
                    <span class="info-value">{{ ucwords(strtolower($employee->first_name)) }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">MATRICULE :</span>
                    <span class="info-value">{{ $employee->matricule }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">N° CNI :</span>
                    <span class="info-value">{{ $employee->id_card_number ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">FONCTION :</span>
                    <span class="info-value">{{ $employee->position?->name ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">SERVICE :</span>
                    <span class="info-value">{{ $employee->service?->name ?? 'N/A' }}</span>
                </div>
            </div>

            <!-- QR Code -->
            <div class="qr-section">
                @if($qr_code_path && file_exists($qr_code_path))
                <img src="{{ $qr_code_path }}" alt="QR Code" class="qr-code">
                @endif
                <div class="card-number">{{ $card->card_number }}</div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <div class="signature-section">
                <div class="signature-label">Le Directeur Général</div>
                @if(file_exists($signature_dg_path))
                <img src="{{ $signature_dg_path }}" alt="Signature" class="signature-img">
                @else
                <div style="height: 6mm;"></div>
                @endif
            </div>

            <div class="validity-section">
                <div class="validity-label">Valide jusqu'au</div>
                <div class="validity-date">{{ $card->expiry_date->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>
</body>

</html>