<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Card;
use App\Models\CardInstallment;
use App\Models\CardInvoice;
use App\Models\Category;
use App\Models\Goal;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BackupExportService
{
    public const BACKUP_VERSION = '1.0';

    /**
     * Generate complete backup for authenticated user.
     */
    public function generateBackup(int $userId): array
    {
        $user = User::find($userId);

        return [
            'meta' => $this->getMeta(),
            'user' => $this->exportUser($user),
            'accounts' => $this->exportAccounts($userId),
            'cards' => $this->exportCards($userId),
            'categories' => $this->exportCategories($userId),
            'transactions' => $this->exportTransactions($userId),
            'recurring_transactions' => $this->exportRecurringTransactions($userId),
            'budgets' => $this->exportBudgets($userId),
            'goals' => $this->exportGoals($userId),
            'invoices' => $this->exportInvoices($userId),
            'installments' => $this->exportInstallments($userId),
        ];
    }

    private function getMeta(): array
    {
        return [
            'system' => 'FinancasPro',
            'backup_version' => self::BACKUP_VERSION,
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    private function exportUser(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    private function exportAccounts(int $userId): array
    {
        return Account::where('user_id', $userId)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'bank' => $a->bank,
                'account_number' => $a->account_number,
                'initial_balance' => $a->initial_balance,
                'current_balance' => $a->current_balance,
                'color' => $a->color,
                'is_active' => $a->is_active,
                'created_at' => $a->created_at?->toIso8601String(),
            ])
            ->toArray();
    }

    private function exportCards(int $userId): array
    {
        return Card::where('user_id', $userId)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'account_id' => $c->account_id,
                'name' => $c->name,
                'brand' => $c->brand,
                'last_4_digits' => $c->last_4_digits,
                'credit_limit' => $c->credit_limit,
                'closing_day' => $c->closing_day,
                'due_day' => $c->due_day,
                'color' => $c->color,
                'is_active' => $c->is_active,
                'created_at' => $c->created_at?->toIso8601String(),
            ])
            ->toArray();
    }

    private function exportCategories(int $userId): array
    {
        return Category::where('user_id', $userId)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type,
                'icon' => $c->icon,
                'color' => $c->color,
                'created_at' => $c->created_at?->toIso8601String(),
            ])
            ->toArray();
    }

    private function exportTransactions(int $userId): array
    {
        return Transaction::where('user_id', $userId)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'description' => $t->description,
                'value' => $t->value,
                'type' => $t->type,
                'date' => $t->date,
                'category_id' => $t->category_id,
                'account_id' => $t->account_id,
                'card_id' => $t->card_id,
                'from_account_id' => $t->from_account_id,
                'payment_method' => $t->payment_method,
                'status' => $t->status,
                'notes' => $t->notes,
                'total_installments' => $t->total_installments,
                'current_installment' => $t->current_installment,
                'recurring_transaction_id' => $t->recurring_transaction_id,
                'created_at' => $t->created_at?->toIso8601String(),
            ])
            ->toArray();
    }

    private function exportRecurringTransactions(int $userId): array
    {
        return RecurringTransaction::where('user_id', $userId)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'description' => $r->description,
                'value' => $r->value,
                'type' => $r->type,
                'frequency' => $r->frequency,
                'start_date' => $r->start_date,
                'end_date' => $r->end_date,
                'next_occurrence' => $r->next_occurrence,
                'category_id' => $r->category_id,
                'account_id' => $r->account_id,
                'card_id' => $r->card_id,
                'payment_method' => $r->payment_method,
                'status' => $r->status,
                'notes' => $r->notes,
                'last_generated_at' => $r->last_generated_at,
                'created_at' => $r->created_at?->toIso8601String(),
            ])
            ->toArray();
    }

    private function exportBudgets(int $userId): array
    {
        return Budget::where('user_id', $userId)
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'category_id' => $b->category_id,
                'limit_value' => $b->limit_value,
                'reference_month' => $b->reference_month,
                'period_type' => $b->period_type,
                'created_at' => $b->created_at?->toIso8601String(),
            ])
            ->toArray();
    }

    private function exportGoals(int $userId): array
    {
        return Goal::where('user_id', $userId)
            ->get()
            ->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'description' => $g->description,
                'icon' => $g->icon,
                'color' => $g->color,
                'target_value' => $g->target_value,
                'current_value' => $g->current_value,
                'status' => $g->status,
                'target_date' => $g->target_date,
                'created_at' => $g->created_at?->toIso8601String(),
            ])
            ->toArray();
    }

    private function exportInvoices(int $userId): array
    {
        $cardIds = Card::where('user_id', $userId)->pluck('id');

        return CardInvoice::whereIn('card_id', $cardIds)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'card_id' => $i->card_id,
                'reference_month' => $i->reference_month,
                'reference_year' => $i->reference_year,
                'opening_date' => $i->opening_date,
                'closing_date' => $i->closing_date,
                'due_date' => $i->due_date,
                'amount' => $i->amount,
                'status' => $i->status,
                'paid_at' => $i->paid_at,
                'created_at' => $i->created_at?->toIso8601String(),
            ])
            ->toArray();
    }

    private function exportInstallments(int $userId): array
    {
        $cardIds = Card::where('user_id', $userId)->pluck('id');
        $invoiceIds = CardInvoice::whereIn('card_id', $cardIds)->pluck('id');

        return CardInstallment::whereIn('card_invoice_id', $invoiceIds)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'transaction_id' => $i->transaction_id,
                'card_invoice_id' => $i->card_invoice_id,
                'installment_number' => $i->installment_number,
                'total_installments' => $i->total_installments,
                'value' => $i->value,
                'due_date' => $i->due_date,
                'status' => $i->status,
                'created_at' => $i->created_at?->toIso8601String(),
            ])
            ->toArray();
    }
}
