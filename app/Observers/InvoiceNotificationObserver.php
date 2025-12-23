<?php

namespace App\Observers;

use App\Models\CardInvoice;
use App\Models\Notification;
use App\Services\NotificationService;

class InvoiceNotificationObserver
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * Handle the CardInvoice "updated" event.
     * Send notification when invoice is closed.
     */
    public function updated(CardInvoice $invoice): void
    {
        // Check if status changed to 'fechada'
        if ($invoice->wasChanged('status') && $invoice->status === 'fechada') {
            $this->notifyInvoiceClosed($invoice);
        }
    }

    /**
     * Send invoice closed notification.
     */
    private function notifyInvoiceClosed(CardInvoice $invoice): void
    {
        $card = $invoice->card;

        if (!$card) {
            return;
        }

        $userId = $card->user_id;
        $cardName = $card->name;
        $amount = $invoice->amount ?? 0;

        // Check if we already sent this notification for this invoice
        $alreadySent = Notification::where('user_id', $userId)
            ->where('type', Notification::TYPE_INVOICE_CLOSED)
            ->whereJsonContains('data->invoice_id', $invoice->id)
            ->exists();

        if (!$alreadySent) {
            $this->notificationService->notifyInvoiceClosed($userId, $cardName, $amount);

            // Update the notification with invoice_id for dedup
            Notification::where('user_id', $userId)
                ->where('type', Notification::TYPE_INVOICE_CLOSED)
                ->latest()
                ->first()
                    ?->update(['data' => ['invoice_id' => $invoice->id, 'card' => $cardName, 'amount' => $amount]]);
        }
    }
}
