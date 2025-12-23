<?php

namespace App\Jobs;

use App\Models\CardInvoice;
use App\Models\Notification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckInvoicesDueSoon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    /**
     * Execute the job.
     * Check for invoices due in the next 3 days and send notifications.
     */
    public function handle(NotificationService $notificationService): void
    {
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        // Find all invoices that are due in exactly 3 days
        $invoices = CardInvoice::with('card')
            ->where('status', '!=', 'paga')
            ->whereDate('due_date', $targetDate)
            ->get();

        foreach ($invoices as $invoice) {
            $userId = $invoice->card->user_id ?? null;

            if (!$userId) {
                continue;
            }

            $cardName = $invoice->card->name ?? 'Cartão';
            $amount = $invoice->amount ?? 0;

            // Check if we already sent this notification for this invoice
            $alreadySent = Notification::where('user_id', $userId)
                ->where('type', Notification::TYPE_INVOICE_DUE_SOON)
                ->whereJsonContains('data->invoice_id', $invoice->id)
                ->exists();

            if (!$alreadySent) {
                $notificationService->notifyInvoiceDueSoon($userId, $cardName, 3, $amount);

                // Store invoice_id in data for dedup
                Notification::where('user_id', $userId)
                    ->where('type', Notification::TYPE_INVOICE_DUE_SOON)
                    ->latest()
                    ->first()
                        ?->update(['data' => ['invoice_id' => $invoice->id, 'card' => $cardName, 'amount' => $amount, 'days' => 3]]);

                Log::info("Notificação de fatura enviada para usuário {$userId}: {$cardName}");
            }
        }
    }
}
