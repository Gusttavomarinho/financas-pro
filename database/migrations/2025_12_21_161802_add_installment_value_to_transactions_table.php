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
            $table->decimal('installment_value', 12, 2)->nullable()->after('total_installments');
        });

        // Adicionar status de arquivamento na tabela accounts
        Schema::table('accounts', function (Blueprint $table) {
            $table->enum('status', ['active', 'archived'])->default('active')->after('is_active');
        });

        // Adicionar account_id na tabela cards (vinculação com conta)
        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('installment_value');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
