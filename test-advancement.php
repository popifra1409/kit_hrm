<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\AdvancementCheckService;
use App\Models\Employee;

echo "=== TEST ALERTES AVANCEMENT ===\n\n";

// Vérifier quelques employés
$employees = Employee::where('is_active', true)
    ->whereNotNull('current_echelon')
    ->whereNotNull('echelon_start_date')
    ->limit(5)
    ->get();

echo "Employés avec échelon et date: " . $employees->count() . "\n\n";

foreach ($employees as $employee) {
    echo "- {$employee->full_name}\n";
    echo "  Échelon: {$employee->current_echelon}\n";
    echo "  Date début: {$employee->echelon_start_date}\n";

    $service = new AdvancementCheckService();
    $months = $service->getMonthsInCurrentEchelon($employee);
    $required = $service->getRequiredMonths($employee);

    echo "  Mois dans échelon: {$months} / {$required} requis\n";
    echo "  Éligible: " . ($service->isEligibleForAdvancement($employee) ? 'OUI' : 'NON') . "\n\n";
}

// Récupérer tous les éligibles
$service = new AdvancementCheckService();
$eligible = $service->checkAllEligibleEmployees();

echo "\n=== RÉSULTAT FINAL ===\n";
echo "Total éligibles: " . $eligible->count() . "\n";
