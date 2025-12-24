<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringTransactionService
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * Processa todas as recorrências ativas que venceram até a data informada
     */
    public function processDue(Carbon $date): int
    {
        $count = 0;

        $dueRecurrings = RecurringTransaction::where('status', 'ativa')
            ->whereDate('next_occurrence', '<=', $date)
            ->get();

        foreach ($dueRecurrings as $recurring) {
            try {
                $transaction = $this->generateTransaction($recurring);

                // Send success notification
                $this->notificationService->notifyRecurringGenerated(
                    $recurring->user_id,
                    $recurring->description,
                    $recurring->value
                );

                $count++;
            } catch (\Exception $e) {
                Log::error("Falha ao gerar recorrência ID {$recurring->id}: " . $e->getMessage());

                // Send failure notification
                $this->notificationService->notifyRecurringFailed(
                    $recurring->user_id,
                    $recurring->description,
                    $e->getMessage()
                );
            }
        }

        return $count;
    }

    /**
     * Gera a transação atual e agenda a próxima
     * 
     * @param bool $forceManual Se true, é geração manual via botão "Gerar Agora" (só bloqueia por status).
     * @param bool $isDuplicate Se true, é uma geração adicional no mesmo período (duplicata intencional).
     */
    public function generateTransaction(RecurringTransaction $recurring, bool $forceManual = false, bool $isDuplicate = false): Transaction
    {
        return DB::transaction(function () use ($recurring, $forceManual, $isDuplicate) {
            // 1. Validar se ainda deve gerar
            // Para geração manual: só bloqueia por status
            // Para automático: bloqueia por status + data + duplicação
            $blockReason = $forceManual
                ? $recurring->getBlockReasonForManual()
                : $recurring->getBlockReason();

            if ($blockReason !== null) {
                throw new \Exception($blockReason);
            }

            // 2. Preparar nota com origem do lançamento
            $originNote = $forceManual
                ? "(Gerado manualmente em " . Carbon::now()->format('d/m/Y H:i') . " via Recorrência #{$recurring->id})"
                : "(Gerado automaticamente via Recorrência #{$recurring->id})";

            if ($isDuplicate) {
                $originNote .= "\n⚠️ Lançamento manual adicional no mesmo período";
            }

            // 3. Criar Transação
            $transactionData = [
                'user_id' => $recurring->user_id,
                'description' => $recurring->description,
                'value' => $recurring->value,
                'type' => $recurring->type,
                'date' => $recurring->next_occurrence,
                'category_id' => $recurring->category_id,
                'account_id' => $recurring->account_id,
                'card_id' => $recurring->card_id,
                'payment_method' => $recurring->payment_method,
                'notes' => trim(($recurring->notes ?? '') . "\n" . $originNote),
                'status' => 'confirmada',
                'total_installments' => 1,
                'recurring_transaction_id' => $recurring->id,
                'generated_manually' => $forceManual,
                'duplicate_period' => $isDuplicate,
            ];

            // Ajuste para compras no crédito: status = confirmada, parcelas = 1
            // Se for crédito, InstallmentService deve ser chamado se precisarmos gerar fatura?
            // TransactionService::createCreditPurchase espera params complexos.
            // Para MVP, vamos usar TransactionService->create simples se for débito/dinheiro.
            // Se for CRÉDITO, precisamos criar a CardInstallment.

            $transaction = null;

            if ($recurring->payment_method === 'credito' && $recurring->card_id) {
                // Injetar Service para não duplicar lógica?
                $transactionService = app(TransactionService::class);
                $card = $recurring->card; // Relationship

                // Usando lógica de compra à vista no crédito (1x)
                $transaction = $transactionService->createCreditPurchase(
                    $transactionData,
                    $card,
                    1, // installments
                    1  // current
                );
            } elseif ($recurring->type === 'transferencia') {
                // Transferência recorrente (ex: investimento mensal)
                // Requer conta destino. O schema atual tem account_id (origem?).
                // Falta 'to_account_id' na RecurringTransaction para suportar transferência completa.
                // MVP: Se for transferência, logar erro ou criar transação "simples" incompleta?
                // Decisão MVP: Não suportar Transferência Recorrente ainda (bloquear no Form).
                throw new \Exception("Transferência recorrente não suportada no MVP.");
            } else {
                $transaction = Transaction::create($transactionData);

                if ($transaction->type !== 'despesa' && $transaction->type !== 'receita') {
                    // Fallback check
                }

                // Atualizar saldo se necessário (Observer cuida disso se affects_balance=true)
                // Transaction Observer deve lidar com saldo se 'confirmada'
            }

            // 3. Atualizar Recorrência para próxima data
            $recurring->last_generated_at = $recurring->next_occurrence;
            $recurring->next_occurrence = $recurring->calculateNextOccurrence();

            // Verificar fim
            if ($recurring->end_date && $recurring->next_occurrence->gt($recurring->end_date)) {
                $recurring->status = 'concluida';
            }

            $recurring->save();

            AuditLog::log('generate_recurring', 'RecurringTransaction', $recurring->id, [
                'generated_transaction_id' => $transaction->id,
                'date' => $transactionData['date']
            ]);

            return $transaction;
        });
    }
}
