<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecurringTransaction;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\RecurringTransactionService;

class RecurringTransactionController extends Controller
{
    /**
     * Lista recorrências do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $query = RecurringTransaction::where('user_id', $request->user()->id)
            ->with(['account', 'card', 'category'])
            ->orderBy('next_occurrence', 'asc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $recurrings = $query->paginate($request->per_page ?? 20);

        return response()->json($recurrings);
    }

    /**
     * Cria nova regra de recorrência
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:receita,despesa'], // Transferência bloqueada no MVP
            'frequency' => ['required', 'in:semanal,mensal,anual,personalizada'],
            'frequency_value' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'card_id' => ['nullable', 'exists:cards,id'],
            'payment_method' => ['required', 'in:dinheiro,debito,credito,pix,boleto'],
            'notes' => ['nullable', 'string'],
        ], [
            'type.in' => 'Transferências recorrentes indisponíveis no momento.',
        ]);

        // Validação cruzada
        if ($validated['payment_method'] === 'credito' && empty($validated['card_id'])) {
            return response()->json(['message' => 'Cartão obrigatório para crédito.'], 422);
        }

        // Setup inicial
        $data = $validated;
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'ativa';

        // Next occurrence começa na start_date
        // Se start_date já passou, o job vai pegar.
        $data['next_occurrence'] = $data['start_date'];

        $recurring = RecurringTransaction::create($data);

        AuditLog::log('create', 'RecurringTransaction', $recurring->id);

        return response()->json([
            'message' => 'Assinatura criada com sucesso!',
            'data' => $recurring->load(['category', 'account', 'card']),
        ], 201);
    }

    /**
     * Detalhes
     */
    public function show(Request $request, RecurringTransaction $recurring): JsonResponse
    {
        if ($recurring->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $recurring->load([
            'transactions' => function ($q) {
                $q->select('id', 'recurring_transaction_id', 'date', 'value', 'type', 'description', 'generated_manually', 'duplicate_period', 'status')
                    ->orderBy('date', 'desc')
                    ->limit(50);
            },
            'category',
            'account',
            'card'
        ]);

        return response()->json([
            'data' => $recurring,
            'button_state' => $recurring->getGenerateButtonState(),
            'blocked_tooltip' => $recurring->getBlockedButtonTooltip(),
            'generation_info' => $recurring->getGenerationInfo(),
        ]);
    }

    /**
     * Atualizar
     */
    public function update(Request $request, RecurringTransaction $recurring): JsonResponse
    {
        if ($recurring->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $validated = $request->validate([
            'description' => ['sometimes', 'string', 'max:255'],
            'value' => ['sometimes', 'numeric', 'min:0.01'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'status' => ['sometimes', 'in:ativa,pausada,encerrada'],
            // Não permitimos mudar frequency/type facilmente pois quebra a lógica de next_occurrence
            // Se precisar mudar frequência, melhor recriar ou exigir re-cálculo de next_occurrence
            'notes' => ['nullable', 'string'],
        ]);

        $recurring->update($validated);

        // Se status mudou para ativa e next_occurrence estava no passado, talvez devêssemos ajustar?
        // Deixar para o job ou manual.

        AuditLog::log('update', 'RecurringTransaction', $recurring->id, [
            'changes' => $recurring->getChanges()
        ]);

        return response()->json([
            'message' => 'Assinatura atualizada!',
            'data' => $recurring->fresh()->load(['category', 'account', 'card']),
        ]);
    }

    public function destroy(Request $request, RecurringTransaction $recurring): JsonResponse
    {
        if ($recurring->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $recurring->delete(); // Soft delete per model

        AuditLog::log('delete', 'RecurringTransaction', $recurring->id);

        return response()->json([
            'message' => 'Assinatura removida (arquivada)!',
        ]);
    }

    public function pause(Request $request, RecurringTransaction $recurring): JsonResponse
    {
        if ($recurring->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $recurring->update(['status' => 'pausada']);
        AuditLog::log('pause', 'RecurringTransaction', $recurring->id);

        return response()->json([
            'message' => 'Recorrência pausada.',
            'data' => $recurring->load(['account', 'card', 'category'])
        ]);
    }

    public function resume(Request $request, RecurringTransaction $recurring): JsonResponse
    {
        if ($recurring->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $recurring->update(['status' => 'ativa']);
        AuditLog::log('resume', 'RecurringTransaction', $recurring->id);

        return response()->json([
            'message' => 'Recorrência retomada.',
            'data' => $recurring->load(['account', 'card', 'category'])
        ]);
    }

    public function end(Request $request, RecurringTransaction $recurring): JsonResponse
    {
        if ($recurring->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $recurring->update([
            'status' => 'encerrada',
            'end_date' => now()
        ]);
        AuditLog::log('end', 'RecurringTransaction', $recurring->id);

        return response()->json([
            'message' => 'Recorrência encerrada.',
            'data' => $recurring->load(['account', 'card', 'category'])
        ]);
    }

    public function generate(Request $request, RecurringTransaction $recurring, RecurringTransactionService $service): JsonResponse
    {
        if ($recurring->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        // Verificar se é uma duplicata intencional (requer confirmação do frontend)
        $isDuplicate = $request->boolean('confirm_duplicate', false);

        // Se já existe transação no período e não confirmou duplicata, retornar aviso
        $buttonState = $recurring->getGenerateButtonState();
        if ($buttonState === 'alert' && !$isDuplicate) {
            return response()->json([
                'message' => 'Já existe um lançamento neste período.',
                'requires_confirmation' => true,
                'button_state' => 'alert',
            ], 409); // Conflict
        }

        try {
            // forceManual=true: botão "Gerar Agora" usa validação permissiva
            $transaction = $service->generateTransaction($recurring, forceManual: true, isDuplicate: $isDuplicate);

            return response()->json([
                'message' => $isDuplicate
                    ? 'Lançamento adicional gerado com sucesso!'
                    : 'Transação gerada com sucesso!',
                'data' => [
                    'recurring' => $recurring->fresh()->load(['category', 'account', 'card']),
                    'transaction_id' => $transaction->id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao gerar: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Retorna sugestões de assinaturas detectadas automaticamente
     */
    public function suggestions(Request $request, \App\Services\SubscriptionDetectionService $detector): JsonResponse
    {
        $suggestions = $detector->detectPotentialSubscriptions($request->user()->id);

        return response()->json([
            'data' => $suggestions,
            'count' => $suggestions->count()
        ]);
    }

    /**
     * Cria recorrência a partir de uma sugestão
     */
    public function createFromSuggestion(Request $request, \App\Services\SubscriptionDetectionService $detector): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string'],
            'amount_avg' => ['required', 'numeric'],
            'amount_last' => ['nullable', 'numeric'],
            'frequency' => ['required', 'string'],
            'frequency_value' => ['required', 'integer'],
            'last_occurrence' => ['required', 'date'],
            'category_id' => ['nullable', 'integer'],
            'account_id' => ['nullable', 'integer'],
            'card_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'string'],
        ]);

        $recurring = $detector->createRecurringFromSuggestion($request->user()->id, $validated);

        AuditLog::log('create_from_suggestion', 'RecurringTransaction', $recurring->id);

        return response()->json([
            'message' => 'Recorrência criada a partir da sugestão!',
            'data' => $recurring->load(['category', 'account', 'card'])
        ], 201);
    }
}

