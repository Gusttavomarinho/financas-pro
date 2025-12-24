<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\CardInvoice;
use App\Models\CardInstallment;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CardTestDataSeeder extends Seeder
{
    /**
     * Creates test data for cards with various invoice scenarios:
     * - Paid invoices (historical)
     * - Overdue invoices with open balance
     * - Future invoices
     * - Partial payments
     * - Early payments with discount
     */
    public function run(): void
    {
        // Get first card from user's account
        $card = Card::first();

        if (!$card) {
            $this->command->warn('No card found. Please create a card first.');
            return;
        }

        $this->command->info("Creating test data for card: {$card->name}");

        // Clean up previous test data (transactions with 'seeder' in notes)
        $testTransactionIds = Transaction::where('card_id', $card->id)
            ->where('notes', 'like', '%seeder%')
            ->pluck('id');

        if ($testTransactionIds->count() > 0) {
            // Delete related installments
            CardInstallment::whereIn('transaction_id', $testTransactionIds)->delete();
            // Delete transactions
            Transaction::whereIn('id', $testTransactionIds)->forceDelete();
            $this->command->line("  - Cleaned up {$testTransactionIds->count()} previous test transactions");
        }

        // Delete empty test invoices
        $emptyInvoices = CardInvoice::where('card_id', $card->id)
            ->where('total_value', 0)
            ->delete();

        // Get expense category
        $category = Category::where('type', 'despesa')->first();
        $categoryId = $category?->id;

        // 1. Paid invoice from 2 months ago
        $this->createPaidInvoice($card, $categoryId, Carbon::now()->subMonths(2));

        // 2. Paid invoice from 1 month ago
        $this->createPaidInvoice($card, $categoryId, Carbon::now()->subMonth());

        // 3. Overdue invoice with open balance (due date was 5 days ago)
        $this->createOverdueInvoice($card, $categoryId);

        // 4. Current invoice with partial payment (early payment)
        $this->createPartiallyPaidInvoice($card, $categoryId);

        // 5. Future invoice
        $this->createFutureInvoice($card, $categoryId, Carbon::now()->addMonth());

        // 6. Future invoice with advance payment
        $this->createFutureInvoiceWithAdvancePayment($card, $categoryId);

        $this->command->info('Test data created successfully!');
    }

    private function createPaidInvoice(Card $card, ?int $categoryId, Carbon $referenceDate): void
    {
        $periodStart = $referenceDate->copy()->startOfMonth();
        $periodEnd = $referenceDate->copy()->endOfMonth();
        $closingDate = $referenceDate->copy()->day($card->closing_day);
        $dueDate = $referenceDate->copy()->addMonth()->day($card->due_day);

        $invoice = CardInvoice::firstOrCreate(
            [
                'card_id' => $card->id,
                'reference_month' => $referenceDate->format('Y-m'),
            ],
            [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'closing_date' => $closingDate,
                'due_date' => $dueDate,
                'total_value' => 0,
                'paid_value' => 0,
                'status' => 'paga',
            ]
        );

        // Update invoice values
        $invoice->update([
            'total_value' => 850.00,
            'paid_value' => 850.00,
            'status' => 'paga',
        ]);

        // Create transaction and installment
        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Compra teste - fatura paga',
            850.00,
            $periodStart->addDays(5),
            'paga'
        );

        $this->command->line("  - Created paid invoice for {$referenceDate->format('Y-m')}");
    }

    private function createOverdueInvoice(Card $card, ?int $categoryId): void
    {
        // Due date was 5 days ago
        $dueDate = Carbon::now()->subDays(5);
        $closingDate = $dueDate->copy()->subDays(10);
        $referenceDate = $closingDate->copy();
        $periodStart = $referenceDate->copy()->startOfMonth();
        $periodEnd = $referenceDate->copy()->endOfMonth();

        $invoice = CardInvoice::firstOrCreate(
            [
                'card_id' => $card->id,
                'reference_month' => $referenceDate->format('Y-m'),
            ],
            [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'closing_date' => $closingDate,
                'due_date' => $dueDate,
                'total_value' => 0,
                'paid_value' => 0,
                'status' => 'aberta',
            ]
        );

        // Update invoice values
        $invoice->update([
            'total_value' => 1250.00,
            'paid_value' => 0,
            'status' => 'vencida',
        ]);

        // Create transactions
        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Restaurante - fatura vencida',
            450.00,
            $periodStart->addDays(3),
            'em_fatura'
        );

        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Supermercado - fatura vencida',
            800.00,
            $periodStart->addDays(10),
            'em_fatura'
        );

        $this->command->line("  - Created overdue invoice (due {$dueDate->format('d/m/Y')})");
    }

    private function createPartiallyPaidInvoice(Card $card, ?int $categoryId): void
    {
        $now = Carbon::now();
        $closingDate = $now->copy()->addDays(5)->day($card->closing_day);
        $dueDate = $closingDate->copy()->addMonth()->day($card->due_day);
        $periodStart = $closingDate->copy()->subMonth()->day($card->closing_day)->addDay();
        $periodEnd = $closingDate;

        // Make sure due date is in the future
        if ($dueDate->isPast()) {
            $dueDate = $dueDate->addMonth();
            $closingDate = $closingDate->addMonth();
        }

        $invoice = CardInvoice::firstOrCreate(
            [
                'card_id' => $card->id,
                'reference_month' => $now->format('Y-m'),
            ],
            [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'closing_date' => $closingDate,
                'due_date' => $dueDate,
                'total_value' => 0,
                'paid_value' => 0,
                'status' => 'aberta',
            ]
        );

        // Update invoice values
        $invoice->update([
            'total_value' => 2000.00,
            'paid_value' => 500.00,
            'status' => 'aberta',
        ]);

        // Create transactions
        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Eletrônicos - parcela 2/5',
            400.00,
            $periodStart->addDays(2),
            'em_fatura',
            2,
            5
        );

        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Roupa - parcela 1/3',
            300.00,
            $periodStart->addDays(8),
            'em_fatura',
            1,
            3
        );

        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Farmácia',
            1300.00,
            $periodStart->addDays(12),
            'em_fatura'
        );

        $this->command->line("  - Created current invoice with partial payment (R$ 500 of R$ 2000 paid)");
    }

    private function createFutureInvoice(Card $card, ?int $categoryId, Carbon $referenceDate): void
    {
        $periodStart = $referenceDate->copy()->startOfMonth();
        $periodEnd = $referenceDate->copy()->endOfMonth();
        $closingDate = $referenceDate->copy()->day($card->closing_day);
        $dueDate = $referenceDate->copy()->addMonth()->day($card->due_day);

        // Ensure closing date is in the future
        if ($closingDate->isPast()) {
            $closingDate = $closingDate->addMonth();
            $dueDate = $dueDate->addMonth();
        }

        $invoice = CardInvoice::firstOrCreate(
            [
                'card_id' => $card->id,
                'reference_month' => $referenceDate->format('Y-m'),
            ],
            [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'closing_date' => $closingDate,
                'due_date' => $dueDate,
                'total_value' => 0,
                'paid_value' => 0,
                'status' => 'aberta',
            ]
        );

        // Update invoice values
        $invoice->update([
            'total_value' => 650.00,
            'paid_value' => 0,
            'status' => 'aberta',
        ]);

        // Add future transactions
        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Assinatura streaming - futura',
            50.00,
            $periodStart->addDays(1),
            'pendente'
        );

        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Eletrônicos - parcela 3/5',
            400.00,
            $periodStart->addDays(2),
            'pendente',
            3,
            5
        );

        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Academia mensal',
            200.00,
            $periodStart->addDays(5),
            'pendente'
        );

        $this->command->line("  - Created future invoice for {$referenceDate->format('Y-m')}");
    }

    private function createFutureInvoiceWithAdvancePayment(Card $card, ?int $categoryId): void
    {
        $referenceDate = Carbon::now()->addMonths(2);
        $periodStart = $referenceDate->copy()->startOfMonth();
        $periodEnd = $referenceDate->copy()->endOfMonth();
        $closingDate = $referenceDate->copy()->day($card->closing_day);
        $dueDate = $referenceDate->copy()->addMonth()->day($card->due_day);

        $invoice = CardInvoice::firstOrCreate(
            [
                'card_id' => $card->id,
                'reference_month' => $referenceDate->format('Y-m'),
            ],
            [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'closing_date' => $closingDate,
                'due_date' => $dueDate,
                'total_value' => 0,
                'paid_value' => 0,
                'status' => 'aberta',
            ]
        );

        // Update invoice values
        $invoice->update([
            'total_value' => 800.00,
            'paid_value' => 300.00,
            'status' => 'aberta',
        ]);

        // Add transactions
        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Roupa - parcela 2/3 (antecipada)',
            300.00,
            $periodStart->addDays(8),
            'antecipada',
            2,
            3
        );

        $this->createTransactionWithInstallment(
            $card,
            $invoice,
            $categoryId,
            'Viagem parcela 1/4',
            500.00,
            $periodStart->addDays(15),
            'pendente',
            1,
            4
        );

        $this->command->line("  - Created future invoice with advance payment (R$ 300 paid in advance)");
    }

    private function createTransactionWithInstallment(
        Card $card,
        CardInvoice $invoice,
        ?int $categoryId,
        string $description,
        float $value,
        Carbon $date,
        string $status,
        int $installmentNumber = 1,
        int $totalInstallments = 1
    ): void {
        // Create transaction
        $transaction = Transaction::create([
            'user_id' => $card->user_id,
            'account_id' => $card->account_id,
            'card_id' => $card->id,
            'category_id' => $categoryId,
            'type' => 'despesa',
            'payment_method' => 'credito',
            'description' => $description,
            'value' => $value,
            'date' => $date->format('Y-m-d'),
            'time' => '12:00:00',
            'total_installments' => $totalInstallments,
            'status' => 'confirmada',
            'affects_balance' => false, // Credit card purchase doesn't affect balance directly
            'notes' => 'Dado de teste - seeder',
        ]);

        // Create installment linking to invoice
        CardInstallment::create([
            'card_invoice_id' => $invoice->id,
            'transaction_id' => $transaction->id,
            'installment_number' => $installmentNumber,
            'total_installments' => $totalInstallments,
            'value' => $value,
            'status' => $status,
        ]);
    }
}
