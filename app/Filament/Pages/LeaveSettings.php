<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;

class LeaveSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static string $view = 'filament.pages.leave-settings';
    protected static ?string $navigationGroup = '⚙️ Paramétrage';
    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return 'Congés & Permissions';
    }

    public function getTitle(): string
    {
        return 'Paramétrage des Congés & Permissions';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'cycle_months' => (int) SystemSetting::get('leave.cycle_months', 12),
            'min_cycles_before_first_leave' => (int) SystemSetting::get('leave.min_cycles_before_first_leave', 1),
            'base_days_fonctionnaire' => (int) SystemSetting::get('leave.base_days_fonctionnaire', 30),
            'base_days_contractuel' => (int) SystemSetting::get('leave.base_days_contractuel', 18),
            'base_days_stagiaire' => (int) SystemSetting::get('leave.base_days_stagiaire', 0),
            'bonus_every_n_cycles' => (int) SystemSetting::get('leave.bonus_every_n_cycles', 5),
            'bonus_days' => (int) SystemSetting::get('leave.bonus_days', 2),
            'bonus_cumulative' => (bool) SystemSetting::get('leave.bonus_cumulative', true),
            'rayon_x_days' => (int) SystemSetting::get('leave.rayon_x_days', 30),
            'permission_threshold_days' => (int) SystemSetting::get('leave.permission_threshold_days', 10),
            'permission_period_basis' => SystemSetting::get('leave.permission_period_basis', 'calendar_year'),
        ]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cycle de Service')
                    ->description("Base de calcul de l'ancienneté et de l'éligibilité au congé")
                    ->schema([
                        Forms\Components\TextInput::make('cycle_months')
                            ->label('Durée d\'un cycle de service (mois)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('12 = cycle annuel classique depuis la date de recrutement'),

                        Forms\Components\TextInput::make('min_cycles_before_first_leave')
                            ->label('Cycles requis avant le 1er congé')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->helperText('1 = le premier congé n\'est possible qu\'après 12 mois de service'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Congé Annuel — Jours de Base')
                    ->schema([
                        Forms\Components\TextInput::make('base_days_fonctionnaire')
                            ->label('Fonctionnaires (affecté/détaché)')
                            ->numeric()->minValue(0)->required()->suffix('jours'),

                        Forms\Components\TextInput::make('base_days_contractuel')
                            ->label('Contractuels (FP/structure)')
                            ->numeric()->minValue(0)->required()->suffix('jours'),

                        Forms\Components\TextInput::make('base_days_stagiaire')
                            ->label('Stagiaires')
                            ->numeric()->minValue(0)->required()->suffix('jours'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Bonus d\'Ancienneté')
                    ->schema([
                        Forms\Components\TextInput::make('bonus_every_n_cycles')
                            ->label('Tous les combien de cycles')
                            ->numeric()->minValue(0)->required()
                            ->helperText('5 = un bonus à chaque tranche de 5 cycles de service'),

                        Forms\Components\TextInput::make('bonus_days')
                            ->label('Jours de bonus')
                            ->numeric()->minValue(0)->required()->suffix('jours'),

                        Forms\Components\Toggle::make('bonus_cumulative')
                            ->label('Cumulatif')
                            ->helperText('Activé : +2j à 5 ans, +4j à 10 ans, +6j à 15 ans... Désactivé : toujours +2j max, une seule fois.')
                            ->inline(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Congé Rayon X')
                    ->schema([
                        Forms\Components\TextInput::make('rayon_x_days')
                            ->label('Jours accordés')
                            ->numeric()->minValue(0)->required()->suffix('jours')
                            ->helperText('S\'applique aux employés des services marqués "Éligible au Congé Rayon X" (voir fiche Service)'),
                    ]),

                Forms\Components\Section::make('Permission d\'Absence')
                    ->schema([
                        Forms\Components\TextInput::make('permission_threshold_days')
                            ->label('Seuil cumulé avant déduction du Congé Annuel')
                            ->numeric()->minValue(0)->required()->suffix('jours')
                            ->helperText('Au-delà de ce nombre de jours cumulés sur la période, les jours supplémentaires sont déduits du Congé Annuel'),

                        Forms\Components\Select::make('permission_period_basis')
                            ->label('Période de référence pour le cumul')
                            ->options([
                                'calendar_year' => 'Année civile (exercice budgétaire)',
                                'service_cycle' => 'Cycle de service de l\'employé (glissant)',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            SystemSetting::set('leave.' . $key, $value, 'leave');
        }

        Notification::make()
            ->title('Paramètres enregistrés')
            ->success()
            ->send();
    }
}
