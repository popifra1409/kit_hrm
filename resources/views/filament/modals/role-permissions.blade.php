<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @php
            $permissionsByModule = $role->permissions->groupBy('module');
        @endphp

        @foreach ($permissionsByModule as $module => $permissions)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-lg mb-3 text-gray-900 dark:text-gray-100">
                    @switch($module)
                        @case('users')
                            👥 Utilisateurs
                        @break

                        @case('employees')
                            👨‍💼 Employés
                        @break

                        @case('leaves')
                            📅 Congés & Absences
                        @break

                        @case('payroll')
                            💰 Paie
                        @break

                        @case('documents')
                            📄 Documents
                        @break

                        @case('evaluations')
                            ⭐ Évaluations
                        @break

                        @case('trainings')
                            📚 Formations
                        @break

                        @case('procurement')
                            🏗️ Marchés Publics
                        @break

                        @case('contracts')
                            📋 Contrats
                        @break

                        @case('structure')
                            🏢 Structure
                        @break

                        @case('reports')
                            📊 Rapports
                        @break

                        @case('settings')
                            ⚙️ Paramètres
                        @break

                        @default
                            {{ ucfirst($module) }}
                    @endswitch
                </h3>
                <ul class="space-y-1">
                    @foreach ($permissions as $permission)
                        <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>{{ $permission->description ?? $permission->name }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <p class="text-sm text-blue-800 dark:text-blue-200">
            <strong>Total :</strong> {{ $role->permissions->count() }} permission(s) attribuée(s) à ce rôle.
        </p>
    </div>
</div>
