<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReportController;
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

    // Transactions
    Route::apiResource('transactions', TransactionController::class);
    Route::post('transactions/{transaction}/duplicate', [TransactionController::class, 'duplicate']);
    Route::post('transactions/{transaction}/refund', [TransactionController::class, 'refund']);
    Route::post('transactions/{transaction}/partial-refund', [TransactionController::class, 'partialRefund']);
    Route::post('transactions/{transaction}/anticipate', [TransactionController::class, 'anticipate']);
    Route::post('transactions/{transaction}/refund-by-value', [TransactionController::class, 'refundByValue']);
    Route::patch('transactions/{transaction}/notes', [TransactionController::class, 'updateNotes']);

    // Categories
    Route::apiResource('categories', CategoryController::class)->except(['show']);

    // Reports / Dashboard
    Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('reports/period', [ReportController::class, 'period']);

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
});
