<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\TradeBody;
use App\Models\Qualification;
use App\Models\Service;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class ProfileSearch extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static string $view = 'filament.pages.profile-search';
    protected static ?string $navigationGroup = '👥 Gestion du Personnel';
    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Recherche de Profils';
    }

    public function getTitle(): string
    {
        return 'Recherche de Profils Professionnels';
    }

    public ?string $search = null;
    public ?int $tradeBodyId = null;
    public ?int $qualificationId = null;
    public ?int $serviceId = null;
    public ?int $minYearsExperience = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(5)
                    ->schema([
                        Forms\Components\TextInput::make('search')
                            ->label('Nom ou Matricule')
                            ->placeholder('Rechercher...')
                            ->live(debounce: 500),

                        Forms\Components\Select::make('tradeBodyId')
                            ->label('Corps de Métier')
                            ->options(fn() => TradeBody::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Tous')
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('qualificationId', null)),

                        Forms\Components\Select::make('qualificationId')
                            ->label('Qualification')
                            ->options(function ($get) {
                                if (!$get('tradeBodyId')) {
                                    return Qualification::where('is_active', true)->pluck('name', 'id');
                                }
                                return Qualification::where('trade_body_id', $get('tradeBodyId'))
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->placeholder('Toutes')
                            ->live(),

                        Forms\Components\Select::make('serviceId')
                            ->label('Service (actuel)')
                            ->options(fn() => Service::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Tous')
                            ->live(),

                        Forms\Components\TextInput::make('minYearsExperience')
                            ->label('Ancienneté min. (ans)')
                            ->numeric()
                            ->minValue(0)
                            ->live(),
                    ]),
            ]);
    }

    public function getViewData(): array
    {
        $query = Employee::where('is_active', true)
            ->with(['tradeBody', 'qualification', 'jobTitle', 'currentService', 'department']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('matricule', 'like', "%{$this->search}%")
                    ->orWhere('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->tradeBodyId) {
            $query->where('trade_body_id', $this->tradeBodyId);
        }

        if ($this->qualificationId) {
            $query->where('qualification_id', $this->qualificationId);
        }

        if ($this->serviceId) {
            $query->where('current_service_id', $this->serviceId);
        }

        $employees = $query->get();

        if ($this->minYearsExperience) {
            $employees = $employees->filter(function ($employee) {
                return $employee->recruitment_date
                    && $employee->recruitment_date->diffInYears(now()) >= $this->minYearsExperience;
            })->values();
        }

        return [
            'employees' => $employees,
            'total' => $employees->count(),
        ];
    }
}
