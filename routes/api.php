<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransactionAttachmentController;
use App\Http\Controllers\Api\RecurringTransactionController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    // Accounts
    Route::apiResource('accounts', AccountController::class);
    Route::get('accounts/{account}/balance', [AccountController::class, 'balance']);
    Route::get('accounts/{account}/check-delete', [AccountController::class, 'checkDelete']);
    Route::post('accounts/{account}/unarchive', [AccountController::class, 'unarchive']);

    // Cards
    Route::apiResource('cards', CardController::class);
    Route::get('cards/{card}/invoice', [CardController::class, 'currentInvoice']);
    Route::get('cards/{card}/invoices', [CardController::class, 'invoices']);
    Route::post('cards/{card}/pay', [CardController::class, 'payInvoice']);
    Route::post('cards/{card}/unarchive', [CardController::class, 'unarchive']);

    // Transactions
    Route::apiResource('transactions', TransactionController::class);
    Route::post('transactions/{transaction}/duplicate', [TransactionController::class, 'duplicate']);
    Route::post('transactions/{transaction}/refund', [TransactionController::class, 'refund']);
    Route::post('transactions/{transaction}/partial-refund', [TransactionController::class, 'partialRefund']);
    Route::post('transactions/{transaction}/anticipate', [TransactionController::class, 'anticipate']);
    Route::post('transactions/{transaction}/refund-by-value', [TransactionController::class, 'refundByValue']);
    Route::patch('transactions/{transaction}/notes', [TransactionController::class, 'updateNotes']);

    // Attachments
    Route::get('transactions/{transaction}/attachments', [TransactionAttachmentController::class, 'index']);
    Route::post('transactions/{transaction}/attachments', [TransactionAttachmentController::class, 'store']);
    Route::delete('attachments/{attachment}', [TransactionAttachmentController::class, 'destroy']);
    Route::get('attachments/{attachment}/download', [TransactionAttachmentController::class, 'download']);

    // Recurring Transactions
    Route::apiResource('recurring-transactions', App\Http\Controllers\Api\RecurringTransactionController::class);
    Route::post('recurring-transactions/{recurring}/pause', [App\Http\Controllers\Api\RecurringTransactionController::class, 'pause']);
    Route::post('recurring-transactions/{recurring}/resume', [App\Http\Controllers\Api\RecurringTransactionController::class, 'resume']);
    Route::post('recurring-transactions/{recurring}/end', [App\Http\Controllers\Api\RecurringTransactionController::class, 'end']);
    Route::post('recurring-transactions/{recurring}/generate', [App\Http\Controllers\Api\RecurringTransactionController::class, 'generate']);
    Route::get('recurrences/suggestions', [App\Http\Controllers\Api\RecurringTransactionController::class, 'suggestions']);
    Route::post('recurrences/suggestions/create', [App\Http\Controllers\Api\RecurringTransactionController::class, 'createFromSuggestion']);

    // Calendar
    Route::get('calendar', [App\Http\Controllers\Api\CalendarController::class, 'index']);

    // Categories
    Route::apiResource('categories', CategoryController::class)->except(['show']);

    // Budgets (Orçamentos)
    Route::apiResource('budgets', BudgetController::class)->except(['show']);
    Route::get('budgets/summary', [BudgetController::class, 'summary']);

    // Goals (Objetivos)
    Route::apiResource('goals', GoalController::class);
    Route::post('goals/{goal}/deposit', [GoalController::class, 'deposit']);
    Route::post('goals/{goal}/withdraw', [GoalController::class, 'withdraw']);
    Route::post('goals/{goal}/cancel', [GoalController::class, 'cancel']);
    Route::post('goals/{goal}/reactivate', [GoalController::class, 'reactivate']);

    // Reports / Dashboard
    Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('reports/period', [ReportController::class, 'period']);
    Route::get('reports/summary', [ReportController::class, 'summary']);
    Route::get('reports/by-category', [ReportController::class, 'byCategory']);
    Route::get('reports/by-account', [ReportController::class, 'byAccount']);
    Route::get('reports/monthly-evolution', [ReportController::class, 'monthlyEvolution']);
    Route::get('reports/savings-rate', [ReportController::class, 'savingsRate']);
    Route::get('reports/fixed-vs-variable', [ReportController::class, 'fixedVsVariable']);
    Route::get('reports/credit-limit-evolution', [ReportController::class, 'creditLimitEvolution']);
    Route::get('reports/future-commitment', [ReportController::class, 'futureCommitment']);
    Route::get('reports/top-cards', [ReportController::class, 'topCards']);

    // Planning Routes
    Route::get('reports/budget-vs-actual', [ReportController::class, 'budgetVsActual']);
    Route::get('reports/budget-consumption', [ReportController::class, 'budgetConsumption']);
    Route::get('reports/budget-alerts', [ReportController::class, 'budgetAlerts']);
    Route::get('reports/goals-progress', [ReportController::class, 'goalsProgress']);

    // Audit Logs
    Route::get('audit-logs', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\AuditLog::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->entity_type) {
            $query->where('model', $request->entity_type);
        }
        if ($request->entity_id) {
            $query->where('model_id', $request->entity_id);
        }

        return response()->json([
            'data' => $query->take(20)->get(),
        ]);
    });

    // Export Routes (PDF / CSV)
    Route::get('reports/transactions/pdf', [ReportController::class, 'transactionsPdf']);
    Route::get('reports/transactions/csv', [ReportController::class, 'transactionsCsv']);
    Route::get('reports/summary/pdf', [ReportController::class, 'summaryPdf']);

    // Import OFX
    Route::post('import/parse', [ImportController::class, 'parse']);
    Route::post('import/confirm', [ImportController::class, 'confirm']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
