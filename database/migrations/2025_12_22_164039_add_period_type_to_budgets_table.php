<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Adiciona coluna period_type para suportar orçamentos ANUAIS
     */
    public function up(): void
    {
        // Adicionar coluna period_type
        Schema::table('budgets', function (Blueprint $table) {
            // 'mensal' = YYYY-MM, 'anual' = YYYY
            $table->string('period_type', 10)->default('mensal');
        });

        // SQLite não suporta drop index dentro de Schema::table
        // Usamos raw SQL que funciona em SQLite
        try {
            DB::statement('DROP INDEX IF EXISTS budgets_user_id_category_id_reference_month_unique');
        } catch (\Exception $e) {
            // Index may not exist
        }

        // Criar novo índice único incluindo period_type
        Schema::table('budgets', function (Blueprint $table) {
            $table->unique(['user_id', 'category_id', 'reference_month', 'period_type'], 'budgets_unique_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS budgets_unique_period');
        } catch (\Exception $e) {
            // Index may not exist
        }

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn('period_type');
        });

        // Recreate original index
        Schema::table('budgets', function (Blueprint $table) {
            $table->unique(['user_id', 'category_id', 'reference_month']);
        });
    }
};
