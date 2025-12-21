<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\User;
use App\Models\Transaction;
use App\Services\InstallmentService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_create_installments_generates_correct_values()
    {
        $card = Card::factory()->create([
            'closing_day' => 10,
            'due_day' => 20,
        ]);

        // Purchase: 300.00 in 3 installments
        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 300.00,
            'date' => '2025-01-05',
            'total_installments' => 3,
            'description' => 'Test Purchase'
        ]);

        $service = app(InstallmentService::class);
        $service->createInstallments($transaction);

        // Relation is cardInstallments, not installments
        $this->assertCount(3, $transaction->cardInstallments);

        foreach ($transaction->cardInstallments as $installment) {
            $this->assertEquals(100.00, $installment->value);
        }
    }

    public function test_create_installments_handles_cents_correctly()
    {
        $card = Card::factory()->create([
            'closing_day' => 10,
            'due_day' => 20,
        ]);

        // Purchase: 100.00 in 3 installments (33.33, 33.33, 33.34)
        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 100.00,
            'date' => '2025-01-05',
            'total_installments' => 3
        ]);

        $service = app(InstallmentService::class);
        $service->createInstallments($transaction);

        $installments = $transaction->cardInstallments;
        $this->assertCount(3, $installments);

        // Sorting by installment_number to ensure order
        $installments = $installments->sortBy('installment_number')->values();

        $this->assertEquals(33.33, $installments[0]->value);
        $this->assertEquals(33.33, $installments[1]->value);
        // Last one gets the remainder
        $this->assertEquals(33.34, $installments[2]->value);

        $total = $installments->sum('value');
        $this->assertEquals(100.00, $total);
    }

    public function test_installments_are_assigned_to_consecutive_months()
    {
        $card = Card::factory()->create([
            'closing_day' => 10,
            'due_day' => 20,
        ]);

        // Purchase Jan 05 -> Month 1: Jan Invoice (Due Jan 20)
        // Month 2: Feb Invoice
        // Month 3: Mar Invoice
        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 300.00,
            'date' => '2025-01-05',
            'total_installments' => 3
        ]);

        $service = app(InstallmentService::class);
        $service->createInstallments($transaction);

        $installments = $transaction->cardInstallments()->with('invoice')->get();
        // Sort to ensure order
        $installments = $installments->sortBy('installment_number')->values();

        // 1st Installment -> Jan Invoice (Ref 2025-01)
        $this->assertEquals('2025-01', $installments[0]->invoice->reference_month);
        // 2nd Installment -> Feb Invoice (Ref 2025-02)
        $this->assertEquals('2025-02', $installments[1]->invoice->reference_month);
        // 3rd Installment -> Mar Invoice (Ref 2025-03)
        $this->assertEquals('2025-03', $installments[2]->invoice->reference_month);
    }

    public function test_installments_cross_years()
    {
        $card = Card::factory()->create([
            'closing_day' => 10,
            'due_day' => 20,
        ]);

        // Purchase Nov 2025 -> 3 installments
        // 1: Nov (2025-11)
        // 2: Dec (2025-12)
        // 3: Jan (2026-01)
        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 300.00,
            'date' => '2025-11-05',
            'total_installments' => 3
        ]);

        $service = app(InstallmentService::class);
        $service->createInstallments($transaction);

        $installments = $transaction->cardInstallments()->with('invoice')->get();
        $installments = $installments->sortBy('installment_number')->values();

        $this->assertEquals('2025-11', $installments[0]->invoice->reference_month);
        $this->assertEquals('2025-12', $installments[1]->invoice->reference_month);
        $this->assertEquals('2026-01', $installments[2]->invoice->reference_month);
    }

    public function test_purchase_after_closing_day_shifts_installments()
    {
        $card = Card::factory()->create([
            'closing_day' => 10,
            'due_day' => 20,
        ]);

        // Purchase Jan 15 (After Closing Jan 10)
        // Should enter Feb Invoice (2025-02) as 1st installment
        // 2nd -> Mar (2025-03)
        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 200.00,
            'date' => '2025-01-15',
            'total_installments' => 2
        ]);

        $service = app(InstallmentService::class);
        $service->createInstallments($transaction);

        $installments = $transaction->cardInstallments()->with('invoice')->get();
        $installments = $installments->sortBy('installment_number')->values();

        // 2nd Installment should be Mar
        $this->assertEquals('2025-03', $installments[1]->invoice->reference_month);
    }

    public function test_installments_do_not_skip_months_with_large_gap()
    {
        // Scenario from user: Closing 30, Due 11.
        // Purchase Jan (ref Jan?).
        // Expect Jan, Feb, Mar.

        $card = Card::factory()->create([
            'closing_day' => 30,
            'due_day' => 11,
        ]);

        // Purchase Dec 15 -> Competence Dec (Closing Dec 30) -> Due Jan 11 (Ref 2025-01)
        $transaction = Transaction::factory()->create([
            'card_id' => $card->id,
            'value' => 300.00,
            'date' => '2024-12-15', // Dec 15, 2024
            'total_installments' => 3
        ]);

        $service = app(InstallmentService::class);
        $service->createInstallments($transaction);

        $installments = $transaction->cardInstallments()->with('invoice')->get();
        $installments = $installments->sortBy('installment_number')->values();

        // 1: Dec 15 -> Due Jan 11 -> Ref 2025-01
        $this->assertEquals('2025-01', $installments[0]->invoice->reference_month);

        // 2: Should be Due Feb 11 -> Ref 2025-02
        $this->assertEquals('2025-02', $installments[1]->invoice->reference_month);

        // 3: Should be Due Mar 11 -> Ref 2025-03
        $this->assertEquals('2025-03', $installments[2]->invoice->reference_month);
    }
}
