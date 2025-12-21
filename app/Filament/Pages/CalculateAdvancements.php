<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use App\Models\Employee;
use App\Models\Advancement;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;

class CalculateAdvancements extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Calculer Avancements';
    protected static ?string $title = 'Calculer les Avancements Automatiques';
    protected static ?string $navigationGroup = '👥 Gestion du Personnel';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.calculate-advancements';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getEligibleEmployeesQuery())
            ->columns([
                TextColumn::make('matricule')
                    ->label('Matricule')
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Nom complet')
                    ->searchable(['first_name', 'last_name']),

                TextColumn::make('qualification')
                    ->label('Qualification')
                    ->wrap(),

                TextColumn::make('category_current')
                    ->label('Catégorie actuelle')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('anciennete')
                    ->label('Ancienneté (ans)')
                    ->getStateUsing(
                        fn($record) => $record->recruitment_date ?
                            $record->recruitment_date->diffInYears(now()) : 0
                    )
                    ->badge()
                    ->color('success'),

                BadgeColumn::make('disciplinary_points')
                    ->label('Points disciplinaires')
                    ->colors([
                        'success' => 0,
                        'warning' => fn($state) => $state > 0 && $state < 3,
                        'danger' => fn($state) => $state >= 3,
                    ]),

                TextColumn::make('eligible')
                    ->label('Éligible')
                    ->getStateUsing(
                        fn($record) =>
                        $record->recruitment_date->diffInYears(now()) >= 2 &&
                            $record->disciplinary_points == 0 ? 'Oui' : 'Non'
                    )
                    ->badge()
                    ->colors([
                        'success' => 'Oui',
                        'danger' => 'Non',
                    ]),
            ])
            ->actions([
                Action::make('create_advancement')
                    ->label('Créer avancement')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->visible(
                        fn($record) =>
                        $record->recruitment_date->diffInYears(now()) >= 2 &&
                            $record->disciplinary_points == 0
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Créer un avancement automatique')
                    ->modalDescription(fn($record) => "Créer un avancement pour {$record->full_name} ?")
                    ->action(function ($record) {
                        $this->createAdvancement($record);
                    }),
            ]);
    }

    protected function getEligibleEmployeesQuery()
    {
        return Employee::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereNotNull('recruitment_date')
            ->orderBy('recruitment_date');
    }

    protected function createAdvancement($employee)
    {
        try {
            // Calculer le nouvel échelon
            $currentEchelon = $employee->echelon_number ?? 1;
            $newEchelon = min($currentEchelon + 1, 15); // Max 15 échelons

            $categoryNumber = $employee->category_number ?? 7;
            $newCategory = $categoryNumber . '/' . $newEchelon;

            // Créer l'avancement
            $advancement = Advancement::create([
                'employee_id' => $employee->id,
                'previous_category' => $employee->category_current,
                'new_category' => $newCategory,
                'previous_echelon' => $currentEchelon,
                'new_echelon' => $newEchelon,
                'advancement_date' => now(),
                'type' => 'automatic',
                'reason' => 'Avancement automatique (2 ans d\'ancienneté, 0 point disciplinaire)',
                'status' => 'pending',
            ]);

            Notification::make()
                ->title('Avancement créé !')
                ->success()
                ->body("Avancement créé pour {$employee->full_name}")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->danger()
                ->body('Erreur lors de la création : ' . $e->getMessage())
                ->send();
        }
    }

    public function generateAllAdvancements()
    {
        $eligibleEmployees = Employee::where('is_active', true)
            ->where('status', 'active')
            ->where('disciplinary_points', 0)
            ->get()
            ->filter(function ($employee) {
                return $employee->recruitment_date &&
                    $employee->recruitment_date->diffInYears(now()) >= 2;
            });

        $count = 0;
        foreach ($eligibleEmployees as $employee) {
            // Vérifier si un avancement en attente n'existe pas déjà
            $existingPending = Advancement::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->exists();

            if (!$existingPending) {
                $this->createAdvancement($employee);
                $count++;
            }
        }

        Notification::make()
            ->title('Avancements générés !')
            ->success()
            ->body("{$count} avancements automatiques ont été créés.")
            ->send();
    }
}
