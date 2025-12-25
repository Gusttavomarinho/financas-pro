<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class AccountService
{
    /**
     * Cria uma nova conta com transação automática de saldo inicial.
     * 
     * REGRA: Saldo inicial NUNCA é aplicado diretamente.
     * Em vez disso, é criada uma transação de "Saldo inicial".
     */
    public function createAccount(array $data, int $userId): Account
    {
        return DB::transaction(function () use ($data, $userId) {
            $initialBalance = $data['initial_balance'] ?? 0;

            // 1. Criar conta com initial_balance = 0 (saldo vem das transações)
            $account = Account::create([
                'user_id' => $userId,
                'type' => $data['type'],
                'name' => $data['name'],
                'initial_balance' => 0, // Sempre 0, saldo vem das transações
                'currency' => $data['currency'] ?? 'BRL',
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? null,
                'bank' => $data['bank'] ?? null,
                'agency' => $data['agency'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'active',
                'exclude_from_totals' => $data['exclude_from_totals'] ?? false,
            ]);

            // 2. Se saldo inicial != 0, criar transação automática
            if ($initialBalance != 0) {
                $this->createInitialBalanceTransaction($account, $initialBalance, $userId);
            }

            return $account;
        });
    }

    /**
     * Cria transação de saldo inicial.
     */
    private function createInitialBalanceTransaction(Account $account, float $amount, int $userId): Transaction
    {
        $isIncome = $amount > 0;
        $category = $this->getSystemCategory($isIncome ? 'receita' : 'despesa');

        $transaction = Transaction::create([
            'user_id' => $userId,
            'account_id' => $account->id,
            'type' => $isIncome ? 'receita' : 'despesa',
            'value' => abs($amount),
            'description' => 'Saldo inicial',
            'date' => now()->toDateString(),
            'time' => now()->format('H:i'),
            'payment_method' => 'transferencia',
            'affects_balance' => true,
            'status' => 'confirmada',
            'notes' => 'Transação gerada automaticamente ao criar a conta.',
            'category_id' => $category?->id,
        ]);

        AuditLog::log('initial_balance', 'Transaction', $transaction->id, [
            'account_id' => $account->id,
            'amount' => $amount,
            'auto_generated' => true,
        ]);

        return $transaction;
    }

    /**
     * Ajusta o saldo de uma conta criando transação de ajuste.
     * 
     * REGRA: Nunca atualizar saldo diretamente.
     * Gera transação de ajuste baseada na diferença.
     */
    public function adjustBalance(Account $account, float $targetBalance, int $userId): Transaction
    {
        $currentBalance = $account->current_balance;
        $difference = $targetBalance - $currentBalance;

        if (abs($difference) < 0.01) {
            throw new \InvalidArgumentException('O novo saldo é igual ao saldo atual.');
        }

        $isIncome = $difference > 0;
        $category = $this->getSystemCategory($isIncome ? 'receita' : 'despesa');

        $transaction = Transaction::create([
            'user_id' => $userId,
            'account_id' => $account->id,
            'type' => $isIncome ? 'receita' : 'despesa',
            'value' => abs($difference),
            'description' => 'Ajuste manual de saldo',
            'date' => now()->toDateString(),
            'time' => now()->format('H:i'),
            'payment_method' => 'transferencia',
            'affects_balance' => true,
            'status' => 'confirmada',
            'notes' => sprintf(
                'Ajuste de R$ %s para R$ %s (diferença: %s R$ %s)',
                number_format($currentBalance, 2, ',', '.'),
                number_format($targetBalance, 2, ',', '.'),
                $isIncome ? '+' : '-',
                number_format(abs($difference), 2, ',', '.')
            ),
            'category_id' => $category?->id,
        ]);

        AuditLog::log('balance_adjustment', 'Transaction', $transaction->id, [
            'account_id' => $account->id,
            'previous_balance' => $currentBalance,
            'target_balance' => $targetBalance,
            'difference' => $difference,
            'auto_generated' => true,
        ]);

        return $transaction;
    }

    /**
     * Obtém a categoria do sistema para transações automáticas.
     */
    private function getSystemCategory(string $type): ?Category
    {
        $name = $type === 'receita' ? 'Outras receitas' : 'Outras despesas';

        return Category::where('is_system', true)
            ->where('type', $type)
            ->where('name', $name)
            ->whereNull('user_id')
            ->first();
    }
}
