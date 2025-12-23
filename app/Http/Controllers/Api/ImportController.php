<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Card;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\ImportDetectionService;
use App\Services\NotificationService;
use App\Services\OfxParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function __construct(
        private OfxParserService $ofxParser,
        private ImportDetectionService $detectionService,
        private NotificationService $notificationService
    ) {
    }

    public function parse(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['ofx', 'qfx'])) {
            return response()->json([
                'message' => 'Formato inválido. Apenas arquivos OFX e QFX são suportados.',
            ], 422);
        }

        try {
            $parsed = $this->ofxParser->parse($file);

            if (empty($parsed['transactions'])) {
                return response()->json([
                    'message' => 'Nenhuma transação encontrada no arquivo.',
                    'account_info' => $parsed['account_info'],
                    'transactions' => [],
                ], 200);
            }

            $userId = auth()->id();

            $analyzed = $this->detectionService->analyze(
                $parsed['transactions'],
                $userId,
                $parsed['account_info']
            );

            $stats = $this->getStats($analyzed);

            // Get user's categories
            $categories = Category::where('user_id', $userId)
                ->orWhereNull('user_id')
                ->select('id', 'name', 'icon', 'color', 'type')
                ->orderBy('name')
                ->get();

            return response()->json([
                'message' => 'Arquivo processado com sucesso.',
                'account_info' => $parsed['account_info'],
                'transactions' => $analyzed,
                'stats' => $stats,
                'categories' => $categories,
                'payment_methods' => $this->detectionService->getAvailablePaymentMethods(),
                'accounts' => Account::where('user_id', $userId)->select('id', 'name', 'bank')->get(),
                'cards' => Card::where('user_id', $userId)->select('id', 'name', 'brand', 'last_four')->get(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar arquivo: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'default_account_id' => 'required|exists:accounts,id',
            'transactions' => 'required|array|min:1',
            'transactions.*.date' => 'required|date',
            'transactions.*.description' => 'required|string|max:255',
            'transactions.*.amount' => 'required|numeric',
            'transactions.*.type' => 'required|in:receita,despesa,transferencia',
            'transactions.*.category_id' => 'nullable|exists:categories,id',
            'transactions.*.account_id' => 'nullable|exists:accounts,id',
            'transactions.*.card_id' => 'nullable|exists:cards,id',
            'transactions.*.payment_method' => 'nullable|string|max:50',
            'transactions.*.hash' => 'required|string|size:32',
            'transactions.*.hash_version' => 'required|integer',
            'transactions.*.status' => 'nullable|in:confirmada,pendente',
        ]);

        $userId = auth()->id();
        $defaultAccountId = $request->default_account_id;
        $created = [];
        $skipped = [];

        DB::beginTransaction();

        try {
            foreach ($request->transactions as $txData) {
                $existingHash = Transaction::where('user_id', $userId)
                    ->where('import_hash', $txData['hash'])
                    ->exists();

                if ($existingHash) {
                    $skipped[] = $txData['description'];
                    continue;
                }

                $type = $txData['type'];
                if ($type === 'transferencia') {
                    $type = $txData['amount'] >= 0 ? 'receita' : 'despesa';
                }

                // Use item's account_id if set, otherwise use default
                $accountId = $txData['account_id'] ?? $defaultAccountId;

                $transaction = Transaction::create([
                    'user_id' => $userId,
                    'account_id' => $accountId,
                    'card_id' => $txData['card_id'] ?? null,
                    'category_id' => $txData['category_id'] ?? null,
                    'description' => $txData['description'],
                    'value' => abs($txData['amount']),
                    'type' => $type,
                    'date' => $txData['date'],
                    'status' => $txData['status'] ?? 'confirmada',
                    'payment_method' => $txData['payment_method'] ?? null,
                    'notes' => 'Importado via OFX',
                    'import_hash' => $txData['hash'],
                    'import_hash_version' => $txData['hash_version'],
                ]);

                $created[] = $transaction->id;
            }

            AuditLog::log(
                'import_transactions',
                'Transaction',
                null,
                [
                    'total_imported' => count($created),
                    'total_skipped' => count($skipped),
                    'transaction_ids' => $created,
                ],
                $userId
            );

            // Send notification
            if (count($created) > 0) {
                $this->notificationService->notifyImportCompleted($userId, count($created), count($skipped));
            }

            DB::commit();

            return response()->json([
                'message' => 'Importação concluída com sucesso.',
                'imported' => count($created),
                'skipped' => count($skipped),
                'skipped_descriptions' => $skipped,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao importar transações: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getStats(array $analyzed): array
    {
        $stats = [
            'total' => count($analyzed),
            'new' => 0,
            'duplicate' => 0,
            'possible_duplicate' => 0,
            'transfer' => 0,
            'total_income' => 0,
            'total_expense' => 0,
        ];

        foreach ($analyzed as $item) {
            $stats[$item['technical_status']]++;
            $amount = $item['original']['amount'] ?? 0;
            if ($amount > 0) {
                $stats['total_income'] += $amount;
            } else {
                $stats['total_expense'] += abs($amount);
            }
        }

        return $stats;
    }
}
