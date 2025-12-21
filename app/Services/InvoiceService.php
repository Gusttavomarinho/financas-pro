<?php

namespace App\Services;

use App\Models\Card;
use App\Models\CardInvoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Services\BusinessAuditService;

class InvoiceService
{
    protected BusinessAuditService $auditService;

    public function __construct(BusinessAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Retorna a fatura atual (aberta) do cartão
     */
    public function getCurrentInvoice(Card $card): CardInvoice
    {
        // Data base hoje para determinar fatura atual
        return $this->getOrCreateInvoice($card, Carbon::today());
    }

    /**
     * Busca ou cria uma fatura para o cartão baseado na data de compra
     * Lógica revisada para alocação em faturas abertas e futuras
     */
    public function getOrCreateInvoice(Card $card, $transactionDate): CardInvoice
    {
        $transactionDate = $transactionDate instanceof Carbon ? $transactionDate : Carbon::parse($transactionDate);

        // 1. Calcular detalhes da fatura ideal baseada na data de compra
        $invoiceDetails = $this->calculateInvoiceDetails($card, $transactionDate);

        // 2. Verificar se já existe
        $invoice = CardInvoice::where('card_id', $card->id)
            ->where('reference_month', $invoiceDetails['reference_month'])
            ->first();

        // 3. Se existe e está PAGA/FECHADA, pular para o próximo mês
        // A regra é: nunca lançar em fatura paga/fechada.
        if ($invoice && in_array($invoice->status, ['paga', 'fechada'])) {
            // Recalcular para o mês seguinte (compra processada na próxima fatura)
            // Apenas recalcula data base somando 1 mês
            $invoiceDetails = $this->calculateInvoiceDetails($card, $transactionDate->copy()->addMonth());

            // Verificar novamente se existe
            $invoice = CardInvoice::where('card_id', $card->id)
                ->where('reference_month', $invoiceDetails['reference_month'])
                ->first();
        }

        // 4. Se não existe, criar
        if (!$invoice) {
            $invoice = CardInvoice::create([
                'card_id' => $card->id,
                'reference_month' => $invoiceDetails['reference_month'],
                'period_start' => $invoiceDetails['period_start'],
                'period_end' => $invoiceDetails['period_end'],
                'closing_date' => $invoiceDetails['closing_date'],
                'due_date' => $invoiceDetails['due_date'],
                'total_value' => 0,
                'paid_value' => 0,
                'status' => 'aberta',
            ]);
        }

        return $invoice;
    }

    /**
     * Busca ou cria uma fatura para um mês de referência específico.
     * Garante a criação de faturas consecutivas sem pular meses.
     */
    public function getOrCreateInvoiceByReferenceMonth(Card $card, string $referenceMonth): CardInvoice
    {
        // 1. Verificar se já existe
        $invoice = CardInvoice::where('card_id', $card->id)
            ->where('reference_month', $referenceMonth)
            ->first();

        if ($invoice) {
            return $invoice;
        }

        // 2. Se não existe, calcular as datas baseadas no mês de referência (Vencimento)
        // Reference Month = YYYY-MM
        [$year, $month] = explode('-', $referenceMonth);
        $dueMonth = Carbon::create((int) $year, (int) $month, 1);

        $closingDay = $card->closing_day;
        $dueDay = $card->due_day;

        // Determinar Mês de Competência
        // Se DueDay > ClosingDay: Competência = Mês do Vencimento
        // Se DueDay <= ClosingDay: Competência = Mês Anterior ao Vencimento
        if ($dueDay > $closingDay) {
            $competenceDate = $dueMonth->copy();
        } else {
            $competenceDate = $dueMonth->copy()->subMonth();
        }

        // Calcular Data de Fechamento (Closing Date)
        $compYear = $competenceDate->year;
        $compMonth = $competenceDate->month;
        $daysInCompMonth = Carbon::create($compYear, $compMonth, 1)->daysInMonth;
        $effectiveClosingDay = min($closingDay, $daysInCompMonth);

        $closingDate = Carbon::create($compYear, $compMonth, $effectiveClosingDay);

        // Calcular Período (Start/End)
        $periodEnd = $closingDate;

        $prevMonth = $closingDate->copy()->subMonth();
        $prevDaysInMonth = $prevMonth->daysInMonth;
        $effectivePrevClosingDay = min($closingDay, $prevDaysInMonth);

        $periodStart = $prevMonth->copy()->day($effectivePrevClosingDay)->addDay();

        // Calcular Data de Vencimento
        $daysInDueMonth = $dueMonth->daysInMonth;
        $effectiveDueDay = min($dueDay, $daysInDueMonth);
        $dueDate = $dueMonth->copy()->day($effectiveDueDay);

        // Criar
        return CardInvoice::create([
            'card_id' => $card->id,
            'reference_month' => $referenceMonth,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'closing_date' => $closingDate,
            'due_date' => $dueDate,
            'total_value' => 0,
            'paid_value' => 0,
            'status' => 'aberta',
        ]);
    }

    /**
     * Calcula detalhes da fatura (período, fechamento, vencimento) para uma data de transação
     */
    private function calculateInvoiceDetails(Card $card, Carbon $date): array
    {
        $closingDay = $card->closing_day;
        $dueDay = $card->due_day;

        // Determinar "Mês de Competência" (Closing Date Month)
        // Se dia <= closingDay: Competência é o mês atual da data
        // Se dia > closingDay: Competência é o mês seguinte da data

        $competenceDate = $date->copy();
        if ($date->day > $closingDay) {
            $competenceDate->addMonth();
        }

        // Data de Fechamento: Dia ClosingDay do Mês de Competência
        // Cuidado com meses com menos dias
        $year = $competenceDate->year;
        $month = $competenceDate->month;
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $effectiveClosingDay = min($closingDay, $daysInMonth);

        $closingDate = Carbon::create($year, $month, $effectiveClosingDay);

        // Período de Consumo
        // Início: Dia seguinte ao fechamento do mês anterior
        $prevMonth = $closingDate->copy()->subMonth();
        $prevMonthClosingDay = min($closingDay, $prevMonth->daysInMonth);
        $periodStart = $prevMonth->copy()->day($prevMonthClosingDay)->addDay();
        $periodEnd = $closingDate;

        // Data de Vencimento
        // Baseada na regra do cartão e no Mês de Competência (Fechamento)
        // Se dueDay > closingDay: Vencimento no mesmo mês da competência
        // Se dueDay <= closingDay: Vencimento no mês seguinte à competência

        if ($dueDay > $closingDay) {
            // Mesmo mês
            $dueDateMonth = Carbon::create($year, $month, 1);
        } else {
            // Mês seguinte
            $dueDateMonth = Carbon::create($year, $month, 1)->addMonth();
        }

        $effectiveDueDay = min($dueDay, $dueDateMonth->daysInMonth);
        $dueDate = $dueDateMonth->copy()->day($effectiveDueDay);

        // Mês de Referência da Fatura = YYYY-MM do Vencimento (Regra Oficial)
        $referenceMonth = $dueDate->format('Y-m');

        return [
            'reference_month' => $referenceMonth,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'closing_date' => $closingDate,
            'due_date' => $dueDate,
        ];
    }

    /**
     * Registra um pagamento na fatura
     */
    public function payInvoice(CardInvoice $invoice, float $amount, int $accountId): Transaction
    {
        // Não permitir pagamento de valor 0 ou negativo
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Valor de pagamento deve ser maior que zero.');
        }

        // Capture Before State
        $before = $invoice->only(['paid_value', 'status']);

        return DB::transaction(function () use ($invoice, $amount, $accountId, $before) {
            // Incrementar valor pago no modelo para garantir atualização correta do status
            $invoice->paid_value += $amount;
            $invoice->updateStatus();
            $invoice->save();

            // Criar transação de débito na conta
            $transaction = Transaction::create([
                'user_id' => $invoice->card->user_id,
                'account_id' => $accountId,
                'card_id' => $invoice->card_id,
                'type' => 'despesa',
                'value' => $amount,
                'description' => "Pagamento fatura {$invoice->card->name} - {$this->formatMonth($invoice->reference_month)}",
                'date' => now()->toDateString(),
                'payment_method' => 'transferencia',
                'affects_balance' => true,
                'status' => 'confirmada',
            ]);

            // Capture After State and Log
            $after = $invoice->fresh()->only(['paid_value', 'status']);
            $this->auditService->log('pay_invoice', $invoice, [
                'before' => $before,
                'after' => $after
            ]);

            return $transaction;
        });
    }

    /**
     * Formata o mês de referência para exibição
     */
    private function formatMonth(string $referenceMonth): string
    {
        $months = [
            '01' => 'Janeiro',
            '02' => 'Fevereiro',
            '03' => 'Março',
            '04' => 'Abril',
            '05' => 'Maio',
            '06' => 'Junho',
            '07' => 'Julho',
            '08' => 'Agosto',
            '09' => 'Setembro',
            '10' => 'Outubro',
            '11' => 'Novembro',
            '12' => 'Dezembro',
        ];

        [$year, $month] = explode('-', $referenceMonth);
        return $months[$month] . '/' . $year;
    }

    /**
     * Atualiza status de todas as faturas vencidas
     */
    public function updateOverdueInvoices(): void
    {
        CardInvoice::where('status', 'fechada')
            ->where('due_date', '<', now())
            ->where('paid_value', '<', DB::raw('total_value'))
            ->update(['status' => 'vencida']);
    }

    /**
     * Fecha faturas cujo prazo de fechamento passou
     */
    public function closeExpiredInvoices(): void
    {
        CardInvoice::where('status', 'aberta')
            ->where('closing_date', '<=', now())
            ->update(['status' => 'fechada']);
    }

    /**
     * Reprocessa datas de faturas abertas após alteração do cartão
     */
    public function reprocessOpenInvoices(Card $card): void
    {
        $invoices = $card->invoices()->where('status', 'aberta')->get();

        foreach ($invoices as $invoice) {
            // Recalcula datas baseado no mês de referência (que é o mês de vencimento) e novos dias do cartão
            // Reference Month = YYYY-MM do Vencimento

            [$year, $month] = explode('-', $invoice->reference_month);
            $dueMonth = Carbon::create((int) $year, (int) $month, 1);

            $closingDay = $card->closing_day;
            $dueDay = $card->due_day;

            // 1. Determinar Mês de Competência (Fechamento)
            // Se DueDay > ClosingDay: Competência = Mês do Vencimento
            // Se DueDay <= ClosingDay: Competência = Mês Anterior ao Vencimento
            if ($dueDay > $closingDay) {
                $competenceDate = $dueMonth->copy();
            } else {
                $competenceDate = $dueMonth->copy()->subMonth();
            }

            // 2. Calcular Data de Fechamento (Closing Date)
            $compYear = $competenceDate->year;
            $compMonth = $competenceDate->month;
            $daysInMonth = Carbon::create($compYear, $compMonth, 1)->daysInMonth;
            $effectiveClosingDay = min($closingDay, $daysInMonth);

            $closingDate = Carbon::create($compYear, $compMonth, $effectiveClosingDay);

            // 3. Calcular Period Start e End
            $periodEnd = $closingDate;

            $prevMonth = $closingDate->copy()->subMonth();
            $prevDaysInMonth = $prevMonth->daysInMonth; // Corrigido bug de daysInMonth do mes anterior
            $prevClosingDay = min($closingDay, $prevDaysInMonth);

            $periodStart = $prevMonth->copy()->day($prevClosingDay)->addDay();

            // 4. Calcular Data de Vencimento
            // Deve cair no mês de referência (Reference Month)
            $daysInDueMonth = $dueMonth->daysInMonth;
            $effectiveDueDay = min($dueDay, $daysInDueMonth);
            $dueDate = $dueMonth->copy()->day($effectiveDueDay);

            $invoice->update([
                'closing_date' => $closingDate,
                'due_date' => $dueDate,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ]);

            // Se a data de fechamento passou com a mudança, fecha a fatura
            if ($invoice->closing_date <= now()) {
                $invoice->update(['status' => 'fechada']);
            }
        }
    }
}
