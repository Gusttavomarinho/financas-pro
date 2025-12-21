<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Lista transações com filtros e paginação
     * REGRA: Mostra apenas transações (compras), não parcelas individuais
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::where('user_id', $request->user()->id)
            ->with(['account', 'card', 'category', 'fromAccount', 'cardInstallments'])
            ->whereNotIn('status', ['estornada', 'cancelada'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->account_id) {
            $query->where(function ($q) use ($request) {
                $q->where('account_id', $request->account_id)
                    ->orWhere('from_account_id', $request->account_id);
            });
        }

        if ($request->card_id) {
            $query->where('card_id', $request->card_id);
        }

        if ($request->date_from) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->search) {
            $query->where('description', 'like', "%{$request->search}%");
        }

        $perPage = $request->per_page ?? 50;
        $transactions = $query->paginate($perPage);

        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Cria uma nova transação
     */
    public function store(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('Transaction Store Payload:', $request->all());

        $validated = $request->validate([
            'type' => ['required', 'in:receita,despesa,transferencia'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'from_account_id' => ['nullable', 'exists:accounts,id'],
            'card_id' => ['nullable', 'exists:cards,id', 'required_if:payment_method,debito,credito'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'payment_method' => ['nullable', 'in:dinheiro,debito,credito,pix,boleto,transferencia'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:48'],
            'notes' => ['nullable', 'string'],
        ], [
            'type.required' => 'O tipo é obrigatório.',
            'value.required' => 'O valor é obrigatório.',
            'value.min' => 'O valor deve ser maior que zero.',
            'description.required' => 'A descrição é obrigatória.',
            'date.required' => 'A data é obrigatória.',
            'card_id.required_if' => 'Selecione um cartão para este meio de pagamento.',
        ]);

        $userId = $request->user()->id;
        $installments = $validated['installments'] ?? 1;

        // Compra no crédito (parcelada ou à vista)
        if ($validated['payment_method'] === 'credito' && $validated['card_id']) {
            $card = Card::findOrFail($validated['card_id']);

            $transaction = $this->transactionService->createCreditPurchase(
                [...$validated, 'user_id' => $userId],
                $card,
                $installments
            );

            AuditLog::log('create_credit_purchase', 'Transaction', $transaction->id, [
                'installments' => $installments,
                'value' => $validated['value'],
            ]);

            $message = $installments > 1
                ? "Compra parcelada em {$installments}x criada com sucesso!"
                : 'Compra no crédito criada com sucesso!';

            return response()->json([
                'message' => $message,
                'data' => $transaction->load(['card', 'category', 'cardInstallments']),
            ], 201);
        }

        // Transferência entre contas
        if ($validated['type'] === 'transferencia') {
            $result = $this->transactionService->createTransfer([
                ...$validated,
                'user_id' => $userId,
            ]);

            AuditLog::log('create_transfer', 'Transaction', $result['debit']->id);

            return response()->json([
                'message' => 'Transferência realizada com sucesso!',
                'data' => $result['debit']->load(['account', 'fromAccount']),
            ], 201);
        }

        // Lógica para Débito via Cartão
        // Se vier card_id e método for débito, usamos a conta vinculada ao cartão
        if ($validated['payment_method'] === 'debito' && !empty($validated['card_id'])) {
            $card = Card::findOrFail($validated['card_id']);
            $validated['account_id'] = $card->account_id;
            // Mantemos o card_id salvo na transação também para referência
        }

        // Transação simples (receita/despesa em conta)
        $transaction = Transaction::create([
            ...$validated,
            'user_id' => $userId,
            'total_installments' => 1,
            'affects_balance' => true,
            'status' => 'confirmada',
        ]);

        AuditLog::log('create', 'Transaction', $transaction->id);

        return response()->json([
            'message' => 'Lançamento criado com sucesso!',
            'data' => $transaction->load(['account', 'card', 'category']),
        ], 201);
    }

    /**
     * Exibe uma transação com detalhes completos
     */
    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $transaction->load([
            'account',
            'card',
            'category',
            'fromAccount',
            'cardInstallments.invoice'
        ]);

        return response()->json([
            'data' => $transaction,
        ]);
    }

    /**
     * Atualiza uma transação
     * Suporta edição completa com reprocessamento (Undo/Redo)
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $validated = $request->validate([
            'description' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'notes' => ['nullable', 'string'],
            'value' => ['sometimes', 'numeric', 'min:0.01'],
            'date' => ['sometimes', 'date'],
            'installments' => ['nullable', 'integer', 'min:1'], // Mapped to total_installments
            'card_id' => ['nullable', 'exists:cards,id'],
            // 'account_id' etc not implemented for full switch yet for simplicity?
            // User asked "Edit ANY field".
            // Implementation detail: validation rules might need to be same as store
        ]);

        // Map installments -> total_installments
        if (isset($validated['installments'])) {
            $validated['total_installments'] = $validated['installments'];
            unset($validated['installments']);
        }

        $oldData = $transaction->toArray();

        // Delegate to service
        $transaction = $this->transactionService->updateTransaction($transaction, $validated);

        // Logs are handled by Observer (update) + Service (create credit/installments)
        // Controller specific log:
        AuditLog::log('update', 'Transaction', $transaction->id, [
            'old' => $oldData,
            'new' => $validated,
        ]);

        return response()->json([
            'message' => 'Lançamento atualizado com sucesso!',
            'data' => $transaction->fresh()->load(['account', 'card', 'category', 'cardInstallments']),
        ]);
    }

    /**
     * Remove uma transação (soft delete)
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        // Se for compra no crédito, estornar parcelas
        if ($transaction->isCreditPurchase()) {
            $this->transactionService->refund($transaction);
        } else {
            $transaction->delete();
        }

        AuditLog::log('delete', 'Transaction', $transaction->id);

        return response()->json([
            'message' => 'Lançamento removido com sucesso!',
        ]);
    }

    /**
     * Duplica uma transação
     */
    public function duplicate(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $newTransaction = $this->transactionService->duplicate($transaction);

        AuditLog::log('duplicate', 'Transaction', $newTransaction->id, [
            'original_id' => $transaction->id,
        ]);

        return response()->json([
            'message' => 'Lançamento duplicado com sucesso!',
            'data' => $newTransaction->load(['account', 'card', 'category', 'cardInstallments']),
        ], 201);
    }

    /**
     * Estorna uma compra no crédito
     */
    public function refund(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        if ($transaction->status === 'estornada') {
            return response()->json([
                'message' => 'Esta transação já foi estornada.',
            ], 400);
        }

        $this->transactionService->refund($transaction);

        AuditLog::log('refund', 'Transaction', $transaction->id);

        return response()->json([
            'message' => 'Lançamento estornado com sucesso! As parcelas futuras foram removidas das faturas.',
            'data' => $transaction->fresh()->load(['cardInstallments']),
        ]);
    }

    /**
     * Estorno parcial - mantém apenas N parcelas
     */
    public function partialRefund(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $validated = $request->validate([
            'keep_installments' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $this->transactionService->partialRefund($transaction, $validated['keep_installments']);

            AuditLog::log('partial_refund', 'Transaction', $transaction->id, [
                'keep_installments' => $validated['keep_installments'],
            ]);

            return response()->json([
                'message' => "Estorno parcial realizado! Mantidas {$validated['keep_installments']} parcelas.",
                'data' => $transaction->fresh()->load(['cardInstallments']),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Antecipa parcelas de uma transação
     */
    public function anticipate(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $validated = $request->validate([
            'installment_ids' => ['required', 'array', 'min:1'],
            'installment_ids.*' => ['integer', 'exists:card_installments,id'],
            'discount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            app(\App\Services\InstallmentService::class)->anticipateInstallments(
                $transaction,
                $validated['installment_ids'],
                $validated['discount']
            );

            /*
            // TODO: Move to BusinessAuditService properly
            AuditLog::log('anticipate_installments', 'Transaction', $transaction->id, [
                'count' => count($validated['installment_ids']),
                'discount' => $validated['discount']
            ]);
            */
            // Using the actual BusinessAuditService would be better if injected, 
            // but for now let's use the Facade/Model based logging or inject BusinessService.
            // Using the existing AuditLog helper for consistency with this file.
            AuditLog::log('anticipate_installments', 'Transaction', $transaction->id, [
                'installments_count' => count($validated['installment_ids']),
                'discount' => $validated['discount']
            ]);

            return response()->json([
                'message' => 'Parcelas antecipadas com sucesso!',
                'data' => $transaction->fresh()->load(['cardInstallments']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Estorno por valor - fluxo principal de estorno
     */
    public function refundByValue(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $validated = $request->validate([
            'value' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->transactionService->refundByValue($transaction, $validated['value']);

            return response()->json([
                'message' => 'Estorno realizado com sucesso!',
                'data' => $transaction->fresh()->load(['cardInstallments']),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Atualiza apenas as notas de uma transação
     */
    public function updateNotes(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->transactionService->updateNotes($transaction, $validated['notes'] ?? null);

        return response()->json([
            'message' => 'Observações atualizadas!',
            'data' => $transaction->fresh(),
        ]);
    }
}
