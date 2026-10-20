<?php

namespace App\Filament\Resources\EmployeeResource\Components;

use App\Models\Direction;
use App\Models\SubDirection;
use App\Support\OrganizationalAssignment;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;

/**
 * Chaîne d'affectation des formulaires employé, dans l'ordre hiérarchique :
 * Branche → Direction → Département (médical) ou Sous-direction (administratif)
 * → Service → Unité / Secteur.
 *
 * C'est la même chaîne que l'API du recensement (voir OrganizationalAssignment),
 * appliquée ici aux écrans RH. Seuls département, service et secteur sont stockés
 * sur l'employé : la direction et la sous-direction servent à restreindre la
 * sélection et se déduisent du service retenu.
 *
 * Direction et sous-direction restent facultatives : sur les données actuelles,
 * 9 des 14 services administratifs ne sont rattachés à aucune sous-direction et
 * les départements médicaux ne dépendent que d'une seule direction. Une obligation
 * à ces niveaux rendrait ces services introuvables au formulaire ; ils se contentent
 * de filtrer la suite dès qu'ils sont choisis.
 */
class OrganizationalCascade
{
    /**
     * @param bool $requireService Service exigé à l'enregistrement (création) ;
     *                             à l'édition il reste recommandé, avec avertissement visible.
     */
    public static function fields(bool $requireService): array
    {
        $fields = [
            self::branch(),
            Grid::make(2)->schema([
                self::direction(),
                self::department(),
                self::subDirection(),
                self::service($requireService),
                self::sector(),
            ]),
        ];

        if (!$requireService) {
            $fields[] = self::missingAffectationWarning();
        }

        return $fields;
    }

    private static function branch(): ToggleButtons
    {
        // Branche de navigation uniquement : la colonne n'existe pas dans employees.
        return ToggleButtons::make('branch_type')
            ->label('Branche d\'Affectation')
            ->options([
                OrganizationalAssignment::BRANCH_MEDICAL => 'Branche Médicale',
                OrganizationalAssignment::BRANCH_ADMINISTRATIVE => 'Branche Administrative',
            ])
            ->icons([
                OrganizationalAssignment::BRANCH_MEDICAL => 'heroicon-o-heart',
                OrganizationalAssignment::BRANCH_ADMINISTRATIVE => 'heroicon-o-building-office',
            ])
            ->colors([
                OrganizationalAssignment::BRANCH_MEDICAL => 'success',
                OrganizationalAssignment::BRANCH_ADMINISTRATIVE => 'primary',
            ])
            ->inline()
            ->required()
            ->dehydrated(false)
            ->reactive()
            ->live()
            ->columnSpanFull()
            ->default(OrganizationalAssignment::BRANCH_MEDICAL)
            ->afterStateUpdated(fn(Set $set) => self::reset($set, [
                'direction_id',
                'department_id',
                'sub_direction_id',
                'current_service_id',
                'sector_id',
            ]));
    }

    private static function direction(): Select
    {
        // Une direction n'est proposée que si elle a réellement des enfants exploitables
        // pour la branche choisie, jamais d'après sa colonne « type ».
        return Select::make('direction_id')
            ->label('Direction')
            ->dehydrated(false)
            ->options(fn(Get $get) => Direction::query()
                ->where('is_active', true)
                ->whereHas(
                    $get('branch_type') === OrganizationalAssignment::BRANCH_MEDICAL ? 'departments' : 'subDirections',
                    fn($query) => $query->where('is_active', true)
                )
                ->orderBy('order')
                ->orderBy('name')
                ->pluck('name', 'id'))
            ->searchable()
            ->preload()
            ->native(false)
            ->placeholder('— Toutes les directions —')
            ->reactive()
            ->afterStateUpdated(fn(Set $set) => self::reset($set, [
                'department_id',
                'sub_direction_id',
                'current_service_id',
                'sector_id',
            ]))
            ->helperText('Facultatif : restreint le département / la sous-direction puis le service.');
    }

    private static function department(): Select
    {
        return Select::make('department_id')
            ->label('Département')
            ->visible(fn(Get $get) => $get('branch_type') === OrganizationalAssignment::BRANCH_MEDICAL)
            ->relationship('department', 'name', fn($query, Get $get) => $query
                ->where('is_active', true)
                ->when(
                    $get('direction_id'),
                    fn($q) => $q->where('direction_id', $get('direction_id'))
                )
                ->orderBy('order')
                ->orderBy('name'))
            ->searchable()
            ->preload()
            ->native(false)
            ->placeholder('— Tous les départements —')
            ->reactive()
            ->afterStateUpdated(fn(Set $set) => self::reset($set, ['current_service_id', 'sector_id']))
            ->helperText('Facultatif : restreint la liste des services médicaux.');
    }

    private static function subDirection(): Select
    {
        return Select::make('sub_direction_id')
            ->label('Sous-Direction')
            ->dehydrated(false)
            ->visible(fn(Get $get) => $get('branch_type') === OrganizationalAssignment::BRANCH_ADMINISTRATIVE)
            ->options(fn(Get $get) => SubDirection::query()
                ->where('is_active', true)
                ->when(
                    $get('direction_id'),
                    fn($q) => $q->where('direction_id', $get('direction_id'))
                )
                ->orderBy('order')
                ->orderBy('name')
                ->pluck('name', 'id'))
            ->searchable()
            ->preload()
            ->native(false)
            ->placeholder('— Toutes les sous-directions —')
            ->reactive()
            ->afterStateUpdated(fn(Set $set) => self::reset($set, ['current_service_id', 'sector_id']))
            ->helperText('Facultatif : restreint la liste des services administratifs.');
    }

    private static function service(bool $requireService): Select
    {
        return Select::make('current_service_id')
            ->label('Service')
            ->required($requireService)
            ->relationship(
                'currentService',
                'name',
                fn($query, Get $get) => self::applyBranchFilter($query, $get)
            )
            ->searchable()
            ->preload()
            ->native(false)
            ->placeholder('— Choisir un service —')
            ->reactive()
            ->afterStateUpdated(fn(Set $set) => $set('sector_id', null))
            ->helperText(fn(Get $get) => $get('branch_type') === OrganizationalAssignment::BRANCH_MEDICAL
                ? 'Service médical du département choisi'
                : 'Service administratif, support ou technique de la sous-direction choisie');
    }

    private static function sector(): Select
    {
        return Select::make('sector_id')
            ->label('Unité / Secteur')
            ->relationship('sector', 'name', fn($query, Get $get) => $query
                ->where('is_active', true)
                ->when(
                    $get('current_service_id'),
                    fn($q) => $q->where('service_id', $get('current_service_id'))
                )
                ->orderBy('order')
                ->orderBy('name'))
            ->searchable()
            ->preload()
            ->native(false)
            ->placeholder('— Aucune unité —')
            ->disabled(fn(Get $get) => !$get('current_service_id'))
            ->helperText(fn(Get $get) => $get('current_service_id')
                ? 'Facultatif : unité ou secteur rattaché au service choisi.'
                : 'Choisissez d\'abord un service.');
    }

    private static function missingAffectationWarning(): Placeholder
    {
        return Placeholder::make('affectation_manquante')
            ->label('⚠️ Affectation non renseignée')
            ->content('Cet employé n\'est rattaché à aucun service. La cascade Direction → Département / Sous-direction → Service est facultative à l\'édition, mais l\'agent reste introuvable dans les états par service tant que ce champ est vide.')
            ->visible(fn(Get $get) => !$get('current_service_id'))
            ->columnSpanFull();
    }

    private static function applyBranchFilter($query, Get $get)
    {
        $query->where('is_active', true);

        if ($get('branch_type') === OrganizationalAssignment::BRANCH_MEDICAL) {
            return $query
                ->where('type', 'medical')
                ->when(
                    $get('department_id'),
                    fn($q) => $q->where('department_id', $get('department_id'))
                )
                ->orderBy('order')
                ->orderBy('name');
        }

        return $query
            ->whereIn('type', OrganizationalAssignment::ADMINISTRATIVE_SERVICE_TYPES)
            ->when(
                $get('sub_direction_id'),
                fn($q) => $q->where('sub_direction_id', $get('sub_direction_id'))
            )
            ->orderBy('order')
            ->orderBy('name');
    }

    private static function reset(Set $set, array $statePaths): void
    {
        foreach ($statePaths as $statePath) {
            $set($statePath, null);
        }
    }
}
