<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\User;
use App\Models\Transaction;
use App\Models\CardInvoice;
use App\Services\InstallmentService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnticipateInstallmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Setup Account for User (needed for some logic)
        $this->account = \App\Models\Account::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_anticipate_moves_installments_to_current_invoice()
    {
        $card = Card::factory()->create([
            'closing_day' => 10,
            'due_day' => 20,
            'credit_limit' => 1000
        ]);

        // Purchase Jan 05 -> Jan (2025-01), Feb, Mar
        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 300.00,
            'date' => '2025-01-05',
            'total_installments' => 3
        ]);

        $service = app(InstallmentService::class);
        $service->createInstallments($transaction);

        $installments = $transaction->cardInstallments()->orderBy('installment_number')->get();
        // 1: Jan (Current), 2: Feb, 3: Mar

        // Target: Current Invoice is Jan (2025-01)
        // Let's pretend we are in Jan 06
        $this->travelTo('2025-01-06');

        $invoiceService = app(InvoiceService::class);
        $currentInvoice = $invoiceService->getCurrentInvoice($card);

        // Anticipate Installment 3 (Mar) to Current
        $service->anticipateInstallments($transaction, [$installments[2]->id], 0.0);

        $installments[2]->refresh();

        $this->assertEquals($currentInvoice->id, $installments[2]->card_invoice_id, 'Installment 3 should be in current invoice');
        $this->assertEquals('antecipada', $installments[2]->status, 'Status should be antecipada');
    }

    public function test_anticipate_creates_discount_transaction()
    {
        $card = Card::factory()->create([
            'closing_day' => 10,
            'due_day' => 20,
        ]);

        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 200.00,
            'date' => '2025-01-05', // Jan
            'total_installments' => 2 // Jan, Feb
        ]);

        $service = app(InstallmentService::class);
        $service->createInstallments($transaction);

        $installments = $transaction->cardInstallments()->orderBy('installment_number')->get();

        // Anticipate 2nd installment (Feb) with 10.00 discount
        $this->travelTo('2025-01-06');

        $service->anticipateInstallments($transaction, [$installments[1]->id], 10.00);

        // Check Adjustment Transaction
        $adjustment = Transaction::where('type', 'ajuste')
            ->where('description', 'like', '%Desconto Antecipação%')
            ->first();

        $this->assertNotNull($adjustment, 'Adjustment transaction should be created');
        $this->assertEquals(10.00, $adjustment->value);

        // Check Adjustment Installment (Negative)
        $adjInstallment = $adjustment->cardInstallments->first();
        $this->assertEquals(-10.00, $adjInstallment->value);
        $this->assertEquals('em_fatura', $adjInstallment->status);
    }

    public function test_cannot_anticipate_paid_or_current_installments()
    {
        $card = Card::factory()->create();
        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 100,
            'total_installments' => 2
        ]);

        $service = app(InstallmentService::class);
        $service->createInstallments($transaction);
        $installments = $transaction->cardInstallments;

        // Ensure Current Invoice exists for the check to work
        // In the service, we look up current invoice.
        // We need to make sure the installment's invoice MATCHES what the service considers "Current".

        $invoiceService = app(InvoiceService::class);
        $currentInvoice = $invoiceService->getCurrentInvoice($card);

        // Ensure the installment is indeed in this current invoice
        $installments[0]->update(['card_invoice_id' => $currentInvoice->id]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Não é possível antecipar parcelas que já estão na fatura atual');

        $service->anticipateInstallments($transaction, [$installments[0]->id], 0);
    }
}
