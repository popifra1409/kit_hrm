<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\LeaveApprovalStep;

class LeaveApprovalStepSeeder extends Seeder
{
    public function run(): void
    {
        // Rôles fixes nécessaires pour les étapes 3 à 6 (créés seulement s'ils n'existent pas déjà)
        foreach (['chef_service_nursing', 'dat', 'daaf', 'dmr_dmra'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $steps = [
            [
                'code' => 'chef_immediat',
                'name' => 'Chef Immédiat (Major / Chef de Service)',
                'order' => 1,
                'resolver_type' => 'service_head', // Employee->currentService->serviceHead
                'resolver_role' => null,
            ],
            [
                'code' => 'dept_head',
                'name' => 'Chef de Département / Sous-Direction',
                'order' => 2,
                'resolver_type' => 'department_head',
                'resolver_role' => null,
            ],
            [
                'code' => 'nursing_chief',
                'name' => 'Chef de Service Nursing',
                'order' => 3,
                'resolver_type' => 'role',
                'resolver_role' => 'chef_service_nursing',
            ],
            [
                'code' => 'dat',
                'name' => 'DAT',
                'order' => 4,
                'resolver_type' => 'role',
                'resolver_role' => 'dat',
            ],
            [
                'code' => 'daaf',
                'name' => 'DAAF',
                'order' => 5,
                'resolver_type' => 'role',
                'resolver_role' => 'daaf',
            ],
            [
                'code' => 'dmr_dmra',
                'name' => 'DMR/DMRA',
                'order' => 6,
                'resolver_type' => 'role',
                'resolver_role' => 'dmr_dmra',
            ],
        ];

        foreach ($steps as $step) {
            LeaveApprovalStep::firstOrCreate(
                ['code' => $step['code']],
                $step + ['is_active' => true]
            );
        }

        echo "✅ Rôles de validation et circuit d'approbation (6 étapes) créés.\n";
        echo "⚠️  Pensez à assigner les rôles chef_service_nursing/dat/daaf/dmr_dmra aux bons utilisateurs (UserResource).\n";
    }
}
