<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShowEmployeeAttributes extends Command
{
    protected $signature = 'employees:show-attributes';
    protected $description = 'Afficher tous les attributs de la table employees';

    public function handle()
    {
        $this->info('📋 ATTRIBUTS DE LA TABLE EMPLOYEES');
        $this->newLine();

        // Méthode 1 : Liste simple
        $this->info('🔹 LISTE DES COLONNES :');
        $columns = Schema::getColumnListing('employees');
        
        foreach ($columns as $index => $column) {
            $this->line(($index + 1) . '. ' . $column);
        }

        $this->newLine(2);

        // Méthode 2 : Détails complets (PostgreSQL)
        $this->info('🔹 DÉTAILS COMPLETS :');
        
        $details = DB::select("
            SELECT 
                column_name,
                data_type,
                character_maximum_length,
                is_nullable,
                column_default
            FROM information_schema.columns
            WHERE table_name = 'employees'
            ORDER BY ordinal_position
        ");

        $tableData = [];
        foreach ($details as $detail) {
            $tableData[] = [
                'Colonne' => $detail->column_name,
                'Type' => $detail->data_type . 
                    ($detail->character_maximum_length ? "({$detail->character_maximum_length})" : ''),
                'Nullable' => $detail->is_nullable === 'YES' ? '✅' : '❌',
                'Défaut' => $detail->column_default ?? 'NULL',
            ];
        }

        $this->table(
            ['Colonne', 'Type', 'Nullable', 'Défaut'],
            $tableData
        );

        $this->newLine();
        $this->info('✅ Total : ' . count($columns) . ' colonnes');

        return 0;
    }
}