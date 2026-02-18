<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\SystemSetting::get('hospital_name', config('app.name')) }} - Système de Gestion RH</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-50 via-white to-amber-50 min-h-screen">
    @php
        // Récupération des paramètres
        $hospitalName = \App\Models\SystemSetting::get('hospital_name', 'Centre Hospitalier Universitaire');
        $hospitalAcronym = \App\Models\SystemSetting::get('hospital_short_name', 'CHU');
        $hospitalCity = \App\Models\SystemSetting::get('hospital_city', 'Yaoundé');
        $hospitalSlogan = \App\Models\SystemSetting::get(
            'hospital_slogan',
            'Système de Gestion des Ressources Humaines',
        );
        $logoPath = \App\Models\SystemSetting::get('logo_path');

        // Statistiques dynamiques
        $employeesCount = \App\Models\Employee::where('is_active', true)->count();
        $departmentsCount = \App\Models\Department::count();
        $servicesCount = \App\Models\Service::count();
    @endphp

    <div class="container mx-auto px-4 py-16">
        <!-- Header -->
        <div class="text-center mb-16">
            <div class="flex justify-center mb-6">
                @if ($logoPath)
                    <!-- Logo de l'hôpital -->
                    <div class="bg-white p-4 rounded-2xl shadow-xl">
                        <img src="{{ asset('storage/' . $logoPath) }}" alt="Logo {{ $hospitalAcronym }}"
                            class="h-16 w-auto">
                    </div>
                @else
                    <!-- Icône par défaut -->
                    <div class="bg-gradient-to-r from-blue-600 to-amber-500 p-4 rounded-2xl shadow-xl">
                        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                @endif
            </div>

            <h1 class="text-5xl font-bold text-gray-900 mb-4">
                🏥 {{ $hospitalAcronym }}
            </h1>
            <p class="text-xl text-gray-600 mb-2">
                {{ $hospitalSlogan }}
            </p>
            <p class="text-lg text-gray-500">
                {{ $hospitalName }} - {{ $hospitalCity }}
            </p>
        </div>

        <!-- Bouton Connexion -->
        <div class="text-center mb-16">
            @auth
                <a href="/admin"
                    class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Accéder au Tableau de Bord
                </a>
            @else
                <a href="/admin/login"
                    class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Se Connecter
                </a>
            @endauth
        </div>

        <!-- Fonctionnalités -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
            <!-- Gestion du Personnel -->
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition duration-200">
                <div class="flex justify-center mb-4">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2 text-center">Gestion du Personnel</h3>
                <p class="text-gray-600 text-sm text-center">{{ $employeesCount }} employés • Affectations • Avancements
                </p>
            </div>

            <!-- Congés & Absences -->
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition duration-200">
                <div class="flex justify-center mb-4">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2 text-center">Congés & Absences</h3>
                <p class="text-gray-600 text-sm text-center">Workflow d'approbation • Pointage • Remplacements</p>
            </div>

            <!-- Paie -->
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition duration-200">
                <div class="flex justify-center mb-4">
                    <div class="bg-amber-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2 text-center">Gestion de la Paie</h3>
                <p class="text-gray-600 text-sm text-center">Bulletins • DIPE CNPS • Synthèses</p>
            </div>

            <!-- Développement RH -->
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition duration-200">
                <div class="flex justify-center mb-4">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2 text-center">Développement RH</h3>
                <p class="text-gray-600 text-sm text-center">Évaluations • Formations • Compétences</p>
            </div>
        </div>

        <!-- Statistiques Dynamiques -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Notre Structure</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">{{ $employeesCount }}</div>
                    <div class="text-gray-600">Employés Actifs</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-green-600 mb-2">{{ $departmentsCount }}</div>
                    <div class="text-gray-600">Départements</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-amber-600 mb-2">{{ $servicesCount }}</div>
                    <div class="text-gray-600">Services</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-gray-600">
            <p class="mb-2">© {{ date('Y') }} {{ $hospitalName }}</p>
            <p class="text-sm">Version 1.0 • KITHRM {{ app()->version() }} • PHP {{ PHP_VERSION }}</p>
        </div>
    </div>
</body>

</html>
