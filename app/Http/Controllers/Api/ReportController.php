<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Card;
use App\Models\Transaction;
use App\Models\CardInvoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Dados do dashboard principal
     */
    public function dashboard(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Saldo total das contas
        $totalBalance = Account::where('user_id', $userId)
            ->where('is_active', true)
            ->get()
            ->sum('current_balance');

        // Receitas do mês
        $monthIncome = Transaction::where('user_id', $userId)
            ->where('type', 'receita')
            ->where('affects_balance', true)
            ->where('status', 'confirmada')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('value');

        // Despesas do mês
        $monthExpenses = Transaction::where('user_id', $userId)
            ->where('type', 'despesa')
            ->where('affects_balance', true)
            ->where('status', 'confirmada')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('value');

        // Faturas em aberto
        $openInvoices = CardInvoice::whereHas('card', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->whereIn('status', ['aberta', 'fechada', 'parcialmente_paga'])
            ->sum(DB::raw('total_value - paid_value'));

        // Gastos por categoria (últimos 30 dias)
        $expensesByCategory = Transaction::where('user_id', $userId)
            ->where('type', 'despesa')
            ->where('affects_balance', true)
            ->where('status', 'confirmada')
            ->where('date', '>=', $now->copy()->subDays(30))
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($transactions) {
                $category = $transactions->first()->category;
                return [
                    'category' => $category ? $category->name : 'Sem categoria',
                    'color' => $category ? $category->color : '#6b7280',
                    'total' => $transactions->sum('value'),
                ];
            })
            ->values();

        // Transações recentes
        $recentTransactions = Transaction::where('user_id', $userId)
            ->where('affects_balance', true)
            ->with(['account', 'card', 'category'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // Próximas contas (faturas próximas)
        $upcomingBills = CardInvoice::whereHas('card', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->whereIn('status', ['aberta', 'fechada'])
            ->where('due_date', '>=', $now)
            ->orderBy('due_date')
            ->take(5)
            ->with('card')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'description' => "Fatura {$invoice->card->name}",
                    'due_date' => $invoice->due_date,
                    'value' => $invoice->total_value - $invoice->paid_value,
                ];
            });

        return response()->json([
            'data' => [
                'total_balance' => round($totalBalance, 2),
                'month_income' => round($monthIncome, 2),
                'month_expenses' => round($monthExpenses, 2),
                'open_invoices' => round($openInvoices, 2),
                'balance_trend' => 0, // TODO: calcular tendência
                'income_trend' => 0,
                'expenses_trend' => 0,
                'expenses_by_category' => $expensesByCategory,
                'recent_transactions' => $recentTransactions,
                'upcoming_bills' => $upcomingBills,
            ],
        ]);
    }

    /**
     * Relatório de um período específico
     */
    public function period(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $userId = $request->user()->id;
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $transactions = Transaction::where('user_id', $userId)
            ->where('affects_balance', true)
            ->where('status', 'confirmada')
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->get();

        $income = $transactions->where('type', 'receita')->sum('value');
        $expenses = $transactions->where('type', 'despesa')->sum('value');

        $byCategory = $transactions
            ->where('type', 'despesa')
            ->groupBy('category_id')
            ->map(function ($items) {
                $category = $items->first()->category;
                return [
                    'category' => $category ? $category->name : 'Sem categoria',
                    'total' => $items->sum('value'),
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return response()->json([
            'data' => [
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'summary' => [
                    'income' => round($income, 2),
                    'expenses' => round($expenses, 2),
                    'balance' => round($income - $expenses, 2),
                ],
                'by_category' => $byCategory,
                'transaction_count' => $transactions->count(),
            ],
        ]);
    }
}
