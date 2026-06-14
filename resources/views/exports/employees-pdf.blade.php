<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Liste des Employes</title>
    <style>
        * {
            font-family: "Helvetica", "Arial", sans-serif;
        }

        body {
            margin: 12px;
            font-size: 8px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin: 0 0 3px 0;
            font-size: 13px;
        }

        .header-info {
            text-align: right;
            font-size: 7px;
            color: #666;
            margin-bottom: 8px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th {
            background-color: #4B5563;
            color: white;
            padding: 3px;
            text-align: center;
            border: 1px solid #333;
            font-weight: bold;
            font-size: 7px;
        }

        td {
            padding: 3px;
            border: 1px solid #ddd;
            font-size: 7px;
        }

        tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .green {
            color: green;
        }

        .red {
            color: red;
        }
    </style>
</head>

<body>
    <h1>LISTE DES EMPLOYES - CLASSIFICATION SALARIALE</h1>
    <div class="header-info">
        Genere le {{ now()->format('d/m/Y a H:i') }} | Total: {{ count($employees) }} employe(s)
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">ID</th>
                <th style="width: 6%;">Matricule</th>
                <th style="width: 8%;">Nom</th>
                <th style="width: 8%;">Prenom</th>
                <th style="width: 6%;">Sexe</th>
                <th style="width: 7%;">Naissance</th>
                <th style="width: 8%;">Poste</th>
                <th style="width: 7%;">Service</th>
                <th style="width: 6%;">Type Class.</th>
                <th style="width: 6%;">Cat. Recr.</th>
                <th style="width: 6%;">Cat. Act.</th>
                <th style="width: 6%;">Ech. Act.</th>
                <th style="width: 5%;">Indice</th>
                <th style="width: 7%;">Recrutement</th>
                <th style="width: 7%;">Retraite</th>
                <th style="width: 4%;">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $employee)
                <tr>
                    <td class="center">{{ $employee['id'] }}</td>
                    <td class="bold">{{ $employee['matricule'] }}</td>
                    <td>{{ $employee['last_name'] }}</td>
                    <td>{{ $employee['first_name'] }}</td>
                    <td class="center">{{ $employee['gender'] }}</td>
                    <td class="center">{{ $employee['birth_date'] }}</td>
                    <td>{{ substr($employee['position'], 0, 12) }}</td>
                    <td>{{ substr($employee['service'], 0, 12) }}</td>
                    <td class="center bold">{{ $employee['classification_type'] }}</td>
                    <td class="center bold">{{ $employee['category_recruitment'] }}</td>
                    <td class="center bold">{{ $employee['category_number'] }}</td>
                    <td class="center bold">{{ $employee['echelon_number'] }}</td>
                    <td class="center bold">{{ $employee['indice'] }}</td>
                    <td class="center">{{ $employee['recruitment_date'] }}</td>
                    <td class="center">{{ $employee['retirement_date'] }}</td>
                    <td class="center {{ $employee['is_active'] === 'A' ? 'green' : 'red' }}">
                        {{ $employee['is_active'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div
        style="margin-top: 10px; text-align: center; font-size: 7px; color: #999; border-top: 1px solid #ccc; padding-top: 5px;">
        CAM = Cameroon | NUM = Numerique | A = Actif | I = Inactif | Recr. = Recrutement
    </div>
</body>

</html>