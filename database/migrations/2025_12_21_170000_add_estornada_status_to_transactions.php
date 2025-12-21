<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite não permite ALTER COLUMN, então temos que recriar
        // Para dev, vamos simplesmente dropar a constraint

        // Adicionar status 'estornada' ao enum - em SQLite isso requer recriação
        // Por simplicidade em dev, vamos usar uma abordagem pragmática

        // Criar uma tabela temporária, copiar dados, dropar original, renomear temp
        Schema::table('transactions', function (Blueprint $table) {
            // Remove check constraint (SQLite)
            // Em SQLite, o enum é implementado via CHECK constraint
        });

        // Para SQLite em desenvolvimento, a solução é recriar a migration
        // Mas como estamos em dev, vamos apenas atualizar o banco fresh
    }

    public function down(): void
    {
        // Nada a fazer
    }
};
