<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        echo "🔧 Réorganisation de la structure organisationnelle...\n";

        // ÉTAPE 1 : Identifier les directions qui sont dans departments
        $directionCodes = ['DG', 'DAF', 'DRH', 'DSI', 'DAM', 'DTI', 'DL', 'DC', 'DQRG'];

        $misplacedDirections = DB::table('departments')
            ->whereIn('code', $directionCodes)
            ->get();

        echo "📊 Trouvé {$misplacedDirections->count()} directions mal placées dans 'departments'\n";

        // ÉTAPE 2 : Les migrer vers la table directions
        foreach ($misplacedDirections as $dept) {
            // Vérifier si elle n'existe pas déjà dans directions
            $exists = DB::table('directions')
                ->where('code', $dept->code)
                ->exists();

            if (!$exists) {
                DB::table('directions')->insert([
                    'name' => $dept->name,
                    'code' => $dept->code,
                    'acronym' => $dept->acronym ?? $dept->code,
                    'type' => $dept->type ?? 'administrative',
                    'description' => $dept->description,
                    'order' => $dept->order ?? 0,
                    'is_active' => $dept->is_active ?? true,
                    'phone' => $dept->phone,
                    'email' => $dept->email,
                    'created_at' => $dept->created_at,
                    'updated_at' => now(),
                ]);

                echo "  ✓ Migré : {$dept->name} ({$dept->code})\n";
            } else {
                echo "  ⚠️ Existe déjà : {$dept->name} ({$dept->code})\n";
            }
        }

        // ÉTAPE 3 : Supprimer ces directions de la table departments
        DB::table('departments')
            ->whereIn('code', $directionCodes)
            ->delete();

        echo "✅ Directions nettoyées de la table departments\n";

        // ÉTAPE 4 : Créer la DAM si elle n'existe pas
        $dam = DB::table('directions')->where('code', 'DAM')->first();

        if (!$dam) {
            $damId = DB::table('directions')->insertGetId([
                'name' => 'Direction des Affaires Médicales',
                'code' => 'DAM',
                'acronym' => 'DAM',
                'type' => 'administrative',
                'description' => 'Coordination et supervision des activités médicales',
                'order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "✅ DAM créée (ID: {$damId})\n";
        } else {
            $damId = $dam->id;
            echo "✅ DAM trouvée (ID: {$damId})\n";
        }

        // ÉTAPE 5 : Les vrais départements médicaux doivent rester dans departments
        // et être rattachés à la DAM
        $medicalDeptCodes = ['DMI', 'DCHIR', 'DPED', 'DGYN', 'DURG', 'DIMAG', 'DBIO'];

        $updated = DB::table('departments')
            ->whereIn('code', $medicalDeptCodes)
            ->update([
                'direction_id' => $damId,
                'hierarchical_level' => 'sub_direction',
                'updated_at' => now(),
            ]);

        echo "✅ {$updated} département(s) médical(aux) rattaché(s) à la DAM\n";

        // ÉTAPE 6 : Statistiques finales
        $directionsCount = DB::table('directions')->count();
        $departmentsCount = DB::table('departments')->count();
        $deptsWithDirection = DB::table('departments')->whereNotNull('direction_id')->count();

        echo "\n📊 RÉSULTAT FINAL:\n";
        echo "  - Directions : {$directionsCount}\n";
        echo "  - Départements : {$departmentsCount}\n";
        echo "  - Départements rattachés : {$deptsWithDirection}\n";
    }

    public function down(): void
    {
        // Difficile à annuler proprement
        echo "⚠️ Migration non réversible automatiquement\n";
    }
};
