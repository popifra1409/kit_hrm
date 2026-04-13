<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de Paie</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 14pt;
            margin: 5px 0;
        }

        .header h2 {
            font-size: 12pt;
            margin: 3px 0;
        }

        .header h3 {
            font-size: 11pt;
            margin: 3px 0;
            font-weight: normal;
        }

        .title-box {
            background-color: #d0d0d0;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin: 15px 0;
        }

        .info-section {
            border: 2px solid #000;
            padding: 10px;
            margin-bottom: 15px;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            width: 30%;
            padding: 3px 5px;
            font-weight: bold;
        }

        .info-value {
            display: table-cell;
            width: 20%;
            padding: 3px 5px;
        }

        .salary-summary {
            background-color: #f0f0f0;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #000;
        }

        .salary-summary table {
            width: 100%;
        }

        .salary-summary td {
            padding: 3px;
        }

        .salary-summary .label {
            font-weight: bold;
        }

        .salary-summary .value {
            text-align: right;
            font-weight: bold;
        }

        .payroll-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .payroll-table th {
            background-color: #d0d0d0;
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
        }

        .payroll-table td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 9pt;
        }

        .payroll-table .text-right {
            text-align: right;
        }

        .payroll-table .text-center {
            text-align: center;
        }

        .totals-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .grand-total {
            font-weight: bold;
            font-size: 10pt;
            background-color: #e0e0e0;
        }

        .net-total {
            font-weight: bold;
            font-size: 11pt;
            background-color: #d0d0d0;
        }

        .footer {
            margin-top: 30px;
            font-size: 9pt;
            text-align: right;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    {{-- En-tête --}}
    <div class="header">
        <h1>CENTRE HOSPITALIER ET UNIVERSITAIRE DE YAOUNDÉ</h1>
        <h2>DIRECTION DES RESSOURCES HUMAINES ET FINANCIÈRES</h2>
        <h3>SOUS-DIRECTION DES RESSOURCES HUMAINES</h3>
        <h3>SERVICE DE LA SOLDE ET DES PENSIONS</h3>
    </div>

    {{-- Titre --}}
    <div class="title-box">
        BULLETIN DE PAIE
    </div>

    <div style="text-align: center; font-weight: bold; margin: 10px 0;">
        {{ $payroll->month_name }} {{ $payroll->year }}
    </div>

    {{-- Informations Employé --}}
    <div class="info-section">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">M./MME/MLLE</div>
                <div class="info-value" style="width: 70%;" colspan="3">
                    <strong>{{ strtoupper($employee->last_name . ' ' . $employee->first_name) }}</strong>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Fonction</div>
                <div class="info-value">{{ $employee->qualification }}</div>
                <div class="info-label">Mode de paiement</div>
                <div class="info-value">BILLETAGE</div>
            </div>
            <div class="info-row">
                <div class="info-label">Grade/Statut</div>
                <div class="info-value"></div>
                <div class="info-label">Numéro de compte</div>
                <div class="info-value">{{ $employee->bank_account_number }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Classement</div>
                <div class="info-value">{{ $employee->category_current }}</div>
                <div class="info-label">Banque</div>
                <div class="info-value">{{ $employee->bank_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de naissance</div>
                <div class="info-value">{{ $employee->birth_date ? $employee->birth_date->format('d/m/Y') : '' }}</div>
                <div class="info-label">DIPE</div>
                <div class="info-value"></div>
            </div>
            <div class="info-row">
                <div class="info-label">Situation matrimoniale</div>
                <div class="info-value">{{ strtoupper($employee->marital_status ?? '') }}</div>
                <div class="info-label">Date d'embauche</div>
                <div class="info-value">
                    {{ $employee->recruitment_date ? $employee->recruitment_date->format('d/m/Y') : '' }}</div>
            </div>
        </div>
    </div>

    {{-- Résumé Salaire --}}
    <div class="salary-summary">
        <table>
            <tr>
                <td class="label">Salaire de base :</td>
                <td class="value">{{ number_format($payroll->base_salary, 0, ',', ' ') }}</td>
                <td style="width: 20%;"></td>
                <td class="label">Matricule</td>
                <td class="value">{{ $employee->matricule }}</td>
            </tr>
            <tr>
                <td class="label">Net à Percevoir :</td>
                <td class="value" style="font-size: 12pt;">{{ number_format($payroll->net_salary, 0, ',', ' ') }}</td>
                <td style="width: 20%;"></td>
                <td class="label">N° CNPS</td>
                <td class="value">{{ $employee->cnps_number ?? '' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="font-size: 9pt; font-style: italic;">
                    ({{ $this->numberToWords($payroll->net_salary) }} FRANCS CFA)
                </td>
                <td style="width: 20%;"></td>
                <td class="label">Matricule Finance</td>
                <td class="value"></td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td style="width: 20%;"></td>
                <td class="label">Numéro ligne</td>
                <td class="value"></td>
            </tr>
        </table>
    </div>

    {{-- Tableau des Éléments --}}
    <table class="payroll-table">
        <thead>
            <tr>
                <th style="width: 40%;">Éléments de salaire</th>
                <th class="text-right" style="width: 20%;">Imposable</th>
                <th class="text-right" style="width: 20%;">Non Imposable</th>
                <th class="text-right" style="width: 20%;">Retenu</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalImposable = 0;
                $totalNonImposable = 0;
                $totalRetenu = 0;
            @endphp

            @foreach ($lines as $line)
                <tr>
                    <td>{{ strtoupper($line->item_name) }}</td>
                    @if ($line->type === 'gain')
                        @if ($line->is_taxable)
                            <td class="text-right">{{ number_format($line->amount, 0, ',', ' ') }}</td>
                            <td class="text-right"></td>
                            @php $totalImposable += $line->amount; @endphp
                        @else
                            <td class="text-right"></td>
                            <td class="text-right">{{ number_format($line->amount, 0, ',', ' ') }}</td>
                            @php $totalNonImposable += $line->amount; @endphp
                        @endif
                        <td class="text-right"></td>
                    @else
                        <td class="text-right"></td>
                        <td class="text-right"></td>
                        <td class="text-right">{{ number_format($line->amount, 0, ',', ' ') }}</td>
                        @php $totalRetenu += $line->amount; @endphp
                    @endif
                </tr>
            @endforeach

            {{-- Ligne TOTAUX --}}
            <tr class="totals-row">
                <td>TOTAUX</td>
                <td class="text-right">{{ number_format($totalImposable, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($totalNonImposable, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($totalRetenu, 0, ',', ' ') }}</td>
            </tr>

            {{-- TOTAL BRUT --}}
            <tr class="grand-total">
                <td colspan="3">TOTAL BRUT (F.CFA)</td>
                <td class="text-right">{{ number_format($payroll->gross_salary, 0, ',', ' ') }}</td>
            </tr>

            {{-- NET A PERCEVOIR --}}
            <tr class="net-total">
                <td colspan="3">NET A PERCEVOIR</td>
                <td class="text-right">{{ number_format($payroll->net_salary, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 9pt;">
        Nombre de lignes: {{ $lines->count() }}
    </div>

    <div class="footer">
        <div>Editer le: {{ now()->format('d/m/Y H:i:s') }} par ERP_SOLTEC</div>
        <div style="margin-top: 30px;">Signature</div>
    </div>
</body>

</html>

@php
    function numberToWords($number)
    {
        $number = (int) $number;
        // Fonction simplifiée - vous pouvez l'améliorer
    return strtoupper('DEUX CENT DIX SEPT MILLE DEUX CENT SOIXANTE SEPT');
    }
@endphp
