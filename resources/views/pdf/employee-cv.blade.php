<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CV - {{ $employee->full_name }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; }
        .header { background: #1e3a5f; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 22px; }
        .header p { margin: 4px 0 0; font-size: 13px; opacity: 0.9; }
        .content { padding: 24px 32px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 13px; font-weight: bold; color: #1e3a5f; text-transform: uppercase;
            border-bottom: 2px solid #1e3a5f; padding-bottom: 4px; margin-bottom: 10px; }
        table.info { width: 100%; border-collapse: collapse; }
        table.info td { padding: 4px 8px 4px 0; vertical-align: top; }
        table.info td.label { color: #6b7280; width: 160px; }
        table.diplomas { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.diplomas th { text-align: left; background: #f3f4f6; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #6b7280; }
        table.diplomas td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-verified { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .footer { text-align: center; font-size: 9px; color: #9ca3af; padding: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $employee->full_name }}</h1>
        <p>{{ $employee->matricule }} — {{ $employee->qualification?->name ?? 'Qualification non renseignée' }}</p>
    </div>

    <div class="content">
        <div class="section">
            <div class="section-title">Informations Générales</div>
            <table class="info">
                <tr>
                    <td class="label">Date de naissance</td>
                    <td>{{ $employee->birth_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="label">Sexe</td>
                    <td>{{ $employee->gender === 'M' ? 'Masculin' : ($employee->gender === 'F' ? 'Féminin' : '—') }}</td>
                </tr>
                <tr>
                    <td class="label">Téléphone</td>
                    <td>{{ $employee->phone ?? '—' }}</td>
                    <td class="label">Email</td>
                    <td>{{ $employee->email ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Adresse</td>
                    <td colspan="3">{{ $employee->address ?? '—' }}{{ $employee->city ? ', ' . $employee->city : '' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Affectation Professionnelle</div>
            <table class="info">
                <tr>
                    <td class="label">Corps de métier</td>
                    <td>{{ $employee->tradeBody?->name ?? '—' }}</td>
                    <td class="label">Qualification</td>
                    <td>{{ $employee->qualification?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Poste hiérarchique</td>
                    <td>{{ $employee->jobTitle?->name ?? '—' }}</td>
                    <td class="label">Service</td>
                    <td>{{ $employee->currentService?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Date de recrutement</td>
                    <td>{{ $employee->recruitment_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="label">Ancienneté</td>
                    <td>{{ $employee->anciennete }} an(s)</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Diplômes Principaux</div>
            <table class="info">
                <tr>
                    <td class="label">Diplôme de recrutement</td>
                    <td>
                        @if($recruitmentDiploma)
                            {{ $recruitmentDiploma->title }} — {{ $recruitmentDiploma->institution }} ({{ $recruitmentDiploma->year_obtained }})
                            <span class="badge {{ $recruitmentDiploma->is_verified ? 'badge-verified' : 'badge-pending' }}">
                                {{ $recruitmentDiploma->is_verified ? 'Vérifié' : 'Non vérifié' }}
                            </span>
                        @else
                            <em>Non renseigné</em>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Diplôme le plus élevé</td>
                    <td>
                        @if($highestDiploma)
                            {{ $highestDiploma->title }} — {{ $highestDiploma->institution }} ({{ $highestDiploma->year_obtained }})
                            <span class="badge {{ $highestDiploma->is_verified ? 'badge-verified' : 'badge-pending' }}">
                                {{ $highestDiploma->is_verified ? 'Vérifié' : 'Non vérifié' }}
                            </span>
                        @else
                            <em>Non renseigné</em>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Formations Complémentaires</div>
            @if($trainings->isNotEmpty())
                <table class="diplomas">
                    <thead>
                        <tr>
                            <th>Intitulé</th>
                            <th>Établissement</th>
                            <th>Année</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trainings as $training)
                            <tr>
                                <td>{{ $training->title }}</td>
                                <td>{{ $training->institution }}</td>
                                <td>{{ $training->year_obtained }}</td>
                                <td>
                                    <span class="badge {{ $training->is_verified ? 'badge-verified' : 'badge-pending' }}">
                                        {{ $training->is_verified ? 'Vérifié' : 'Non vérifié' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p><em>Aucune formation complémentaire renseignée.</em></p>
            @endif
        </div>
    </div>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ \App\Models\SystemSetting::get('hospital_name', '') }}
    </div>
</body>
</html>