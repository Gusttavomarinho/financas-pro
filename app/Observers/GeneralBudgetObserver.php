<?php

namespace App\Observers;

use App\Models\GeneralBudget;
use App\Models\Transaction;
use App\Services\NotificationService;

class GeneralBudgetObserver
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Check general budget thresholds when a transaction is created.
     */
    public function checkBudgets(Transaction $transaction): void
    {
        if ($transaction->type !== 'despesa') {
            return;
        }

        $userId = $transaction->user_id;
        $transactionDate = $transaction->date;
        $month = (int) date('m', strtotime($transactionDate));
        $year = (int) date('Y', strtotime($transactionDate));

        // Check monthly budget
        $monthlyBudget = GeneralBudget::where('user_id', $userId)
            ->where('type', 'mensal')
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_active', true)
            ->first();

        if ($monthlyBudget) {
            $this->checkThreshold($monthlyBudget);
        }

        // Check annual budget
        $annualBudget = GeneralBudget::where('user_id', $userId)
            ->where('type', 'anual')
            ->where('year', $year)
            ->where('is_active', true)
            ->first();

        if ($annualBudget) {
            $this->checkThreshold($annualBudget);
        }
    }

    private function checkThreshold(GeneralBudget $budget): void
    {
        $percentage = $budget->percentage;

        // 100% threshold
        if ($percentage >= 100 && !$budget->alert_100_sent) {
            $this->notificationService->budgetExceeded(
                $budget->user_id,
                $budget->name,
                $budget->amount,
                $budget->spent
            );
            $budget->update(['alert_100_sent' => true]);
        }
        // 80% threshold
        elseif ($percentage >= 80 && !$budget->alert_80_sent) {
            $this->notificationService->budgetWarning(
                $budget->user_id,
                $budget->name,
                $budget->amount,
                $budget->spent
            );
            $budget->update(['alert_80_sent' => true]);
        }
    }
}
