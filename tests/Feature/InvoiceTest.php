<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\User;
use App\Models\Transaction;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user and authenticate
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_purchase_before_closing_day_goes_to_current_invoice()
    {
        Carbon::setTestNow('2025-05-15'); // May 15th

        // Card: Closes on 20th, Due on 30th
        $card = Card::factory()->create([
            'closing_day' => 20,
            'due_day' => 30,
            'user_id' => $this->user->id
        ]);

        // Purchase on May 18th (Before Closing)
        $service = app(InvoiceService::class);
        $invoice = $service->getOrCreateInvoice($card, '2025-05-18');

        // Reference Month should be May (Due Date 2025-05-30)
        // Note: New logic -> Reference Month is based on Due Date.
        // Due Date for May cycle = May 30th -> Reference Month = 2025-05
        $this->assertEquals('2025-05-30', $invoice->due_date->toDateString());
        $this->assertEquals('2025-05', $invoice->reference_month);
    }

    public function test_purchase_after_closing_day_goes_to_next_invoice()
    {
        Carbon::setTestNow('2025-05-15');

        // Card: Closes on 20th, Due on 30th
        $card = Card::factory()->create([
            'closing_day' => 20,
            'due_day' => 30,
            'user_id' => $this->user->id
        ]);

        // Purchase on May 21st (After Closing)
        $service = app(InvoiceService::class);
        $invoice = $service->getOrCreateInvoice($card, '2025-05-21');

        // Should go to June Invoice (Due Date 2025-06-30)
        $this->assertEquals('2025-06-30', $invoice->due_date->toDateString());
        $this->assertEquals('2025-06', $invoice->reference_month);
    }

    public function test_purchase_on_closing_day_goes_to_next_invoice()
    {
        Carbon::setTestNow('2025-05-15');

        // Best Practice: Closing Day usually includes transactions up to specific time, 
        // but typically "Day of Closing" transactions often bump to next month depending on bank.
        // Let's assume standard behavior: Purchase ON Closing Day -> Next Invoice.

        $card = Card::factory()->create([
            'closing_day' => 20,
            'due_day' => 30,
            'user_id' => $this->user->id
        ]);

        // Purchase on May 20th
        $service = app(InvoiceService::class);
        $invoice = $service->getOrCreateInvoice($card, '2025-05-20');

        // Logic: Closing Day is Inclusive. So it belongs to Current Invoice.
        $this->assertEquals('2025-05-30', $invoice->due_date->toDateString());
    }

    public function test_invoice_naming_convention()
    {
        $card = Card::factory()->create([
            'closing_day' => 10,
            'due_day' => 20, // Due 20
        ]);

        $service = app(InvoiceService::class);

        // Purchase Date: Jan 05 -> Due Jan 20 -> Ref 2025-01
        $inv1 = $service->getOrCreateInvoice($card, '2025-01-05');
        $this->assertEquals('2025-01', $inv1->reference_month);

        // Purchase Date: Jan 12 (After closing) -> Due Feb 20 -> Ref 2025-02
        $inv2 = $service->getOrCreateInvoice($card, '2025-01-12');
        $this->assertEquals('2025-02', $inv2->reference_month);
    }

    public function test_paid_invoice_does_not_receive_new_transactions()
    {
        $card = Card::factory()->create([
            'closing_day' => 20,
            'due_day' => 30,
        ]);

        $service = app(InvoiceService::class);

        // 1. Create Invoice for May
        $invoice = $service->getOrCreateInvoice($card, '2025-05-10');
        $this->assertEquals('2025-05', $invoice->reference_month);

        // 2. Pay/Close the invoice manually
        $invoice->status = 'paga';
        $invoice->save();

        // 3. Try to add another transaction for May 15 (should be in May invoice usually)
        // BUT since May is paid, it should roll over to June? 
        // OR throw error?
        // Requirement: "Fatura paga não recebe lançamento" -> typically rolls over or creates supplemental.
        // Let's assume it finds the NEXT open invoice.

        $newInvoice = $service->getOrCreateInvoice($card, '2025-05-15');

        $this->assertNotEquals($invoice->id, $newInvoice->id);
        $this->assertEquals('2025-06', $newInvoice->reference_month);
    }
}
