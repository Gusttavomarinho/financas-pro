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
        Schema::create('card_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('card_invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('installment_number'); // 1, 2, 3...
            $table->integer('total_installments'); // Total de parcelas
            $table->decimal('value', 12, 2); // Valor da parcela
            $table->date('due_date'); // Data prevista para cobrança na fatura
            $table->enum('status', ['pendente', 'em_fatura', 'paga', 'estornada', 'antecipada'])->default('pendente');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_installments');
    }
};
