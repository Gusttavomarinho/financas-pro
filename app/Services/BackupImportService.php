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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackupImportService
{
    private array $idMaps = [];

    /**
     * Validate backup structure and return preview.
     */
    public function validateAndPreview(array $backup): array
    {
        $errors = [];

        // Check meta
        if (!isset($backup['meta'])) {
            $errors[] = 'Meta information missing';
        } else {
            if (!isset($backup['meta']['system']) || $backup['meta']['system'] !== 'FinancasPro') {
                $errors[] = 'Invalid backup source';
            }
            if (!isset($backup['meta']['backup_version'])) {
                $errors[] = 'Backup version missing';
            }
        }

        // Check required sections
        $requiredSections = ['accounts', 'categories', 'transactions'];
        foreach ($requiredSections as $section) {
            if (!isset($backup[$section]) || !is_array($backup[$section])) {
                $errors[] = "Section '{$section}' missing or invalid";
            }
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
                'preview' => null,
            ];
        }

        // Generate preview
        $preview = [
            'backup_version' => $backup['meta']['backup_version'] ?? 'unknown',
            'generated_at' => $backup['meta']['generated_at'] ?? 'unknown',
            'counts' => [
                'accounts' => count($backup['accounts'] ?? []),
                'cards' => count($backup['cards'] ?? []),
                'categories' => count($backup['categories'] ?? []),
                'transactions' => count($backup['transactions'] ?? []),
                'recurring_transactions' => count($backup['recurring_transactions'] ?? []),
                'budgets' => count($backup['budgets'] ?? []),
                'goals' => count($backup['goals'] ?? []),
                'invoices' => count($backup['invoices'] ?? []),
                'installments' => count($backup['installments'] ?? []),
            ],
        ];

        return [
            'valid' => true,
            'errors' => [],
            'preview' => $preview,
        ];
    }

    /**
     * Restore backup data for user.
     * DESTRUCTIVE: Deletes all existing user data first.
     */
    public function restore(int $userId, array $backup): array
    {
        $validation = $this->validateAndPreview($backup);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Backup inválido: ' . implode(', ', $validation['errors']),
            ];
        }

        $this->idMaps = [
            'accounts' => [],
            'cards' => [],
            'categories' => [],
            'transactions' => [],
            'recurring_transactions' => [],
            'invoices' => [],
        ];

        try {
            DB::transaction(function () use ($userId, $backup) {
                // 1. Delete all existing data (in correct order due to FKs)
                $this->deleteAllUserData($userId);

                // 2. Import in correct order (respect FKs)
                $this->importAccounts($userId, $backup['accounts'] ?? []);
                $this->importCards($userId, $backup['cards'] ?? []);
                $this->importCategories($userId, $backup['categories'] ?? []);
                $this->importRecurringTransactions($userId, $backup['recurring_transactions'] ?? []);
                $this->importTransactions($userId, $backup['transactions'] ?? []);
                $this->importBudgets($userId, $backup['budgets'] ?? []);
                $this->importGoals($userId, $backup['goals'] ?? []);
                $this->importInvoices($backup['invoices'] ?? []);
                $this->importInstallments($backup['installments'] ?? []);
            });

            return [
                'success' => true,
                'message' => 'Backup restaurado com sucesso!',
                'counts' => $validation['preview']['counts'],
            ];
        } catch (\Exception $e) {
            Log::error('Backup restore failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao restaurar backup: ' . $e->getMessage(),
            ];
        }
    }

    private function deleteAllUserData(int $userId): void
    {
        // Delete in reverse FK order
        $cardIds = Card::where('user_id', $userId)->pluck('id');
        $invoiceIds = CardInvoice::whereIn('card_id', $cardIds)->pluck('id');

        CardInstallment::whereIn('card_invoice_id', $invoiceIds)->delete();
        CardInvoice::whereIn('card_id', $cardIds)->delete();
        Transaction::where('user_id', $userId)->delete();
        RecurringTransaction::where('user_id', $userId)->delete();
        Budget::where('user_id', $userId)->delete();
        Goal::where('user_id', $userId)->delete();
        Card::where('user_id', $userId)->delete();
        Category::where('user_id', $userId)->delete();
        Account::where('user_id', $userId)->delete();
    }

    private function importAccounts(int $userId, array $accounts): void
    {
        foreach ($accounts as $data) {
            $oldId = $data['id'];
            unset($data['id'], $data['created_at']);

            $account = Account::create([
                ...$data,
                'user_id' => $userId,
            ]);

            $this->idMaps['accounts'][$oldId] = $account->id;
        }
    }

    private function importCards(int $userId, array $cards): void
    {
        foreach ($cards as $data) {
            $oldId = $data['id'];
            $oldAccountId = $data['account_id'];
            unset($data['id'], $data['created_at']);

            $card = Card::create([
                ...$data,
                'user_id' => $userId,
                'account_id' => $this->idMaps['accounts'][$oldAccountId] ?? null,
            ]);

            $this->idMaps['cards'][$oldId] = $card->id;
        }
    }

    private function importCategories(int $userId, array $categories): void
    {
        foreach ($categories as $data) {
            $oldId = $data['id'];
            unset($data['id'], $data['created_at']);

            $category = Category::create([
                ...$data,
                'user_id' => $userId,
            ]);

            $this->idMaps['categories'][$oldId] = $category->id;
        }
    }

    private function importRecurringTransactions(int $userId, array $recurrings): void
    {
        foreach ($recurrings as $data) {
            $oldId = $data['id'];
            unset($data['id'], $data['created_at']);

            $recurring = RecurringTransaction::create([
                ...$data,
                'user_id' => $userId,
                'account_id' => isset($data['account_id']) ? ($this->idMaps['accounts'][$data['account_id']] ?? null) : null,
                'card_id' => isset($data['card_id']) ? ($this->idMaps['cards'][$data['card_id']] ?? null) : null,
                'category_id' => isset($data['category_id']) ? ($this->idMaps['categories'][$data['category_id']] ?? null) : null,
            ]);

            $this->idMaps['recurring_transactions'][$oldId] = $recurring->id;
        }
    }

    private function importTransactions(int $userId, array $transactions): void
    {
        foreach ($transactions as $data) {
            $oldId = $data['id'];
            $oldRecurringId = $data['recurring_transaction_id'] ?? null;
            unset($data['id'], $data['created_at']);

            // Sanitize payment_method for PostgreSQL compatibility
            // Convert legacy English values to Portuguese
            if (isset($data['payment_method'])) {
                $data['payment_method'] = $this->sanitizePaymentMethod($data['payment_method']);
            }

            $transaction = Transaction::create([
                ...$data,
                'user_id' => $userId,
                'account_id' => isset($data['account_id']) ? ($this->idMaps['accounts'][$data['account_id']] ?? null) : null,
                'from_account_id' => isset($data['from_account_id']) ? ($this->idMaps['accounts'][$data['from_account_id']] ?? null) : null,
                'card_id' => isset($data['card_id']) ? ($this->idMaps['cards'][$data['card_id']] ?? null) : null,
                'category_id' => isset($data['category_id']) ? ($this->idMaps['categories'][$data['category_id']] ?? null) : null,
                'recurring_transaction_id' => $oldRecurringId ? ($this->idMaps['recurring_transactions'][$oldRecurringId] ?? null) : null,
            ]);

            $this->idMaps['transactions'][$oldId] = $transaction->id;
        }
    }

    /**
     * Sanitize payment_method to ensure PostgreSQL check constraint compatibility.
     * Converts legacy English values to Portuguese equivalents.
     */
    private function sanitizePaymentMethod(?string $method): ?string
    {
        if ($method === null) {
            return null;
        }

        // Map of legacy/English values to valid Portuguese values
        $mapping = [
            'debit' => 'debito',
            'credit' => 'credito',
            'transfer' => 'transferencia',
            'cash' => 'dinheiro',
            'bank_slip' => 'boleto',
        ];

        return $mapping[$method] ?? $method;
    }

    private function importBudgets(int $userId, array $budgets): void
    {
        foreach ($budgets as $data) {
            unset($data['id'], $data['created_at']);

            Budget::create([
                ...$data,
                'user_id' => $userId,
                'category_id' => isset($data['category_id']) ? ($this->idMaps['categories'][$data['category_id']] ?? null) : null,
            ]);
        }
    }

    private function importGoals(int $userId, array $goals): void
    {
        foreach ($goals as $data) {
            unset($data['id'], $data['created_at']);

            Goal::create([
                ...$data,
                'user_id' => $userId,
            ]);
        }
    }

    private function importInvoices(array $invoices): void
    {
        foreach ($invoices as $data) {
            $oldId = $data['id'];
            $oldCardId = $data['card_id'];
            unset($data['id'], $data['created_at']);

            $newCardId = $this->idMaps['cards'][$oldCardId] ?? null;
            if (!$newCardId)
                continue;

            $invoice = CardInvoice::create([
                ...$data,
                'card_id' => $newCardId,
            ]);

            $this->idMaps['invoices'][$oldId] = $invoice->id;
        }
    }

    private function importInstallments(array $installments): void
    {
        foreach ($installments as $data) {
            // Support both old (invoice_id) and new (card_invoice_id) backup format
            $oldInvoiceId = $data['card_invoice_id'] ?? $data['invoice_id'] ?? null;
            $oldTransactionId = $data['transaction_id'];
            unset($data['id'], $data['created_at'], $data['invoice_id'], $data['card_invoice_id']);

            $newInvoiceId = $this->idMaps['invoices'][$oldInvoiceId] ?? null;
            $newTransactionId = $this->idMaps['transactions'][$oldTransactionId] ?? null;

            if (!$newInvoiceId)
                continue;

            CardInstallment::create([
                ...$data,
                'card_invoice_id' => $newInvoiceId,
                'transaction_id' => $newTransactionId,
            ]);
        }
    }
}
