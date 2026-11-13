<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Catégories en vigueur (App\Support\OrganizationalAssignment::PERSONNEL_TYPES)
     * et valeurs héritées, listées ici plutôt que lues dans le code applicatif :
     * une migration doit rester ce qu'elle était le jour où elle a tourné.
     */
    private const TYPES = [
        'medical', 'paramedical', 'administratif', 'medico_technique', 'logistique', 'cadres', 'en_formation',
    ];

    private const LEGACY = ['soignant', 'non_soignant', 'autres'];

    /**
     * Le type de personnel devient une catégorie RH déclarée au recensement :
     * « Soignant / Non soignant » disparaît au profit de sept catégories.
     *
     * Reclassement automatique, à la valeur la plus proche :
     *   soignant      → medical
     *   non_soignant  → administratif
     *   paramedical   → inchangé
     *   autres        → inchangé (à trier par les RH, valeur toujours acceptée mais plus proposée)
     *
     * Conséquence réelle sur la paie : QuotpartCalculationService compte les points
     * d'activité médicale pour medical, paramedical, medico_technique et cadres.
     * Le personnel paramédical et médico-technique, qui touchait 0 point avant, peut
     * donc voir son quotient part augmenter dès la première période recalculée.
     * Les médecins ne bougent pas : 'soignant' devenant 'medical', et l'ancienne
     * valeur restant admise dans la liste de calcul.
     */
    public function up(): void
    {
        // La contrainte CHECK et la colonne ne sont en PostgreSQL que dans ce dialecte
        // (2026_04_18_125551) ; sur une autre base il n'y a ni contrainte ni valeurs à convertir.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // La contrainte tombe D'ABORD : PostgreSQL évalue le CHECK à chaque ligne écrite, donc
        // écrit 'medical' sous l'ancienne contrainte échoue (SQLSTATE 23514) et fait rollback
        // de la migration entière. Fenêtre sans contrainte = le temps de la transaction.
        DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_personnel_type_check');

        DB::table('employees')->where('personnel_type', 'soignant')->update(['personnel_type' => 'medical']);
        DB::table('employees')->where('personnel_type', 'non_soignant')->update(['personnel_type' => 'administratif']);

        // Le défaut de colonne hérite du reclassement ci-dessus ('non_soignant' → 'administratif'),
        // et se pose avant le nouveau CHECK : ADD CONSTRAINT ne valide que les lignes existantes,
        // jamais la valeur par défaut. Un défaut hors liste laisserait un INSERT muet en échec.
        DB::statement($this->defaultStatement('administratif'));

        DB::statement($this->checkStatement(array_merge(self::TYPES, self::LEGACY)));
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_personnel_type_check');

        // 'paramedical' était admis avant la bascule et up() ne le touchait pas : down() ne doit
        // pas le franciser en 'soignant', sinon le retour en arrière perd une catégorie vivante.
        DB::table('employees')->where('personnel_type', 'medical')->update(['personnel_type' => 'soignant']);

        // Les cinq catégories sans équivalent d'avant retombent en non_soignant :
        // le retour en arrière perd nécessairement le tri fait entre-temps.
        DB::table('employees')
            ->whereIn('personnel_type', ['administratif', 'medico_technique', 'logistique', 'cadres', 'en_formation'])
            ->update(['personnel_type' => 'non_soignant']);

        DB::statement($this->defaultStatement('non_soignant'));

        DB::statement($this->checkStatement(['soignant', 'non_soignant', 'paramedical', 'autres']));
    }

    private function checkStatement(array $values): string
    {
        return 'ALTER TABLE employees ADD CONSTRAINT employees_personnel_type_check CHECK (personnel_type IN (\''
            .implode('\', \'', $values).'\'))';
    }

    private function defaultStatement(string $value): string
    {
        return "ALTER TABLE employees ALTER COLUMN personnel_type SET DEFAULT '{$value}'";
    }
};
