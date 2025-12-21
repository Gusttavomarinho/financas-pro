<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Categorias de Despesa (sistema)
        $expenseCategories = [
            ['name' => 'Alimentação', 'icon' => '🍔', 'color' => '#ef4444'],
            ['name' => 'Moradia', 'icon' => '🏠', 'color' => '#f97316'],
            ['name' => 'Transporte', 'icon' => '🚗', 'color' => '#f59e0b'],
            ['name' => 'Saúde', 'icon' => '🏥', 'color' => '#22c55e'],
            ['name' => 'Educação', 'icon' => '📚', 'color' => '#3b82f6'],
            ['name' => 'Lazer', 'icon' => '🎮', 'color' => '#8b5cf6'],
            ['name' => 'Vestuário', 'icon' => '👕', 'color' => '#ec4899'],
            ['name' => 'Assinaturas', 'icon' => '📺', 'color' => '#06b6d4'],
            ['name' => 'Impostos', 'icon' => '📋', 'color' => '#6b7280'],
            ['name' => 'Seguros', 'icon' => '🛡️', 'color' => '#14b8a6'],
            ['name' => 'Pets', 'icon' => '🐾', 'color' => '#eab308'],
            ['name' => 'Presentes', 'icon' => '🎁', 'color' => '#d946ef'],
            ['name' => 'Viagens', 'icon' => '✈️', 'color' => '#0ea5e9'],
            ['name' => 'Outros (Despesa)', 'icon' => '📦', 'color' => '#94a3b8'],
        ];

        foreach ($expenseCategories as $category) {
            Category::create([
                'name' => $category['name'],
                'type' => 'despesa',
                'icon' => $category['icon'],
                'color' => $category['color'],
                'is_system' => true,
                'is_active' => true,
                'user_id' => null,
            ]);
        }

        // Categorias de Receita (sistema)
        $incomeCategories = [
            ['name' => 'Salário', 'icon' => '💰', 'color' => '#22c55e'],
            ['name' => 'Freelance', 'icon' => '💼', 'color' => '#10b981'],
            ['name' => 'Investimentos', 'icon' => '📈', 'color' => '#059669'],
            ['name' => 'Aluguel', 'icon' => '🏢', 'color' => '#84cc16'],
            ['name' => 'Dividendos', 'icon' => '📊', 'color' => '#14b8a6'],
            ['name' => 'Restituição', 'icon' => '💵', 'color' => '#06b6d4'],
            ['name' => 'Vendas', 'icon' => '🛒', 'color' => '#0ea5e9'],
            ['name' => 'Outros (Receita)', 'icon' => '✨', 'color' => '#6ee7b7'],
        ];

        foreach ($incomeCategories as $category) {
            Category::create([
                'name' => $category['name'],
                'type' => 'receita',
                'icon' => $category['icon'],
                'color' => $category['color'],
                'is_system' => true,
                'is_active' => true,
                'user_id' => null,
            ]);
        }
    }
}
