<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Notification;
use App\Services\NotificationService;
use Carbon\Carbon;

class BudgetNotificationObserver
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * Handle the Transaction "created" event.
     * Check if budget thresholds are exceeded.
     */
    public function created(Transaction $transaction): void
    {
        // Only check for expenses
        if ($transaction->type !== 'despesa' || !$transaction->category_id) {
            return;
        }

        $this->checkBudgetThreshold($transaction);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        // Only check if value or category changed and it's an expense
        if ($transaction->type !== 'despesa' || !$transaction->category_id) {
            return;
        }

        if ($transaction->wasChanged(['value', 'category_id'])) {
            $this->checkBudgetThreshold($transaction);
        }
    }

    /**
     * Check budget threshold and send notifications.
     */
    private function checkBudgetThreshold(Transaction $transaction): void
    {
        $userId = $transaction->user_id;
        $categoryId = $transaction->category_id;
        $date = Carbon::parse($transaction->date);
        $month = $date->month;
        $year = $date->year;

        // Find budget for this category and month
        $budget = Budget::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$budget) {
            return; // No budget set for this category/month
        }

        // Calculate current spending in this category for the month
        $totalSpent = Transaction::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->where('type', 'despesa')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('value');

        $percentage = ($budget->amount > 0) ? ($totalSpent / $budget->amount) * 100 : 0;
        $categoryName = $transaction->category?->name ?? 'Categoria';
        $monthName = $date->translatedFormat('F');

        // Check thresholds (only notify once per threshold per month)
        if ($percentage >= 100) {
            $this->notifyIfNotAlreadySent(
                $userId,
                Notification::TYPE_BUDGET_EXCEEDED,
                $categoryId,
                $year,
                $month,
                'Orçamento estourado',
                "Orçamento de {$categoryName} estourado em {$monthName}.",
                ['category_id' => $categoryId, 'percentage' => round($percentage), 'spent' => $totalSpent, 'limit' => $budget->amount]
            );
        } elseif ($percentage >= 80) {
            $this->notifyIfNotAlreadySent(
                $userId,
                Notification::TYPE_BUDGET_WARNING,
                $categoryId,
                $year,
                $month,
                'Orçamento em risco',
                "Atenção: você já utilizou " . round($percentage) . "% do orçamento de {$categoryName} em {$monthName}.",
                ['category_id' => $categoryId, 'percentage' => round($percentage), 'spent' => $totalSpent, 'limit' => $budget->amount]
            );
        }
    }

    /**
     * Send notification only if not already sent for this threshold/category/month.
     */
    private function notifyIfNotAlreadySent(
        int $userId,
        string $type,
        int $categoryId,
        int $year,
        int $month,
        string $title,
        string $message,
        array $data
    ): void {
        // Check if we already sent this notification this month
        $alreadySent = Notification::where('user_id', $userId)
            ->where('type', $type)
            ->whereJsonContains('data->category_id', $categoryId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->exists();

        if (!$alreadySent) {
            $this->notificationService->create($userId, $type, $title, $message, $data);
        }
    }
}
