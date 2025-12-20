<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use Carbon\Carbon;

class InitializeEmployeeEchelonsSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::whereNull('current_echelon')->get();

        foreach ($employees as $employee) {
            // Calculer l'ancienneté
            $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date) : now();
            $yearsOfService = $hireDate->diffInYears(now());

            // Déterminer l'échelon selon l'ancienneté
            // Règle simple: 1 échelon tous les 2 ans, max 12
            $echelon = min(floor($yearsOfService / 2) + 1, 12);

            // Date de début de l'échelon actuel
            $echelonStartDate = $hireDate->copy()->addYears(($echelon - 1) * 2);

            $employee->update([
                'current_echelon' => $echelon,
                'echelon_start_date' => $echelonStartDate,
                'last_advancement_date' => $echelon > 1 ? $echelonStartDate : null,
            ]);
        }

        $this->command->info("✅ Échelons initialisés pour {$employees->count()} employés");
    }
}
