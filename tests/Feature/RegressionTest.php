<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Card;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\InvoiceService;
use App\Services\InstallmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class RegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025-01-10'); // Base date
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_full_regression_checklist()
    {
        // 1. Create Card
        $response = $this->postJson('/api/cards', [
            'name' => 'Platinum Card',
            'account_id' => Account::factory()->create(['user_id' => $this->user->id])->id,
            'bank' => 'Bank A',
            'brand' => 'visa',
            'holder_name' => 'John Doe',
            'last_4_digits' => '1234',
            'valid_thru' => '12/2030',
            'credit_limit' => 5000.00,
            'type' => 'credito',
            'closing_day' => 20,
            'due_day' => 30,
        ]);
        $response->assertStatus(201);
        $cardId = $response->json('data.id');
        $card = Card::find($cardId);

        // Verify Audit Log: Create Card
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'model' => 'Card',
            'model_id' => $cardId,
            'user_id' => $this->user->id
        ]);

        // 2. Create Purchase (Installment)
        $msg = [
            'card_id' => $cardId,
            'value' => 300.00,
            'date' => '2025-01-15',
            'description' => 'Test Installment Purchase',
            'installments' => 3,
            'type' => 'despesa',
            'payment_method' => 'credito'
        ];

        $response = $this->postJson('/api/transactions', $msg);
        $response->assertStatus(201);
        $transactionId = $response->json('data.id');
        $transaction = Transaction::find($transactionId);

        // Verify Audit Log: Create Transaction
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create_credit_purchase',
            'model' => 'Transaction',
            'model_id' => $transactionId
        ]);

        // Verify Installments created
        $this->assertCount(3, $transaction->cardInstallments);

        // 3. Partial Pay
        $account = Account::factory()->create(['user_id' => $this->user->id, 'initial_balance' => 1000]);
        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->getOrCreateInvoice($card, '2025-01-15');

        // Ensure invoice has value
        $invoice->recalculateTotal(); // 100.00 from first installment
        $this->assertEquals(100.00, $invoice->total_value);

        $response = $this->postJson("/api/cards/{$cardId}/pay", [
            'account_id' => $account->id,
            'amount' => 50.00
        ]);
        $response->assertStatus(200);
        $invoice->refresh();

        $this->assertEquals('parcialmente_paga', $invoice->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'model' => 'CardInvoice',
            'model_id' => $invoice->id
        ]);

        // 4. Archive/Reactivate Account
        // Using a new account to test this flow
        $archiveAccount = Account::factory()->create(['user_id' => $this->user->id]);

        // Check Delete (should be allowed if no transactions)
        $response = $this->deleteJson("/api/accounts/{$archiveAccount->id}");

        $logs = \App\Models\AuditLog::where('model', 'Account')->get();
        // dump('Audit Logs for Account:', $logs->toArray());

        $response->assertStatus(200);
        $this->assertModelMissing($archiveAccount);

        // Verify Audit Log: Delete Account
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'delete',
            'model' => 'Account',
            'model_id' => $archiveAccount->id
        ]);

        // Restore/Unarchive test - create another one with transactions
        $keepAccount = Account::factory()->create(['user_id' => $this->user->id]);
        Transaction::factory()->create(['account_id' => $keepAccount->id]); // Add dependency

        $response = $this->deleteJson("/api/accounts/{$keepAccount->id}");
        $response->assertStatus(200);
        $response->assertJson(['action' => 'archived']);

        $keepAccount->refresh();
        $this->assertEquals('archived', $keepAccount->status);
        // Note: soft deletes uses deleted_at, but archive() only sets status='archived' and keeps deleted_at NULL?
        // Let's check logic: archive() -> status='archived', save(). Does NOT delete.

        $archiveLogs = \App\Models\AuditLog::where('model', 'Account')->where('model_id', $keepAccount->id)->get();
        dump('Archive Logs:', $archiveLogs->toArray());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'archive',
            'model' => 'Account',
            'model_id' => $keepAccount->id
        ]);

        // Restore
        $response = $this->postJson("/api/accounts/{$keepAccount->id}/unarchive");
        $response->assertStatus(200);
        $keepAccount->refresh();
        $this->assertEquals('active', $keepAccount->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'unarchive',
            'model' => 'Account',
            'model_id' => $keepAccount->id
        ]);

        // 5. Change Limit
        $response = $this->putJson("/api/cards/{$cardId}", [
            'credit_limit' => 7000.00
        ]);
        $response->assertStatus(200);
        $card->refresh();
        $this->assertEquals(7000.00, $card->credit_limit);

        // Verify Audit Log: Update Card
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'model' => 'Card',
            'model_id' => $cardId
        ]);
    }
}
