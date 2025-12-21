<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\AuditLog;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Lista todos os cartões do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $cards = Card::where('user_id', $request->user()->id)
            ->with('primaryAccount')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $cards,
        ]);
    }

    /**
     * Cria um novo cartão
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_id' => ['required', 'exists:accounts,id'],
            'bank' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'in:visa,mastercard,elo,amex,diners,hipercard,discover'],
            'holder_name' => ['required', 'string', 'max:255'],
            'last_4_digits' => ['required', 'string', 'size:4'],
            'valid_thru' => ['required', 'string', 'regex:/^\d{2}\/\d{4}$/'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'in:debito,credito,hibrido'],
            'closing_day' => ['required', 'integer', 'min:1', 'max:31'],
            'due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'name.required' => 'O nome do cartão é obrigatório.',
            'account_id.required' => 'Selecione uma conta para vincular ao cartão.',
            'account_id.exists' => 'A conta selecionada não existe.',
            'bank.required' => 'O banco é obrigatório.',
            'brand.required' => 'A bandeira é obrigatória.',
            'holder_name.required' => 'O nome do titular é obrigatório.',
            'last_4_digits.required' => 'Os últimos 4 dígitos são obrigatórios.',
            'last_4_digits.size' => 'Informe exatamente 4 dígitos.',
            'valid_thru.required' => 'A validade é obrigatória.',
            'valid_thru.regex' => 'A validade deve estar no formato MM/AAAA.',
            'credit_limit.required' => 'O limite de crédito é obrigatório.',
            'credit_limit.min' => 'O limite de crédito deve ser maior ou igual a zero.',
            'type.required' => 'O tipo de cartão é obrigatório.',
            'closing_day.required' => 'O dia de fechamento é obrigatório.',
            'closing_day.min' => 'O dia de fechamento deve ser entre 1 e 31.',
            'closing_day.max' => 'O dia de fechamento deve ser entre 1 e 31.',
            'due_day.required' => 'O dia de vencimento é obrigatório.',
            'due_day.min' => 'O dia de vencimento deve ser entre 1 e 31.',
            'due_day.max' => 'O dia de vencimento deve ser entre 1 e 31.',
        ]);

        $card = Card::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        AuditLog::log('create', 'Card', $card->id);

        return response()->json([
            'message' => 'Cartão criado com sucesso!',
            'data' => $card,
        ], 201);
    }

    /**
     * Exibe um cartão específico
     */
    public function show(Request $request, Card $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        return response()->json([
            'data' => $card->load('primaryAccount'),
        ]);
    }

    /**
     * Atualiza um cartão
     */
    public function update(Request $request, Card $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'bank' => ['sometimes', 'string', 'max:255'],
            'brand' => ['sometimes', 'in:visa,mastercard,elo,amex,diners,hipercard,discover'],
            'holder_name' => ['sometimes', 'string', 'max:255'],
            'last_4_digits' => ['sometimes', 'string', 'size:4'],
            'valid_thru' => ['sometimes', 'string', 'regex:/^\d{2}\/\d{4}$/'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'type' => ['sometimes', 'in:debito,credito,hibrido'],
            'closing_day' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'due_day' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'status' => ['sometimes', 'in:ativo,bloqueado,cancelado,expirado'],
            'primary_account_id' => ['nullable', 'exists:accounts,id'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $oldData = $card->toArray();
        $card->update($validated);

        // Se mudou dia de fechamento ou vencimento, reprocessar faturas abertas
        if (
            (isset($validated['closing_day']) && $validated['closing_day'] != $oldData['closing_day']) ||
            (isset($validated['due_day']) && $validated['due_day'] != $oldData['due_day'])
        ) {
            // Injeção de dependência manual (poderia injetar no método, mas para ser rápido uso app())
            app(\App\Services\InvoiceService::class)->reprocessOpenInvoices($card);
        }

        AuditLog::log('update', 'Card', $card->id, [
            'old' => $oldData,
            'new' => $validated,
        ]);

        return response()->json([
            'message' => 'Cartão atualizado com sucesso!',
            'data' => $card->fresh(),
        ]);
    }

    /**
     * Remove um cartão (soft delete)
     */
    public function destroy(Request $request, Card $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $card->delete();

        AuditLog::log('delete', 'Card', $card->id);

        return response()->json([
            'message' => 'Cartão removido com sucesso!',
        ]);
    }

    /**
     * Retorna a fatura atual do cartão
     */
    public function currentInvoice(Request $request, Card $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        try {
            $invoice = $this->invoiceService->getCurrentInvoice($card);

            // Carregar items via accessor (já trata soft deletes)
            $invoiceData = $invoice->toArray();
            $invoiceData['items'] = $invoice->items;

            return response()->json([
                'data' => $invoiceData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao carregar fatura: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Histórico de faturas do cartão
     */
    public function invoices(Request $request, Card $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $query = $card->invoices();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtro para "futuras" ou "abertas"
        if ($request->boolean('upcoming')) {
            $query->whereIn('status', ['aberta', 'parcialmente_paga'])
                ->where('due_date', '>=', now()->startOfDay());
        }

        $sortDir = $request->input('sort_dir', 'desc');

        $invoices = $query->orderBy('due_date', $sortDir) // Melhor ordenar por vencimento
            ->take(12)
            ->get()
            ->each(function ($invoice) {
                $invoice->append('items');
            });

        return response()->json([
            'data' => $invoices,
        ]);
    }

    /**
     * Registra pagamento de fatura
     */
    public function payInvoice(Request $request, Card $card): JsonResponse
    {
        if ($card->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['required', 'exists:accounts,id'],
        ]);

        try {
            $invoice = $this->invoiceService->getCurrentInvoice($card);
            $transaction = $this->invoiceService->payInvoice($invoice, $validated['amount'], $validated['account_id']);

            AuditLog::log('pay_invoice', 'CardInvoice', $invoice->id, [
                'amount' => $validated['amount'],
                'account_id' => $validated['account_id'],
            ]);

            return response()->json([
                'message' => 'Pagamento registrado com sucesso!',
                'data' => [
                    'invoice' => $invoice->fresh(),
                    'transaction' => $transaction,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar pagamento: ' . $e->getMessage(),
            ], 500);
        }
    }
}
