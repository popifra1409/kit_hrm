<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\EmployeeAdvancementHistory;
use App\Services\NotificationService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    // Variable pour éviter les boucles infinies
    protected static $updating = false;
    protected static $generatingQr = false;

    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        // Générer le QR Code automatiquement à la création
        $this->generateQrCode($employee);
    }

    /**
     * Handle the Employee "updating" event (avant la modification).
     */
    public function updating(Employee $employee): void
    {
        // Éviter les boucles infinies
        if (self::$updating) {
            return;
        }

        // Récupérer les valeurs originales avant modification
        $original = $employee->getOriginal();
        $dirty = $employee->getDirty();

        // NE PLUS GÉRER LES AFFECTATIONS ICI
        // (département, service, poste sont gérés par EmployeeAffectationObserver)

        // GARDER UNIQUEMENT LES AVANCEMENTS

        // Vérifier changement d'échelon
        if (isset($dirty['current_echelon']) && $original['current_echelon'] != $dirty['current_echelon']) {
            $this->recordEchelonChange($employee, $original, $dirty);

            // Mettre à jour les dates DIRECTEMENT dans les dirty attributes
            $employee->echelon_start_date = now();
            $employee->last_advancement_date = now();
        }

        // Vérifier changement de catégorie
        if (isset($dirty['category']) && $original['category'] != $dirty['category']) {
            $this->recordCategoryChange($employee, $original, $dirty);
        }
    }

    /**
     * Handle the Employee "updated" event (après la modification).
     */
    public function updated(Employee $employee): void
    {
        // Régénérer le QR Code si matricule ou date de naissance changent
        if ($employee->wasChanged(['matricule', 'birth_date'])) {
            $this->generateQrCode($employee);
        }
    }

    /**
     * Générer le QR Code de l'employé
     */
    protected function generateQrCode(Employee $employee): void
    {
        // Éviter les boucles infinies
        if (self::$generatingQr) {
            return;
        }

        // Vérifier que les données nécessaires sont présentes
        if (!$employee->matricule) {
            Log::warning('Impossible de générer le QR Code : matricule manquant', [
                'employee_id' => $employee->id
            ]);
            return;
        }

        try {
            self::$generatingQr = true;

            // Données à encoder dans le QR Code
            $data = [
                'type' => 'employee',
                'matricule' => $employee->matricule,
                'birth_date' => $employee->birth_date?->format('d/m/Y'),
                'full_name' => $employee->full_name,
                'hire_date' => $employee->hire_date?->format('d/m/Y'),
                'department' => $employee->department?->name,
                'service' => $employee->service?->name,
                'position' => $employee->position?->name,
                'generated_at' => now()->format('d/m/Y H:i:s'),
            ];

            // Encodage JSON des données
            $jsonData = json_encode($data);

            // Générer l'image QR Code en SVG (pas besoin ImageMagick)
            $filename = 'qrcodes/employee-' . $employee->matricule . '.svg';

            $qrCode = QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($jsonData);

            // Sauvegarder l'image
            Storage::disk('public')->put($filename, $qrCode);

            // Mettre à jour l'employé avec saveQuietly pour éviter la récursion
            $employee->qr_code_path = $filename;
            $employee->qr_code_data = $jsonData;
            $employee->saveQuietly();

            Log::info('QR Code généré avec succès', [
                'employee_id' => $employee->id,
                'matricule' => $employee->matricule,
                'qr_code_path' => $filename
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur génération QR Code: ' . $e->getMessage(), [
                'employee_id' => $employee->id,
                'matricule' => $employee->matricule,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            self::$generatingQr = false;
        }
    }

    /**
     * Enregistrer le changement d'échelon
     */
    protected function recordEchelonChange(Employee $employee, array $original, array $dirty): void
    {
        // Utiliser les valeurs actuelles de l'employé si non présentes dans $original
        $oldCategory = $original['category'] ?? $employee->getOriginal('category');
        $newCategory = $employee->category;

        // Calculer le nouveau salaire
        $newSalary = $this->calculateSalary($newCategory, $dirty['current_echelon']);
        $oldSalary = $this->calculateSalary($oldCategory, $original['current_echelon']);

        EmployeeAdvancementHistory::recordAdvancement(
            $employee,
            'echelon',
            [
                'echelon' => $original['current_echelon'],
                'category' => $oldCategory,
                'salary' => $oldSalary,
            ],
            [
                'echelon' => $dirty['current_echelon'],
                'category' => $newCategory,
                'salary' => $newSalary,
            ],
            [
                'effective_date' => now(),
                'is_automatic' => true,
                'reason' => 'Avancement d\'échelon',
            ]
        );

        // Envoyer notification
        $this->sendNotification($employee, 'advancement_due', [
            'employee_name' => $employee->full_name,
            'current_echelon' => $original['current_echelon'],
            'new_echelon' => $dirty['current_echelon'],
            'effective_date' => now()->format('d/m/Y'),
        ]);
    }

    /**
     * Enregistrer le changement de catégorie
     */
    protected function recordCategoryChange(Employee $employee, array $original, array $dirty): void
    {
        $echelon = $employee->current_echelon ?? 1;
        $newSalary = $this->calculateSalary($dirty['category'], $echelon);
        $oldSalary = $this->calculateSalary($original['category'], $echelon);

        EmployeeAdvancementHistory::recordAdvancement(
            $employee,
            'category',
            [
                'category' => $original['category'],
                'salary' => $oldSalary,
            ],
            [
                'category' => $dirty['category'],
                'salary' => $newSalary,
            ],
            [
                'effective_date' => now(),
                'is_automatic' => false,
                'reason' => 'Changement de catégorie',
            ]
        );

        // Envoyer notification
        $this->sendNotification($employee, 'assignment_change', [
            'employee_name' => $employee->full_name,
            'old_value' => 'Catégorie ' . $original['category'],
            'new_value' => 'Catégorie ' . $dirty['category'],
            'change_type' => 'catégorie',
        ]);
    }

    /**
     * Calculer le salaire selon la grille
     */
    protected function calculateSalary($category, $echelon): ?float
    {
        // Vérifier que category et echelon ne sont pas null
        if (!$category || !$echelon) {
            return null;
        }

        $salaryGrid = \App\Models\SalaryGrid::where('category', $category)
            ->where('echelon', $echelon)
            ->first();

        return $salaryGrid?->base_salary;
    }

    /**
     * Envoyer une notification
     */
    protected function sendNotification(Employee $employee, string $templateCode, array $variables): void
    {
        if ($employee->user) {
            try {
                $notificationService = new NotificationService();
                $notificationService->send(
                    $employee->user,
                    $templateCode,
                    $variables,
                    route('filament.admin.resources.employees.edit', $employee),
                    'Voir les détails'
                );
            } catch (\Exception $e) {
                Log::error('Erreur envoi notification: ' . $e->getMessage());
            }
        }
    }
}
