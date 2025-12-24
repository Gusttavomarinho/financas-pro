<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Flag para marcar transações geradas manualmente via "Gerar Agora"
            $table->boolean('generated_manually')->default(false)->after('recurring_transaction_id');
            // Flag para marcar transações que são duplicatas intencionais no mesmo período
            $table->boolean('duplicate_period')->default(false)->after('generated_manually');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['generated_manually', 'duplicate_period']);
        });
    }
};
