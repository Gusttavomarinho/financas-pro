<?php

namespace App\Services;

use App\Models\Notification;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Create a new notification.
     */
    public function create(int $userId, string $type, string $title, string $message, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->unread()->count();
    }

    /**
     * Create budget warning notification.
     */
    public function notifyBudgetWarning(int $userId, string $categoryName, int $percentage, string $month): Notification
    {
        return $this->create(
            $userId,
            Notification::TYPE_BUDGET_WARNING,
            'Orçamento em risco',
            "Atenção: você já utilizou {$percentage}% do orçamento de {$categoryName} em {$month}.",
            ['category' => $categoryName, 'percentage' => $percentage, 'month' => $month]
        );
    }

    /**
     * Create budget exceeded notification.
     */
    public function notifyBudgetExceeded(int $userId, string $categoryName, string $month): Notification
    {
        return $this->create(
            $userId,
            Notification::TYPE_BUDGET_EXCEEDED,
            'Orçamento excedido',
            "Orçamento de {$categoryName} excedido em {$month}.",
            ['category' => $categoryName, 'month' => $month]
        );
    }

    /**
     * Create recurring generated notification.
     */
    public function notifyRecurringGenerated(int $userId, string $description, float $value, ?string $date = null): Notification
    {
        $formattedValue = number_format($value, 2, ',', '.');
        $dateStr = $date ? " para " . Carbon::parse($date)->format('d/m/Y') : "";

        return $this->create(
            $userId,
            Notification::TYPE_RECURRING_GENERATED,
            'Recorrência gerada',
            "Recorrência '{$description}' gerada{$dateStr} (R$ {$formattedValue}).",
            ['description' => $description, 'value' => $value, 'date' => $date]
        );
    }

    /**
     * Create recurring failed notification.
     */
    public function notifyRecurringFailed(int $userId, string $description, string $reason = ''): Notification
    {
        $message = "Falha ao gerar recorrência '{$description}'.";
        if ($reason) {
            $message .= " {$reason}";
        }

        return $this->create(
            $userId,
            Notification::TYPE_RECURRING_FAILED,
            'Falha em recorrência',
            $message,
            ['description' => $description, 'reason' => $reason]
        );
    }

    /**
     * Create import completed notification.
     */
    public function notifyImportCompleted(int $userId, int $importedCount, int $skippedCount = 0): Notification
    {
        $message = "Importação concluída: {$importedCount} lançamentos adicionados.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} duplicados ignorados.";
        }

        return $this->create(
            $userId,
            Notification::TYPE_IMPORT_COMPLETED,
            'Importação concluída',
            $message,
            ['imported' => $importedCount, 'skipped' => $skippedCount]
        );
    }

    /**
     * Create invoice due soon notification.
     */
    public function notifyInvoiceDueSoon(int $userId, string $cardName, int $daysUntilDue, float $amount): Notification
    {
        $formattedAmount = number_format($amount, 2, ',', '.');
        $dayText = $daysUntilDue === 1 ? 'dia' : 'dias';

        return $this->create(
            $userId,
            Notification::TYPE_INVOICE_DUE_SOON,
            'Fatura próxima do vencimento',
            "Sua fatura {$cardName} vence em {$daysUntilDue} {$dayText} (R$ {$formattedAmount}).",
            ['card' => $cardName, 'days' => $daysUntilDue, 'amount' => $amount]
        );
    }

    /**
     * Create invoice closed notification.
     */
    public function notifyInvoiceClosed(int $userId, string $cardName, float $amount): Notification
    {
        $formattedAmount = number_format($amount, 2, ',', '.');

        return $this->create(
            $userId,
            Notification::TYPE_INVOICE_CLOSED,
            'Fatura fechada',
            "Fatura {$cardName} fechou em R$ {$formattedAmount}.",
            ['card' => $cardName, 'amount' => $amount]
        );
    }
}
