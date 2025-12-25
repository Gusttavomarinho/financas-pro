<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['corrente', 'poupanca', 'carteira_digital', 'investimento', 'caixa', 'credito']);
            $table->string('name');
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('bank')->nullable();
            $table->string('agency')->nullable();
            $table->string('account_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('active'); // 'active' or 'archived'
            $table->boolean('exclude_from_totals')->default(false); // Excluir dos cálculos totais
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
