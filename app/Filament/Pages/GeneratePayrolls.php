<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollLine;
use App\Models\PayrollItem;
use App\Models\SalaryGrid;

class GeneratePayrolls extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Générer Bulletins';
    protected static ?string $title = 'Générer les Bulletins de Paie';
    protected static ?string $navigationGroup = '💰 Gestion de la Paie';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.generate-payrolls';

    public $month;
    public $year;

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('month')
                ->label('Mois')
                ->options([
                    1 => 'Janvier',
                    2 => 'Février',
                    3 => 'Mars',
                    4 => 'Avril',
                    5 => 'Mai',
                    6 => 'Juin',
                    7 => 'Juillet',
                    8 => 'Août',
                    9 => 'Septembre',
                    10 => 'Octobre',
                    11 => 'Novembre',
                    12 => 'Décembre'
                ])
                ->required()
                ->native(false)
                ->default(now()->month),

            Select::make('year')
                ->label('Année')
                ->options(array_combine(range(2020, 2030), range(2020, 2030)))
                ->required()
                ->native(false)
                ->default(now()->year),
        ];
    }

    public function generateAll()
    {
        $this->validate();

        try {
            $employees = Employee::where('is_active', true)
                ->where('status', 'active')
                ->get();

            $created = 0;
            $updated = 0;
            $errors = 0;

            foreach ($employees as $employee) {
                try {
                    $result = $this->generatePayrollForEmployee($employee);
                    if ($result === 'created') $created++;
                    if ($result === 'updated') $updated++;
                } catch (\Exception $e) {
                    $errors++;
                    \Log::error("Erreur génération paie pour {$employee->full_name}: " . $e->getMessage());
                }
            }

            Notification::make()
                ->title('Génération terminée !')
                ->success()
                ->body("{$created} bulletins créés, {$updated} mis à jour" . ($errors > 0 ? ", {$errors} erreurs" : ""))
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->danger()
                ->body('Erreur: ' . $e->getMessage())
                ->send();
        }
    }

    protected function generatePayrollForEmployee($employee)
    {
        // Vérifier si un bulletin existe déjà
        $payroll = Payroll::firstOrNew([
            'employee_id' => $employee->id,
            'month' => $this->month,
            'year' => $this->year,
        ]);

        $isNew = !$payroll->exists;

        // Récupérer le salaire de base depuis la grille
        $baseSalary = $employee->getBaseSalaryFromGrid();

        if ($baseSalary == 0) {
            throw new \Exception("Salaire de base non trouvé pour catégorie {$employee->category_number}/{$employee->echelon_number}");
        }

        $payroll->base_salary = $baseSalary;
        $payroll->status = 'draft';
        $payroll->save();

        // Supprimer les lignes existantes
        $payroll->lines()->delete();

        // Générer les lignes du bulletin
        $this->generatePayrollLines($payroll, $employee, $baseSalary);

        // Recalculer les totaux
        $payroll->recalculate();

        return $isNew ? 'created' : 'updated';
    }

    protected function generatePayrollLines($payroll, $employee, $baseSalary)
    {
        $items = PayrollItem::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $taxableSalary = 0;
        $cnpsSalary = 0;

        // Première passe : calculer les gains
        foreach ($items->where('type', 'gain') as $item) {
            $amount = $item->calculateAmount($baseSalary);

            if ($amount > 0) {
                PayrollLine::create([
                    'payroll_id' => $payroll->id,
                    'payroll_item_id' => $item->id,
                    'item_name' => $item->name,
                    'type' => $item->type,
                    'is_taxable' => $item->is_taxable,
                    'is_subject_to_cnps' => $item->is_subject_to_cnps,
                    'amount' => $amount,
                    'display_order' => $item->display_order,
                ]);

                if ($item->is_taxable) {
                    $taxableSalary += $amount;
                }
                if ($item->is_subject_to_cnps) {
                    $cnpsSalary += $amount;
                }
            }
        }

        // Calculer l'IRPP
        $irpp = Payroll::calculateIRPP($taxableSalary);

        // Calculer le CAC (10% de l'IRPP)
        $cac = $irpp * 0.10;

        // Deuxième passe : calculer les retenues
        foreach ($items->where('type', 'deduction') as $item) {
            $amount = 0;

            if ($item->code === 'PENSION') {
                // CNPS Employé : 4.2% du salaire cotisable
                $amount = ($cnpsSalary * 4.2) / 100;
            } elseif ($item->code === 'IRPP') {
                $amount = $irpp;
            } elseif ($item->code === 'CAC') {
                $amount = $cac;
            } else {
                $amount = $item->calculateAmount($baseSalary, $taxableSalary, $cnpsSalary);
            }

            if ($amount > 0) {
                PayrollLine::create([
                    'payroll_id' => $payroll->id,
                    'payroll_item_id' => $item->id,
                    'item_name' => $item->name,
                    'type' => $item->type,
                    'is_taxable' => $item->is_taxable,
                    'is_subject_to_cnps' => $item->is_subject_to_cnps,
                    'amount' => $amount,
                    'display_order' => $item->display_order,
                ]);
            }
        }
    }
}
