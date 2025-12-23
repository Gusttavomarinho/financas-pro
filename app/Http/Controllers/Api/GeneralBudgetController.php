<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralBudget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeneralBudgetController extends Controller
{
    /**
     * List general budgets for user.
     */
    public function index(Request $request): JsonResponse
    {
        $query = GeneralBudget::where('user_id', Auth::id());

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        // Filter current only
        if ($request->boolean('current')) {
            $now = now();
            $query->where(function ($q) use ($now) {
                $q->where(function ($subQ) use ($now) {
                    $subQ->where('type', 'mensal')
                        ->where('month', $now->month)
                        ->where('year', $now->year);
                })->orWhere(function ($subQ) use ($now) {
                    $subQ->where('type', 'anual')
                        ->where('year', $now->year);
                });
            });
        }

        $budgets = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'data' => $budgets,
        ]);
    }

    /**
     * Create a general budget.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:mensal,anual'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'include_future_categories' => ['sometimes', 'boolean'],
            'month' => ['required_if:type,mensal', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
        ]);

        // Check for existing budget
        $existing = GeneralBudget::where('user_id', Auth::id())
            ->where('type', $validated['type'])
            ->where('month', $validated['month'] ?? null)
            ->where('year', $validated['year'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Já existe um orçamento geral para este período.',
            ], 422);
        }

        $budget = GeneralBudget::create([
            ...$validated,
            'user_id' => Auth::id(),
            'name' => $validated['name'] ?? 'Orçamento Geral',
            'include_future_categories' => $validated['include_future_categories'] ?? false,
        ]);

        return response()->json([
            'message' => 'Orçamento geral criado com sucesso!',
            'data' => $budget,
        ], 201);
    }

    /**
     * Show a general budget.
     */
    public function show(GeneralBudget $generalBudget): JsonResponse
    {
        if ($generalBudget->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        return response()->json([
            'data' => $generalBudget,
        ]);
    }

    /**
     * Update a general budget.
     */
    public function update(Request $request, GeneralBudget $generalBudget): JsonResponse
    {
        if ($generalBudget->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'include_future_categories' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Reset alert flags if amount changed
        if (isset($validated['amount']) && $validated['amount'] != $generalBudget->amount) {
            $validated['alert_80_sent'] = false;
            $validated['alert_100_sent'] = false;
        }

        $generalBudget->update($validated);

        return response()->json([
            'message' => 'Orçamento geral atualizado!',
            'data' => $generalBudget->fresh(),
        ]);
    }

    /**
     * Delete a general budget.
     */
    public function destroy(GeneralBudget $generalBudget): JsonResponse
    {
        if ($generalBudget->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $generalBudget->delete();

        return response()->json([
            'message' => 'Orçamento geral removido.',
        ]);
    }

    /**
     * Get current period summary (for Dashboard widget).
     */
    public function current(): JsonResponse
    {
        $now = now();

        // Get current month budget + annual budget
        $monthlyBudget = GeneralBudget::where('user_id', Auth::id())
            ->where('type', 'mensal')
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->where('is_active', true)
            ->first();

        $annualBudget = GeneralBudget::where('user_id', Auth::id())
            ->where('type', 'anual')
            ->where('year', $now->year)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'data' => [
                'monthly' => $monthlyBudget,
                'annual' => $annualBudget,
            ],
        ]);
    }
}
