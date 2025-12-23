<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Budget;
use App\Models\GeneralBudget;
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
        if ($transaction->type !== 'despesa') {
            return;
        }

        if ($transaction->category_id) {
            $this->checkBudgetThreshold($transaction);
        }

        // Also check general budgets
        $this->checkGeneralBudgetThreshold($transaction);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        // Only check if value or category changed and it's an expense
        if ($transaction->type !== 'despesa') {
            return;
        }

        if ($transaction->wasChanged(['value', 'category_id']) && $transaction->category_id) {
            $this->checkBudgetThreshold($transaction);
        }

        if ($transaction->wasChanged(['value'])) {
            $this->checkGeneralBudgetThreshold($transaction);
        }
    }

    /**
     * Check category budget threshold and send notifications.
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
     * Check general budget threshold and send notifications.
     */
    private function checkGeneralBudgetThreshold(Transaction $transaction): void
    {
        $userId = $transaction->user_id;
        $date = Carbon::parse($transaction->date);
        $month = $date->month;
        $year = $date->year;

        // Check monthly general budget
        $monthlyBudget = GeneralBudget::where('user_id', $userId)
            ->where('type', 'mensal')
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_active', true)
            ->first();

        if ($monthlyBudget) {
            $this->checkGeneralThreshold($monthlyBudget, $date);
        }

        // Check annual general budget
        $annualBudget = GeneralBudget::where('user_id', $userId)
            ->where('type', 'anual')
            ->where('year', $year)
            ->where('is_active', true)
            ->first();

        if ($annualBudget) {
            $this->checkGeneralThreshold($annualBudget, $date);
        }
    }

    private function checkGeneralThreshold(GeneralBudget $budget, Carbon $date): void
    {
        $percentage = $budget->percentage;
        $periodName = $budget->type === 'mensal'
            ? $date->translatedFormat('F')
            : $budget->year;

        // 100% threshold
        if ($percentage >= 100 && !$budget->alert_100_sent) {
            $this->notificationService->create(
                $budget->user_id,
                Notification::TYPE_BUDGET_EXCEEDED,
                'Orçamento Geral estourado',
                "Seu orçamento geral de {$periodName} foi excedido.",
                ['general_budget_id' => $budget->id, 'percentage' => round($percentage), 'spent' => $budget->spent, 'limit' => $budget->amount]
            );
            $budget->update(['alert_100_sent' => true]);
        }
        // 80% threshold
        elseif ($percentage >= 80 && !$budget->alert_80_sent) {
            $this->notificationService->create(
                $budget->user_id,
                Notification::TYPE_BUDGET_WARNING,
                'Orçamento Geral em risco',
                "Atenção: você já utilizou " . round($percentage) . "% do orçamento geral de {$periodName}.",
                ['general_budget_id' => $budget->id, 'percentage' => round($percentage), 'spent' => $budget->spent, 'limit' => $budget->amount]
            );
            $budget->update(['alert_80_sent' => true]);
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
