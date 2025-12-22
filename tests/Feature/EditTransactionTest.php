<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Account;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class EditTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Base date: Jan 05, 2025 (Avoid Closing Day Jan 10 boundary)
        Carbon::setTestNow('2025-01-05');
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->account = Account::factory()->create(['user_id' => $this->user->id, 'initial_balance' => 10000]);
        $this->card = Card::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'closing_day' => 10,
            'due_day' => 20,
            'credit_limit' => 5000
        ]);
    }

    public function test_simple_edit_does_not_reprocess()
    {
        $transaction = Transaction::factory()->create([
            'card_id' => $this->card->id,
            'account_id' => $this->account->id,
            'payment_method' => 'credito',
            'value' => 100.00,
            'total_installments' => 1,
            'description' => 'Original'
        ]);

        $response = $this->putJson("/api/transactions/{$transaction->id}", [
            'description' => 'Updated',
            'notes' => 'Some notes'
        ]);

        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertEquals('Updated', $transaction->description);
    }

    public function test_critical_edit_on_open_invoice_reprocesses()
    {
        $transaction = Transaction::factory()->create([
            'card_id' => $this->card->id,
            'account_id' => $this->account->id,
            'payment_method' => 'credito',
            'value' => 100.00,
            'date' => '2025-01-15', // Feb Invoice
            'total_installments' => 2, // 50 each
            'description' => 'Original'
        ]);

        // Generate installments
        $service = app(\App\Services\InstallmentService::class);
        $service->createInstallments($transaction);

        $this->assertCount(2, $transaction->cardInstallments);

        // Edit Value -> 200.00
        $response = $this->putJson("/api/transactions/{$transaction->id}", [
            'value' => 200.00,
            'installments' => 2
        ]);

        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertEquals(200.00, $transaction->value);

        $allInstallments = $transaction->cardInstallments;

        // 2 estornadas, 2 novas = 4 total
        $this->assertEquals(4, $allInstallments->count());
        $estornadas = $allInstallments->where('status', 'estornada');
        $this->assertEquals(2, $estornadas->count());
        $active = $allInstallments->where('status', 'em_fatura');
        $this->assertEquals(2, $active->count());
        $this->assertEquals(100.00, $active->first()->value);
    }

    public function test_critical_edit_on_paid_invoice_creates_adjustment()
    {
        // 1. Create Purchase in Past (Dec 2024) -> Jan 2025 Invoice
        $pastDate = '2024-12-15';
        $transaction = Transaction::factory()->create([
            'card_id' => $this->card->id,
            'account_id' => $this->account->id,
            'payment_method' => 'credito',
            'value' => 100.00,
            'date' => $pastDate,
            'total_installments' => 1,
            'description' => 'Past Purchase'
        ]);

        $service = app(\App\Services\InstallmentService::class);
        $service->createInstallments($transaction);

        // 2. Get Invoice (Jan 2025) and Pay it
        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->getOrCreateInvoice($this->card, $pastDate);
        $invoice->recalculateTotal();

        // Pay it via API
        $response = $this->postJson("/api/cards/{$this->card->id}/pay", [
            'account_id' => $this->account->id,
            'invoice_id' => $invoice->id,
            'amount' => 100.00
        ]);
        $response->assertStatus(200);

        $invoice->refresh();
        $this->assertEquals('paga', $invoice->status);

        // 3. Edit Transaction
        $response = $this->putJson("/api/transactions/{$transaction->id}", [
            'value' => 50.00,
            'installments' => 1
        ]);

        $response->assertStatus(200);

        $transaction->refresh(); // REFRESH RELATIONSHIPS

        // Verify Adjustment Transaction exists (Type 'ajuste')
        $adjustment = Transaction::where('type', 'ajuste')->first();

        $this->assertNotNull($adjustment, 'Adjustment transaction not found');
        $this->assertEquals(100.00, $adjustment->value);

        $adjustmentInstallment = $adjustment->cardInstallments->first();
        $this->assertNotNull($adjustmentInstallment);
        $this->assertEquals(-100.00, $adjustmentInstallment->value);

        // Verify New Installment (50.00)
        // Access via relationship - we now have 2 installments (Old and New)
        $newInstallment = $transaction->cardInstallments->where('status', 'em_fatura')->where('value', 50.00)->first();
        $this->assertNotNull($newInstallment, 'New installment (50.00) not found');

        $oldInstallment = $transaction->cardInstallments->where('status', 'em_fatura')->where('value', 100.00)->first();
        $this->assertNotNull($oldInstallment, 'Old installment (100.00) not preserved');
    }
}
