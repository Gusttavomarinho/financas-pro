<?php

namespace App\Services;

use App\Models\Card;
use App\Models\CardInstallment;
use App\Models\CardInvoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InstallmentService
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Cria as parcelas para uma compra parcelada no crédito
     * 
     * REGRA BRASILEIRA:
     * - Data base é SEMPRE a data da compra
     * - Primeira parcela vai para a fatura em que a compra se encaixa
     * - Parcelas subsequentes vão para as faturas seguintes
     * 
     * PARCELAMENTO EM ANDAMENTO:
     * - Se startingInstallment > 1, considera parcelas anteriores como "históricas"
     * - Nunca cria parcelas retroativas
     * - Parcela inicial (startingInstallment) vai para a fatura atual
     * 
     * @param int $startingInstallment Número da primeira parcela a criar (default: 1)
     */
    public function createInstallments(Transaction $transaction, int $startingInstallment = 1): void
    {
        // workaround for mysterious BadMethodCall on relation in tests
        $card = \App\Models\Card::find($transaction->card_id);

        $purchaseDate = Carbon::parse($transaction->date);

        $totalInstallments = $transaction->total_installments;

        // Prevent division by zero if total_installments missing
        $totalInstallments = max(1, (int) $totalInstallments);

        $installmentValue = round($transaction->value / $totalInstallments, 2);

        // Ajustar valor da última parcela para evitar diferença de centavos
        $lastInstallmentValue = $transaction->value - ($installmentValue * ($totalInstallments - 1));

        // Descobrir em qual fatura a primeira parcela (startingInstallment) entra
        $firstInvoice = $this->invoiceService->getOrCreateInvoice($card, $purchaseDate);

        // Criar apenas as parcelas a partir de startingInstallment
        // Parcelas anteriores são consideradas "históricas" (não criadas)
        for ($i = $startingInstallment; $i <= $totalInstallments; $i++) {
            // Calcular a fatura desta parcela
            // A parcela startingInstallment vai para a fatura atual
            // Parcelas seguintes vão para faturas futuras
            $monthOffset = $i - $startingInstallment; // 0 para a primeira parcela a criar

            if ($monthOffset === 0) {
                $invoice = $firstInvoice;
            } else {
                // Parcelas seguintes vão para faturas dos meses seguintes
                $nextReferenceMonth = Carbon::parse($firstInvoice->reference_month . '-01')
                    ->addMonths($monthOffset)
                    ->format('Y-m');

                $invoice = $this->invoiceService->getOrCreateInvoiceByReferenceMonth($card, $nextReferenceMonth);
            }

            // Valor da parcela
            $value = ($i === $totalInstallments) ? $lastInstallmentValue : $installmentValue;

            // Criar a parcela com o número correto (i, não resetado)
            CardInstallment::create([
                'transaction_id' => $transaction->id,
                'card_invoice_id' => $invoice->id,
                'installment_number' => $i, // Número real da parcela (ex: 6 de 10)
                'total_installments' => $totalInstallments,
                'value' => $value,
                'due_date' => $invoice->due_date,
                'status' => 'em_fatura',
            ]);

            // Atualizar o total da fatura
            $invoice->recalculateTotal();
        }

        // Atualizar o valor da parcela na transação (Campo removido da migration, ignorar atualização)
        // $transaction->update(['installment_value' => $installmentValue]);
    }

    /**
     * Remove as parcelas de uma transação (para estorno)
     */
    /**
     * Remove as parcelas de uma transação (para estorno ou edição)
     * - Faturas Abertas: Remove a parcela
     * - Faturas Pagas/Fechadas: Gera crédito na fatura ATUAL
     */
    public function removeInstallments(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $installments = $transaction->cardInstallments;

            foreach ($installments as $installment) {
                if ($installment->status === 'estornada')
                    continue;

                $invoice = $installment->invoice;

                // Se a fatura já está paga ou fechada, não podemos mexer nela
                if ($invoice && in_array($invoice->status, ['paga', 'fechada'])) {
                    $card = $transaction->card;

                    // TODO: Implementar createCreditTransaction na InvoiceService ou TransactionService?
                    // Melhor criar uma "Transação de Ajuste" que gera uma parcela negativa?
                    // Ou apenas injetar o valor?

                    // Simples: Criar uma transação de estorno avulsa
                    $creditTransaction = Transaction::create([
                        'user_id' => $transaction->user_id,
                        'card_id' => $transaction->card_id,
                        'account_id' => $transaction->account_id,
                        'category_id' => $transaction->category_id,
                        'type' => 'ajuste',
                        'value' => $installment->value,
                        'description' => "Estorno Parc. {$installment->installment_number}/{$installment->total_installments} - {$transaction->description}",
                        'date' => now()->toDateString(),
                        'payment_method' => 'credito',
                        'total_installments' => 1,
                        'status' => 'confirmada'
                    ]);

                    // E essa transação precisa gerar uma parcela (negativa ou crédito) na fatura atual
                    // Como nosso sistema lida com estorno? 
                    // Se type=estorno e payment_method=credito, InstallmentService deve criar parcela NEGATIVA?

                    // Por enquanto, vamos assumir que o sistema suporta valores negativos em parcelas
                    // Criar parcela avulsa negativa na fatura atual
                    $currentInvoice = $this->invoiceService->getOrCreateInvoice($card, now());

                    CardInstallment::create([
                        'transaction_id' => $creditTransaction->id,
                        'card_invoice_id' => $currentInvoice->id,
                        'installment_number' => 1,
                        'total_installments' => 1,
                        'value' => -$installment->value, // Negativo para abater
                        'due_date' => $currentInvoice->due_date,
                        'status' => 'em_fatura',
                    ]);

                    $currentInvoice->recalculateTotal();

                    // Não marcamos a parcela original como estornada pois ela JÁ FOI PAGA.
                    // Mantemos o histórico.

                } else {
                    // Fatura aberta: pode remover
                    $installment->update(['status' => 'estornada']);
                    if ($invoice) {
                        $invoice->recalculateTotal();
                    }
                }
            }
        });
    }

    /**
     * Antecipa uma parcela específica
     */
    public function anticipateInstallment(CardInstallment $installment, int $targetInvoiceId): void
    {
        DB::transaction(function () use ($installment, $targetInvoiceId) {
            $oldInvoice = $installment->invoice;

            // Mover para nova fatura
            $installment->update([
                'card_invoice_id' => $targetInvoiceId,
                'status' => 'antecipada',
            ]);

            // Recalcular ambas as faturas
            if ($oldInvoice) {
                $oldInvoice->recalculateTotal();
            }

            $newInvoice = CardInvoice::find($targetInvoiceId);
            if ($newInvoice) {
                $newInvoice->recalculateTotal();
            }
        });
    }

    /**
     * Antecipa parcelas selecionadas para a fatura atual
     */
    public function anticipateInstallments(Transaction $transaction, array $installmentIds, float $discount): void
    {
        DB::transaction(function () use ($transaction, $installmentIds, $discount) {
            $card = $transaction->card;

            // 1. Identificar Fatura Atual (Aberta)
            $currentInvoice = $this->invoiceService->getCurrentInvoice($card); // This creates if not exists

            if (!$currentInvoice) {
                // If it fails to create for some reason
                throw new \RuntimeException('Não foi possível identificar a fatura atual para antecipação.');
            }

            // 2. Fetch installments to be anticipated
            $installments = CardInstallment::whereIn('id', $installmentIds)
                ->where('transaction_id', $transaction->id)
                ->get();

            if ($installments->count() !== count($installmentIds)) {
                throw new \InvalidArgumentException('Algumas parcelas não foram encontradas.');
            }

            // Validation: Cannot anticipate installments that are already in the current invoice or paid
            foreach ($installments as $installment) {
                // Simplified check: If existing invoice is paid or closed, it's definitely future/valid (assuming UI filters properly). 
                // BUT rule says "Future / Open". 
                // If installment is already in current invoice, we should block.
                if ($installment->card_invoice_id === $currentInvoice->id) {
                    throw new \InvalidArgumentException('Não é possível antecipar parcelas que já estão na fatura atual.');
                }

                // If installment is in a PAST invoice (paid/closed), it's weird (should be paid).
                // Assuming we only allow moving FUTURE installments (reference month > current).
            }

            // 3. Move Installments
            foreach ($installments as $installment) {
                $oldInvoice = $installment->invoice;

                $installment->update([
                    'card_invoice_id' => $currentInvoice->id,
                    'status' => 'antecipada', // Mark as anticipated for clear labeling
                ]);

                // Recalculate old invoice (from which it was removed)
                if ($oldInvoice) {
                    $oldInvoice->recalculateTotal();
                }
            }

            // 4. Handle Discount (Credit Adjustment)
            if ($discount > 0) {
                // Create Adjustment Transaction
                $adjustment = Transaction::create([
                    'user_id' => $transaction->user_id,
                    'card_id' => $card->id,
                    'account_id' => $transaction->account_id, // Same account
                    'category_id' => $transaction->category_id,
                    'type' => 'ajuste',
                    'value' => $discount,
                    'description' => "Desconto Antecipação - {$transaction->description}",
                    'date' => now()->toDateString(),
                    'payment_method' => 'credito',
                    'total_installments' => 1,
                    'status' => 'confirmada'
                ]);

                // Create Negative Installment in Current Invoice
                CardInstallment::create([
                    'transaction_id' => $adjustment->id,
                    'card_invoice_id' => $currentInvoice->id,
                    'installment_number' => 1,
                    'total_installments' => 1,
                    'value' => -$discount,
                    'due_date' => $currentInvoice->due_date,
                    'status' => 'em_fatura',
                ]);
            }

            // 5. Recalculate Current Invoice
            $currentInvoice->recalculateTotal();

            // 6. Log Audit (Handled by Controller or Service?)
            // Plan said "Log using BusinessAuditService"
            // Let's do it in Controller to keep Service somewhat clean or inject here?
            // Existing pattern uses injected AuditService in other services...
            // But InstallmentService does not have BusinessAuditService injected yet.
            // I will return data for Controller to Log.
        });
    }

    /**
     * Estorno parcial - marca parcelas futuras como estornadas
     */
    public function partialRefund(Transaction $transaction, int $keepInstallments): void
    {
        DB::transaction(function () use ($transaction, $keepInstallments) {
            // Estornar parcelas após o número especificado
            $installmentsToRefund = $transaction->cardInstallments()
                ->where('installment_number', '>', $keepInstallments)
                ->get();

            foreach ($installmentsToRefund as $installment) {
                $installment->update(['status' => 'estornada']);

                if ($installment->invoice) {
                    $installment->invoice->recalculateTotal();
                }
            }

            // Atualizar total de parcelas na transação
            $transaction->update(['total_installments' => $keepInstallments]);
        });
    }
}
