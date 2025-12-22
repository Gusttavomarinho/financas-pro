<?php

namespace App\Services;

use App\Models\Card;
use App\Models\CardInstallment;
use App\Models\CardInvoice;
use App\Models\Transaction;
use App\Services\BusinessAuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    protected InvoiceService $invoiceService;
    protected InstallmentService $installmentService;
    protected BusinessAuditService $auditService;

    public function __construct(
        InvoiceService $invoiceService,
        InstallmentService $installmentService,
        BusinessAuditService $auditService
    ) {
        $this->invoiceService = $invoiceService;
        $this->installmentService = $installmentService;
        $this->auditService = $auditService;
    }

    /**
     * Cria uma compra no cartão de crédito (à vista ou parcelada)
     * 
     * REGRA BRASILEIRA CORRETA:
     * - SEMPRE cria apenas 1 lançamento (a compra)
     * - As parcelas são entidades separadas vinculadas às faturas
     * - Data base é SEMPRE a data da compra, não o vencimento
     * 
     * @param int $startingInstallment Parcela inicial (para parcelamentos em andamento)
     */
    public function createCreditPurchase(array $data, Card $card, int $installments = 1, int $startingInstallment = 1): Transaction
    {
        return DB::transaction(function () use ($data, $card, $installments, $startingInstallment) {
            $installmentValue = round($data['value'] / $installments, 2);

            // Criar a transação única (a compra)
            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'card_id' => $card->id,
                'category_id' => $data['category_id'] ?? null,
                'type' => 'despesa',
                'value' => $data['value'], // Valor TOTAL da compra
                'description' => $data['description'],
                'date' => $data['date'], // Data REAL da compra
                'time' => $data['time'] ?? null,
                'total_installments' => $installments,
                'installment_value' => $installmentValue,
                'payment_method' => 'credito',
                'affects_balance' => false, // Compras no crédito não afetam saldo diretamente
                'status' => 'confirmada',
                'notes' => $data['notes'] ?? null,
            ]);

            // Criar as parcelas (entidades separadas vinculadas às faturas)
            // Passa a parcela inicial para suportar parcelamentos em andamento
            $this->installmentService->createInstallments($transaction, $startingInstallment);

            return $transaction;
        });
    }

    /**
     * Cria uma transferência entre contas
     */
    public function createTransfer(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Transação única de transferência
            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'account_id' => $data['account_id'], // Conta destino
                'from_account_id' => $data['from_account_id'], // Conta origem
                'type' => 'transferencia',
                'value' => $data['value'],
                'description' => $data['description'] ?? 'Transferência entre contas',
                'date' => $data['date'],
                'time' => $data['time'] ?? null,
                'payment_method' => 'transferencia',
                'affects_balance' => true,
                'status' => 'confirmada',
            ]);

            return [
                'debit' => $transaction,
            ];
        });
    }

    /**
     * Duplica uma transação
     * NÃO duplica parcelas - cria uma nova compra independente
     */
    public function duplicate(Transaction $transaction): Transaction
    {
        $newTransaction = Transaction::create([
            'user_id' => $transaction->user_id,
            'account_id' => $transaction->account_id,
            'card_id' => $transaction->card_id,
            'category_id' => $transaction->category_id,
            'type' => $transaction->type,
            'value' => $transaction->value,
            'description' => $transaction->description,
            'date' => now()->toDateString(),
            'time' => now()->format('H:i'),
            'payment_method' => $transaction->payment_method,
            'total_installments' => $transaction->total_installments ?? 1,
            'installment_value' => $transaction->installment_value,
            'affects_balance' => $transaction->isCreditPurchase() ? false : true,
            'status' => 'confirmada',
            'notes' => $transaction->notes,
        ]);

        // Se for compra no crédito parcelada, criar novas parcelas
        if ($newTransaction->isCreditPurchase() && $newTransaction->total_installments > 0) {
            $this->installmentService->createInstallments($newTransaction);
        }

        return $newTransaction;
    }

    /**
     * Estorna uma transação completamente
     */
    public function refund(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Se for compra no crédito, estornar parcelas
            if ($transaction->isCreditPurchase()) {
                $this->installmentService->removeInstallments($transaction);
            }

            // Marcar transação como estornada
            $transaction->update(['status' => 'estornada']);

            $this->auditService->log('refund_total', $transaction, [
                'before' => ['status' => 'confirmada'],
                'after' => ['status' => 'estornada']
            ]);
        });
    }

    /**
     * Atualiza uma transação com reprocessamento completo se necessário
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        // Capture State Before
        $before = $transaction->only(['value', 'date', 'description', 'category_id', 'total_installments', 'card_id', 'payment_method']);

        return DB::transaction(function () use ($transaction, $data, $before) {
            // Se for compra no crédito e houve mudança em campos críticos, precisamos refazer o parcelamento
            $criticalFields = ['value', 'date', 'total_installments', 'card_id'];
            $needsReprocessing = false;

            if ($transaction->isCreditPurchase()) {
                foreach ($criticalFields as $field) {
                    if (isset($data[$field]) && $data[$field] != $transaction->$field) {
                        $needsReprocessing = true;
                        break;
                    }
                }
            }

            if ($needsReprocessing) {
                // 1. Desfaz impacto antigo (Estorna parcelas ou gera crédito)
                $this->installmentService->removeInstallments($transaction);

                // 2. Atualiza a transação base
                $transaction->update($data);

                // 3. Recria parcelas (vão para faturas futuras/correntes baseadas na nova data)
                // Se installments não foi passado, usa o do modelo (que acabou de ser atualizado ou mantido)
                // update() já atualizou o modelo? Sim.
                $this->installmentService->createInstallments($transaction);

            } else {
                // Apenas atualização simples (descrição, categoria, notas, ou transação comum)
                $transaction->update($data);

                // Se mudou valor de transação comum que afeta saldo, o saldo da conta se ajusta automaticamente?
                // Accounts usam coluna calculada 'current_balance' que soma transações.
                // Então sim, atualizar o value da transação atualiza o saldo.
            }

            $after = $transaction->fresh()->only(['value', 'date', 'description', 'category_id', 'total_installments', 'card_id', 'payment_method']);

            $this->auditService->log('edit_transaction', $transaction, [
                'before' => $before,
                'after' => $after
            ]);

            return $transaction;
        });
    }

    /**
     * Estorno parcial - mantém apenas N parcelas
     */
    public function partialRefund(Transaction $transaction, int $keepInstallments): void
    {
        // ... existing implementation ...
        if (!$transaction->isCreditPurchase()) {
            throw new \InvalidArgumentException('Estorno parcial só é possível para compras no crédito.');
        }

        if ($keepInstallments < 1 || $keepInstallments >= $transaction->total_installments) {
            throw new \InvalidArgumentException('Número de parcelas a manter deve ser entre 1 e ' . ($transaction->total_installments - 1));
        }

        DB::transaction(function () use ($transaction, $keepInstallments) {
            $this->installmentService->partialRefund($transaction, $keepInstallments);

            // Recalcular valor total
            $newValue = $transaction->installment_value * $keepInstallments;
            $transaction->update([
                'total_installments' => $keepInstallments,
                'value' => $newValue,
            ]);

            $this->auditService->log('refund_partial', $transaction, [
                'before' => ['total_installments' => $transaction->getOriginal('total_installments'), 'value' => $transaction->getOriginal('value')],
                'after' => ['total_installments' => $keepInstallments, 'value' => $newValue]
            ]);
        });
    }

    /**
     * Estorno por valor - cria ajuste contábil mantendo histórico intacto
     */
    public function refundByValue(Transaction $transaction, float $refundValue): void
    {
        if ($refundValue <= 0) {
            throw new \InvalidArgumentException('Valor de estorno deve ser maior que zero.');
        }

        if ($refundValue > $transaction->value) {
            throw new \InvalidArgumentException('Valor de estorno não pode ser maior que o valor da transação.');
        }

        DB::transaction(function () use ($transaction, $refundValue) {
            $isFullRefund = abs($refundValue - $transaction->value) < 0.01;

            if ($isFullRefund) {
                // Estorno total: usa lógica existente
                $this->refund($transaction);
                return;
            }

            // Estorno parcial por valor: criar ajuste contábil
            $card = $transaction->card;

            // Criar transação de ajuste
            $adjustmentTransaction = Transaction::create([
                'user_id' => $transaction->user_id,
                'card_id' => $card->id,
                'account_id' => $transaction->account_id,
                'category_id' => $transaction->category_id,
                'type' => 'ajuste',
                'value' => $refundValue,
                'description' => "Estorno Parcial - {$transaction->description}",
                'date' => now()->toDateString(),
                'payment_method' => 'credito',
                'total_installments' => 1,
                'status' => 'confirmada',
                'notes' => "Estorno de R$ " . number_format($refundValue, 2, ',', '.') . " ref. transação #{$transaction->id}"
            ]);

            // Criar parcela negativa na fatura atual
            $currentInvoice = $this->invoiceService->getCurrentInvoice($card);

            CardInstallment::create([
                'transaction_id' => $adjustmentTransaction->id,
                'card_invoice_id' => $currentInvoice->id,
                'installment_number' => 1,
                'total_installments' => 1,
                'value' => -$refundValue,
                'due_date' => $currentInvoice->due_date,
                'status' => 'em_fatura',
            ]);

            $currentInvoice->recalculateTotal();

            $this->auditService->log('refund_partial', $transaction, [
                'refund_value' => $refundValue,
                'remaining_value' => $transaction->value - $refundValue,
                'adjustment_transaction_id' => $adjustmentTransaction->id
            ]);
        });
    }

    /**
     * Atualiza apenas as notas de uma transação (permitido mesmo em transações pagas)
     */
    public function updateNotes(Transaction $transaction, ?string $notes): Transaction
    {
        $before = $transaction->notes;

        $transaction->update(['notes' => $notes]);

        $this->auditService->log('edit_notes', $transaction, [
            'before' => $before,
            'after' => $notes
        ]);

        return $transaction;
    }
}
