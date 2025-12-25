<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    /**
     * Lista objetivos do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $query = Goal::where('user_id', $request->user()->id)
            // SQLite-compatible ordering: em_andamento first, then concluido, then cancelado
            ->orderByRaw("CASE status 
                WHEN 'em_andamento' THEN 1 
                WHEN 'concluido' THEN 2 
                WHEN 'cancelado' THEN 3 
                ELSE 4 END")
            ->orderBy('created_at', 'desc');

        // Filtro por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    /**
     * Criar objetivo
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:10'],
            'color' => ['nullable', 'string', 'max:20'],
            'target_value' => ['required', 'numeric', 'min:0.01'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'target_date' => ['nullable', 'date'],
        ], [
            'name.required' => 'O nome do objetivo é obrigatório.',
            'target_value.required' => 'O valor da meta é obrigatório.',
            'target_value.min' => 'O valor deve ser maior que zero.',
        ]);

        $goal = Goal::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        AuditLog::log('create', 'Goal', $goal->id, [
            'name' => $goal->name,
            'target_value' => $goal->target_value,
        ]);

        return response()->json([
            'message' => 'Objetivo criado com sucesso!',
            'data' => $goal->fresh(),
        ], 201);
    }

    /**
     * Exibir objetivo
     */
    public function show(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        return response()->json([
            'data' => $goal,
        ]);
    }

    /**
     * Atualizar objetivo
     */
    public function update(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:10'],
            'color' => ['nullable', 'string', 'max:20'],
            'target_value' => ['sometimes', 'numeric', 'min:0.01'],
            'target_date' => ['nullable', 'date'],
        ]);

        $goal->update($validated);

        // Recalculate status if target_value was changed
        // If goal was 'concluido' but current_value < new target_value, revert to 'em_andamento'
        if (isset($validated['target_value'])) {
            $goal->refresh();
            if ($goal->status === 'concluido' && $goal->current_value < $goal->target_value) {
                $goal->update(['status' => 'em_andamento']);
            }
        }

        AuditLog::log('update', 'Goal', $goal->id, [
            'changes' => array_keys($validated),
        ]);

        return response()->json([
            'message' => 'Objetivo atualizado!',
            'data' => $goal->fresh(),
        ]);
    }

    /**
     * Excluir objetivo
     */
    public function destroy(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        AuditLog::log('delete', 'Goal', $goal->id);

        $goal->delete();

        return response()->json([
            'message' => 'Objetivo removido!',
        ]);
    }

    /**
     * Depositar valor no objetivo
     * 
     * REGRA: NÃO cria transação financeira, apenas controle interno
     */
    public function deposit(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        if ($goal->status !== 'em_andamento') {
            return response()->json([
                'message' => 'Só é possível depositar em objetivos em andamento.',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ], [
            'amount.required' => 'O valor é obrigatório.',
            'amount.min' => 'O valor deve ser maior que zero.',
        ]);

        $goal->deposit($validated['amount']);

        AuditLog::log('deposit', 'Goal', $goal->id, [
            'amount' => $validated['amount'],
            'new_value' => $goal->current_value,
        ]);

        $message = $goal->is_completed
            ? '🎉 Parabéns! Objetivo concluído!'
            : 'Depósito realizado!';

        return response()->json([
            'message' => $message,
            'data' => $goal->fresh(),
        ]);
    }

    /**
     * Sacar valor do objetivo
     * 
     * REGRA: NÃO cria transação financeira, apenas controle interno
     */
    public function withdraw(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        if ($goal->status === 'cancelado') {
            return response()->json([
                'message' => 'Não é possível sacar de um objetivo cancelado.',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($validated['amount'] > $goal->current_value) {
            return response()->json([
                'message' => 'Valor do saque maior que o saldo atual do objetivo.',
            ], 422);
        }

        $goal->withdraw($validated['amount']);

        AuditLog::log('withdraw', 'Goal', $goal->id, [
            'amount' => $validated['amount'],
            'new_value' => $goal->current_value,
        ]);

        return response()->json([
            'message' => 'Saque realizado!',
            'data' => $goal->fresh(),
        ]);
    }

    /**
     * Cancelar objetivo
     */
    public function cancel(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        if ($goal->status === 'cancelado') {
            return response()->json([
                'message' => 'Objetivo já está cancelado.',
            ], 422);
        }

        $goal->update(['status' => 'cancelado']);

        AuditLog::log('cancel', 'Goal', $goal->id);

        return response()->json([
            'message' => 'Objetivo cancelado.',
            'data' => $goal->fresh(),
        ]);
    }

    /**
     * Reativar objetivo cancelado
     */
    public function reactivate(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        if ($goal->status !== 'cancelado') {
            return response()->json([
                'message' => 'Apenas objetivos cancelados podem ser reativados.',
            ], 422);
        }

        $goal->update(['status' => 'em_andamento']);

        AuditLog::log('reactivate', 'Goal', $goal->id);

        return response()->json([
            'message' => 'Objetivo reativado!',
            'data' => $goal->fresh(),
        ]);
    }
}
