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
        Schema::create('general_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->default('Orçamento Geral');
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['mensal', 'anual'])->default('mensal');
            $table->json('category_ids')->nullable(); // null = todas as categorias
            $table->boolean('include_future_categories')->default(false);
            $table->unsignedTinyInteger('month')->nullable(); // null for annual
            $table->unsignedSmallInteger('year');
            $table->boolean('is_active')->default(true);
            $table->boolean('alert_80_sent')->default(false);
            $table->boolean('alert_100_sent')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'type', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_budgets');
    }
};
