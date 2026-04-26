<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

        // ÉTAPE 1.5 : GÉRER LES SERVICES ORPHELINS AVANT DE SUPPRIMER
        $directionIds = $misplacedDirections->pluck('id')->toArray();

        if (count($directionIds) > 0) {
            // Trouver un département de destination pour les services orphelins
            $targetDepartment = DB::table('departments')
                ->whereNotIn('code', $directionCodes)
                ->whereNotNull('direction_id')
                ->first();

            if (!$targetDepartment) {
                // Si aucun département n'existe, on va en créer un temporaire
                echo "⚠️ Aucun département valide trouvé, création d'un département temporaire...\n";

                // D'abord créer/vérifier la DAM
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
                } else {
                    $damId = $dam->id;
                }

                // Créer un département temporaire
                $targetDeptId = DB::table('departments')->insertGetId([
                    'name' => 'Services Généraux',
                    'code' => 'SG',
                    'type' => 'support',
                    'direction_id' => $damId,
                    'hierarchical_level' => 'sub_direction',
                    'is_active' => true,
                    'order' => 999,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $targetDeptId = $targetDepartment->id;
            }

            // Compter et rattacher les services orphelins
            $orphanServices = DB::table('services')
                ->whereIn('department_id', $directionIds)
                ->count();

            if ($orphanServices > 0) {
                echo "📌 {$orphanServices} service(s) à rattacher...\n";

                DB::table('services')
                    ->whereIn('department_id', $directionIds)
                    ->update([
                        'department_id' => $targetDeptId,
                        'sub_direction_id' => null,
                        'updated_at' => now(),
                    ]);

                echo "✅ Services rattachés au département ID: {$targetDeptId}\n";
            }
        }

        // ÉTAPE 2 : Migrer les directions vers la table directions
        foreach ($misplacedDirections as $dept) {
            $exists = DB::table('directions')
                ->where('code', $dept->code)
                ->exists();

            if (!$exists) {
                $type = $dept->type ?? 'administrative';
                if (!in_array($type, ['administrative', 'technique', 'support'])) {
                    $type = 'administrative';
                }

                DB::table('directions')->insert([
                    'name' => $dept->name,
                    'code' => $dept->code,
                    'acronym' => $dept->acronym ?? $dept->code,
                    'type' => $type,
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
        $deleted = DB::table('departments')
            ->whereIn('code', $directionCodes)
            ->delete();

        echo "✅ {$deleted} direction(s) nettoyée(s) de la table departments\n";

        // ÉTAPE 4 : Créer/vérifier la DAM
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

        // ÉTAPE 5 : Rattacher les départements médicaux à la DAM
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
        $servicesCount = DB::table('services')->count();
        $servicesWithParent = DB::table('services')
            ->where(function ($q) {
                $q->whereNotNull('department_id')
                    ->orWhereNotNull('sub_direction_id');
            })
            ->count();

        echo "\n📊 RÉSULTAT FINAL:\n";
        echo "  - Directions : {$directionsCount}\n";
        echo "  - Départements : {$departmentsCount}\n";
        echo "  - Départements rattachés : {$deptsWithDirection}\n";
        echo "  - Services : {$servicesCount}\n";
        echo "  - Services avec parent : {$servicesWithParent}\n";
    }

    public function down(): void
    {
        echo "⚠️ Migration non réversible automatiquement\n";
    }
};
