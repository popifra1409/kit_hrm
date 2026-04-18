<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;

class MigrateServiceResponsables extends Command
{
    protected $signature = 'services:migrate-responsables';
    protected $description = 'Migrer les anciens champs responsables vers service_head_id';

    public function handle()
    {
        $this->info('Migration des responsables de services...');

        $services = Service::whereNull('service_head_id')
            ->where(function ($q) {
                $q->whereNotNull('head_of_service_id')
                    ->orWhereNotNull('major_id')
                    ->orWhereNotNull('service_chief_id')
                    ->orWhereNotNull('deputy_director_id');
            })
            ->get();

        $count = 0;
        foreach ($services as $service) {
            $service->migrateResponsable();
            $count++;
            $this->line("✓ {$service->name} : migré vers {$service->serviceHead->full_name}");
        }

        $this->info("✅ {$count} services migrés avec succès !");

        return 0;
    }
}
