<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * Tests for the Transaction Status (Pending/Paid) Feature
 * 
 * These tests ensure that:
 * 1. Transactions can be created with pending or confirmed status
 * 2. Status is auto-set based on transaction date
 * 3. Status can be toggled via API
 * 4. Existing transaction functionality remains unaffected
 */
class TransactionStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2025-01-15'); // Fixed date for testing

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->account = Account::factory()->create([
            'user_id' => $this->user->id,
            'initial_balance' => 10000
        ]);

        $this->category = Category::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'despesa'
        ]);
    }

    // =============================================
    // AUTO-STATUS BASED ON DATE TESTS
    // =============================================

    public function test_transaction_with_today_date_is_auto_confirmed(): void
    {
        $response = $this->postJson('/api/transactions', [
            'type' => 'despesa',
            'value' => 100.00,
            'description' => 'Expense Today',
            'date' => '2025-01-15', // Same as Carbon::now()
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'payment_method' => 'dinheiro',
        ]);

        $response->assertStatus(201);

        $transaction = Transaction::latest()->first();
        $this->assertEquals('confirmada', $transaction->status);
    }

    public function test_transaction_with_future_date_is_auto_pending(): void
    {
        $response = $this->postJson('/api/transactions', [
            'type' => 'despesa',
            'value' => 100.00,
            'description' => 'Future Expense',
            'date' => '2025-01-20', // 5 days in the future
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'payment_method' => 'dinheiro',
        ]);

        $response->assertStatus(201);

        $transaction = Transaction::latest()->first();
        $this->assertEquals('pendente', $transaction->status);
    }

    public function test_transaction_with_past_date_is_auto_pending(): void
    {
        $response = $this->postJson('/api/transactions', [
            'type' => 'despesa',
            'value' => 100.00,
            'description' => 'Past Expense',
            'date' => '2025-01-10', // 5 days in the past
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'payment_method' => 'dinheiro',
        ]);

        $response->assertStatus(201);

        $transaction = Transaction::latest()->first();
        $this->assertEquals('pendente', $transaction->status);
    }

    // =============================================
    // MANUAL STATUS OVERRIDE TESTS
    // =============================================

    public function test_can_create_transaction_with_explicit_status(): void
    {
        $response = $this->postJson('/api/transactions', [
            'type' => 'despesa',
            'value' => 100.00,
            'description' => 'Explicit Pending',
            'date' => '2025-01-15', // Today
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'payment_method' => 'dinheiro',
            'status' => 'pendente', // Override auto-status
        ]);

        $response->assertStatus(201);

        $transaction = Transaction::latest()->first();
        $this->assertEquals('pendente', $transaction->status);
    }

    // =============================================
    // TOGGLE STATUS ENDPOINT TESTS
    // =============================================

    public function test_can_toggle_status_from_pending_to_confirmed(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'pendente',
            'value' => 100.00,
        ]);

        $response = $this->patchJson("/api/transactions/{$transaction->id}/toggle-status");

        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertEquals('confirmada', $transaction->status);
    }

    public function test_can_toggle_status_from_confirmed_to_pending(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'confirmada',
            'value' => 100.00,
        ]);

        $response = $this->patchJson("/api/transactions/{$transaction->id}/toggle-status");

        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertEquals('pendente', $transaction->status);
    }

    public function test_cannot_toggle_status_of_another_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);

        $transaction = Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'status' => 'pendente',
        ]);

        $response = $this->patchJson("/api/transactions/{$transaction->id}/toggle-status");

        $response->assertStatus(403);
    }

    public function test_cannot_toggle_status_of_estornada_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'estornada',
            'value' => 100.00,
        ]);

        $response = $this->patchJson("/api/transactions/{$transaction->id}/toggle-status");

        $response->assertStatus(422);
    }

    // =============================================
    // FILTER BY STATUS TESTS
    // =============================================

    public function test_can_filter_transactions_by_pending_status(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'pendente',
            'description' => 'Pending One',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'confirmada',
            'description' => 'Confirmed One',
        ]);

        $response = $this->getJson('/api/transactions?status=pendente');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Pending One', $data[0]['description']);
    }

    public function test_can_filter_transactions_by_confirmed_status(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'pendente',
            'description' => 'Pending One',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'confirmada',
            'description' => 'Confirmed One',
        ]);

        $response = $this->getJson('/api/transactions?status=confirmada');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Confirmed One', $data[0]['description']);
    }

    // =============================================
    // PENDING SUMMARY ENDPOINT TESTS
    // =============================================

    public function test_pending_summary_returns_correct_totals(): void
    {
        // Create pending expenses
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'despesa',
            'status' => 'pendente',
            'value' => 150.00,
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'despesa',
            'status' => 'pendente',
            'value' => 50.00,
        ]);

        // Create pending income
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'receita',
            'status' => 'pendente',
            'value' => 500.00,
        ]);

        // Create confirmed (should not count)
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'despesa',
            'status' => 'confirmada',
            'value' => 999.00,
        ]);

        $response = $this->getJson('/api/transactions/pending-summary');

        $response->assertStatus(200);
        $response->assertJson([
            'pending_expenses' => 200.00,
            'pending_income' => 500.00,
            'pending_expenses_count' => 2,
            'pending_income_count' => 1,
        ]);
    }

    // =============================================
    // REGRESSION TESTS - ENSURE EXISTING FUNCTIONALITY WORKS
    // =============================================

    public function test_existing_transaction_creation_still_works(): void
    {
        $response = $this->postJson('/api/transactions', [
            'type' => 'receita',
            'value' => 500.00,
            'description' => 'Salary',
            'date' => '2025-01-15',
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'payment_method' => 'pix',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'description' => 'Salary',
            'value' => 500.00,
            'type' => 'receita',
        ]);
    }

    public function test_existing_transaction_update_still_works(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'description' => 'Original',
            'value' => 100.00,
        ]);

        $response = $this->putJson("/api/transactions/{$transaction->id}", [
            'description' => 'Updated Description',
            'value' => 150.00,
        ]);

        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertEquals('Updated Description', $transaction->description);
        $this->assertEquals(150.00, $transaction->value);
    }

    public function test_existing_transaction_delete_still_works(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $response = $this->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_existing_transaction_duplicate_still_works(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'description' => 'Original Transaction',
            'value' => 100.00,
        ]);

        $response = $this->postJson("/api/transactions/{$transaction->id}/duplicate");

        $response->assertStatus(201);
        $this->assertEquals(2, Transaction::where('description', 'Original Transaction')->count());
    }
}
