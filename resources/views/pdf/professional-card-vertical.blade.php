<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Carte Professionnelle - {{ $employee->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Arial', sans-serif;
            width: 53.98mm;
            height: 85.6mm;
            margin: 0;
            padding: 0;
        }

        .card {
            width: 100%;
            height: 100%;
            border: 1.5px solid #004d00;
            background: #ffffff;
            position: relative;
            overflow: hidden;
            display: table;
        }

        .side-band {
            display: table-cell;
            width: 5mm;
            background: linear-gradient(180deg, #006633 0%, #ce1126 50%, #fcd116 100%);
            vertical-align: top;
        }

        .side-band-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 5px;
            font-weight: bold;
            color: #fff;
            padding: 2mm 0;
            white-space: nowrap;
        }

        .main {
            display: table-cell;
            vertical-align: top;
            padding: 2mm;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 2mm;
        }

        .hospital-name {
            font-size: 7px;
            font-weight: bold;
            color: #004d00;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .card-title {
            font-size: 6.5px;
            color: #666;
            margin-top: 0.5mm;
        }

        .photo-row {
            text-align: center;
            margin-bottom: 2mm;
        }

        .photo {
            width: 22mm;
            height: 26mm;
            border: 1.5px solid #004d00;
            object-fit: cover;
        }

        .info-row {
            margin-bottom: 1.3mm;
            font-size: 6.5px;
            line-height: 1.2;
        }

        .info-label {
            font-weight: bold;
            color: #004d00;
            text-transform: uppercase;
            display: block;
            font-size: 5.5px;
        }

        .info-value {
            color: #222;
            font-weight: 600;
        }

        .qr-footer {
            display: table;
            width: 100%;
            margin-top: 2mm;
            border-top: 0.5px solid #ddd;
            padding-top: 1.5mm;
        }

        .qr-cell {
            display: table-cell;
            width: 16mm;
            vertical-align: top;
        }

        .qr-code {
            width: 14mm;
            height: 14mm;
            border: 1px solid #ddd;
        }

        .validity-cell {
            display: table-cell;
            vertical-align: top;
            padding-left: 1.5mm;
        }

        .validity-label, .issue-label {
            font-size: 5px;
            color: #666;
        }

        .validity-date, .issue-date {
            font-size: 6px;
            font-weight: bold;
            color: #004d00;
            margin-bottom: 1mm;
        }

        .signature-block {
            margin-top: 2mm;
            text-align: center;
        }

        .signature-label {
            font-size: 5px;
            color: #666;
        }

        .signature-img {
            width: 18mm;
            height: auto;
            max-height: 6mm;
        }

        .card-number {
            text-align: center;
            font-size: 5px;
            color: #666;
            font-weight: bold;
            margin-top: 1mm;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="side-band">
            <div class="side-band-text">REPUBLIQUE DU CAMEROUN</div>
        </div>

        <div class="main">
            <div class="header">
                <div class="hospital-name">{{ $hospital_name }}</div>
                <div class="card-title">CARTE PROFESSIONNELLE</div>
            </div>

            <div class="photo-row">
                <img src="{{ $photo_path }}" alt="Photo" class="photo">
            </div>

            <div class="info-row">
                <span class="info-label">Nom / Surname</span>
                <span class="info-value">{{ strtoupper($employee->last_name) }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Prénoms / Given Names</span>
                <span class="info-value">{{ ucwords(strtolower($employee->first_name ?? '')) }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Matricule</span>
                <span class="info-value">{{ $employee->matricule }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">N° CNI</span>
                <span class="info-value">{{ $employee->id_card_number ?? 'N/A' }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Grade</span>
                <span class="info-value">{{ $employee->qualification?->name ?? 'N/A' }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Service</span>
                <span class="info-value">{{ $employee->currentService?->name ?? $employee->department?->name ?? 'N/A' }}</span>
            </div>

            <div class="qr-footer">
                <div class="qr-cell">
                    @if($qr_code_path && file_exists($qr_code_path))
                    <img src="{{ $qr_code_path }}" alt="QR Code" class="qr-code">
                    @endif
                </div>
                <div class="validity-cell">
                    <div class="issue-label">Émise le</div>
                    <div class="issue-date">{{ $card->issue_date?->format('d/m/Y') }}</div>
                    <div class="validity-label">Expire le</div>
                    <div class="validity-date">{{ $card->expiry_date->format('d/m/Y') }}</div>
                </div>
            </div>

            <div class="signature-block">
                <div class="signature-label">Le Directeur Général</div>
                @if(file_exists($signature_dg_path))
                <img src="{{ $signature_dg_path }}" alt="Signature" class="signature-img">
                @else
                <div style="height: 5mm;"></div>
                @endif
            </div>

            <div class="card-number">{{ $card->card_number }}</div>
        </div>
    </div>
</body>

</html>