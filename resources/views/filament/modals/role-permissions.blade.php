<div class="space-y-4">
    @php
    $permissionsByModule = $role->permissions->groupBy('module');
    @endphp

    @if($permissionsByModule->isEmpty())
    <div class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Aucune permission attribuée</p>
    </div>
    @else
    @foreach($permissionsByModule as $module => $permissions)
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
            {{ match($module) {
                        'system' => '⚙️ Système',
                        'users' => '👤 Utilisateurs',
                        'employees' => '👥 Employés',
                        'health' => '🏥 Santé',
                        'contracts' => '📄 Contrats',
                        'leaves' => '🏖️ Congés',
                        'payroll' => '💰 Paie',
                        'evaluations' => '⭐ Évaluations',
                        'trainings' => '🎓 Formations',
                        'documents' => '📂 Documents',
                        'reports' => '📊 Rapports',
                        'settings' => '⚙️ Paramètres',
                        'structure' => '🏢 Structure',
                        default => $module,
                    } }}
            <span class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-2">
                ({{ $permissions->count() }})
            </span>
        </h4>
        <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
            @foreach($permissions as $permission)
            <li class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ $permission->description }}
            </li>
            @endforeach
        </ul>
    </div>
    @endforeach

    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    Total : {{ $role->permissions->count() }} permissions
                </p>
                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                    Ce rôle est attribué à {{ \App\Models\User::role($role->name)->count() }} utilisateur(s)
                </p>
            </div>
        </div>
    </div>
    @endif
</div>