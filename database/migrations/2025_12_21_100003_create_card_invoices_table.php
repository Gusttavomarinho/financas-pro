<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('card_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->string('reference_month', 7); // YYYY-MM ⭐ Facilita filtros e UI
            $table->date('period_start');
            $table->date('period_end');
            $table->date('closing_date');
            $table->date('due_date');
            $table->decimal('total_value', 15, 2)->default(0);
            $table->decimal('paid_value', 15, 2)->default(0);
            $table->enum('status', ['aberta', 'fechada', 'parcialmente_paga', 'paga', 'vencida'])->default('aberta');
            $table->timestamps();

            // Uma fatura por mês por cartão
            $table->unique(['card_id', 'reference_month']);
            $table->index(['card_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_invoices');
    }
};
