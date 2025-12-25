<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    protected AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Lista todas as contas ativas do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $showArchived = $request->boolean('show_archived', false);

        $query = Account::where('user_id', $request->user()->id)
            ->orderBy('name');

        if (!$showArchived) {
            $query->where('status', 'active');
        }

        $accounts = $query->get();

        return response()->json([
            'data' => $accounts,
        ]);
    }

    /**
     * Cria uma nova conta com transação de saldo inicial automática
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:corrente,poupanca,carteira_digital,investimento,caixa,credito'],
            'initial_balance' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'bank' => ['nullable', 'string', 'max:255'],
            'agency' => ['nullable', 'string', 'max:20'],
            'account_number' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'exclude_from_totals' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'O nome da conta é obrigatório.',
            'type.required' => 'O tipo de conta é obrigatório.',
        ]);

        $account = $this->accountService->createAccount($validated, $request->user()->id);

        $message = 'Conta criada com sucesso!';
        if (($validated['initial_balance'] ?? 0) != 0) {
            $message .= ' Transação de saldo inicial foi criada automaticamente.';
        }

        return response()->json([
            'message' => $message,
            'data' => $account->fresh(),
        ], 201);
    }

    /**
     * Exibe uma conta específica
     */
    public function show(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        return response()->json([
            'data' => $account,
        ]);
    }

    /**
     * Atualiza uma conta
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        if ($account->isArchived()) {
            return response()->json([
                'message' => 'Conta arquivada não pode ser editada.',
            ], 400);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:corrente,poupanca,carteira_digital,investimento,caixa,credito'],
            'initial_balance' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'bank' => ['nullable', 'string', 'max:255'],
            'agency' => ['nullable', 'string', 'max:20'],
            'account_number' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'exclude_from_totals' => ['nullable', 'boolean'],
        ]);

        $oldData = $account->toArray();
        $account->update($validated);

        // Note: AuditObserver automatically logs 'update' event

        return response()->json([
            'message' => 'Conta atualizada com sucesso!',
            'data' => $account->fresh(),
        ]);
    }

    /**
     * Remove ou arquiva uma conta
     * - Sem lançamentos: exclusão física permitida
     * - Com lançamentos: apenas arquivamento
     */
    public function destroy(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        // Verificar se pode ser excluída fisicamente
        if ($account->canBeDeleted()) {
            $account->forceDelete();

            AuditLog::log('delete', 'Account', $account->id);

            return response()->json([
                'message' => 'Conta excluída permanentemente!',
                'action' => 'deleted',
            ]);
        }

        // Conta tem lançamentos - arquivar
        $account->archive();

        AuditLog::log('archive', 'Account', $account->id);

        return response()->json([
            'message' => 'Conta arquivada com sucesso! O histórico de lançamentos foi preservado.',
            'action' => 'archived',
        ]);
    }

    /**
     * Retorna o saldo atual da conta
     */
    public function balance(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        return response()->json([
            'data' => [
                'balance' => $account->current_balance,
            ],
        ]);
    }

    /**
     * Verifica se conta pode ser excluída ou apenas arquivada
     */
    public function checkDelete(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $canDelete = $account->canBeDeleted();
        $transactionsCount = $account->transactions()->count() + $account->outgoingTransfers()->count();

        return response()->json([
            'can_delete' => $canDelete,
            'transactions_count' => $transactionsCount,
            'message' => $canDelete
                ? 'Esta conta pode ser excluída permanentemente.'
                : 'Esta conta possui lançamentos e será arquivada. O histórico será preservado.',
        ]);
    }

    /**
     * Reativa uma conta arquivada
     */
    public function unarchive(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        if ($account->status !== 'archived') {
            return response()->json(['message' => 'Esta conta não está arquivada.'], 422);
        }

        $account->update(['status' => 'active']);

        AuditLog::log('unarchive', 'Account', $account->id);

        return response()->json([
            'message' => 'Conta reativada com sucesso!',
            'data' => $account->fresh(),
        ]);
    }

    /**
     * Ajusta o saldo da conta gerando uma transação automática
     */
    public function adjustBalance(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        if ($account->isArchived()) {
            return response()->json([
                'message' => 'Conta arquivada não pode ter saldo ajustado.',
            ], 400);
        }

        $validated = $request->validate([
            'target_balance' => ['required', 'numeric'],
        ], [
            'target_balance.required' => 'O novo saldo é obrigatório.',
        ]);

        try {
            $transaction = $this->accountService->adjustBalance(
                $account,
                $validated['target_balance'],
                $request->user()->id
            );

            $difference = $validated['target_balance'] - $account->current_balance;
            $action = $difference > 0 ? 'aumentou' : 'diminuiu';

            return response()->json([
                'message' => "Saldo ajustado com sucesso! O saldo {$action} em R$ " . number_format(abs($difference), 2, ',', '.'),
                'data' => [
                    'account' => $account->fresh(),
                    'transaction' => $transaction->load('category'),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
