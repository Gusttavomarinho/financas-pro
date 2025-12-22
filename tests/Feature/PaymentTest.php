<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Set fixed time to ensure 'getCurrentInvoice' returns expected invoice
        // Using Jan 15th to target Jan/Feb logic correctly.
        Carbon::setTestNow('2025-01-09');

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_full_payment_closes_invoice_and_deducts_balance()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'initial_balance' => 1000.00
        ]);

        $card = Card::factory()->create([
            'user_id' => $this->user->id,
            'credit_limit' => 5000.00,
            'closing_day' => 10,
            'due_day' => 20
        ]);

        // Create transaction of 500.00
        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 500.00,
            'date' => '2025-01-05',
            'type' => 'despesa'
        ]);

        // Generate Invoice
        $service = app(InvoiceService::class);
        $invoice = $service->getOrCreateInvoice($card, '2025-01-05');

        // Manual set total_value to simulate items for payment logic
        $invoice->total_value = 500.00;
        $invoice->save();
        $invoice->refresh();

        // Pay 500
        $response = $this->postJson("/api/cards/{$card->id}/pay", [
            'account_id' => $account->id,
            'invoice_id' => $invoice->id,
            'amount' => 500.00
        ]);

        $response->assertStatus(200);

        $invoice->refresh();
        $account->refresh();

        $this->assertEquals('paga', $invoice->status);
        $this->assertEquals(500.00, $invoice->paid_value);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'value' => 500.00,
            'type' => 'despesa'
        ]);
    }

    public function test_partial_payment_updates_values_and_keeps_invoice_open()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'initial_balance' => 1000.00
        ]);

        $card = Card::factory()->create([
            'user_id' => $this->user->id,
            'closing_day' => 10,
            'due_day' => 20
        ]);

        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 500.00,
            'date' => '2025-01-05'
        ]);

        $service = app(InvoiceService::class);
        $invoice = $service->getOrCreateInvoice($card, '2025-01-05');

        // Manual set total_value
        $invoice->total_value = 500.00;
        $invoice->save();

        // Pay 200.00
        $response = $this->postJson("/api/cards/{$card->id}/pay", [
            'account_id' => $account->id,
            'invoice_id' => $invoice->id,
            'amount' => 200.00
        ]);

        $response->assertStatus(200);
        $invoice->refresh();

        $this->assertNotEquals('paga', $invoice->status);
        $this->assertEquals('parcialmente_paga', $invoice->status);
        $this->assertEquals(200.00, $invoice->paid_value);

        // Verify remaining
        $this->assertEquals(300.00, $invoice->total_value - $invoice->paid_value);
    }
}
