<?php

namespace App\Console\Commands;

use App\Services\RecurringTransactionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:recurring-transactions {--date= : Data de referência (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa as transações recorrentes vencidas e gera os lançamentos';

    /**
     * Execute the console command.
     */
    public function handle(RecurringTransactionService $service)
    {
        $dateInput = $this->option('date');
        $date = $dateInput ? Carbon::parse($dateInput) : Carbon::now();

        // For testing: simulate the system date so isFuture()/isToday() work correctly
        if ($dateInput) {
            Carbon::setTestNow($date);
            $this->info("⏰ Simulando data do sistema como: " . $date->format('Y-m-d'));
        }

        $this->info("Iniciando processamento para data: " . $date->format('Y-m-d'));

        try {
            $count = $service->processDue($date);

            $message = "Processamento concluído. {$count} transações geradas.";
            $this->info($message);
            Log::info($message, ['date' => $date->format('Y-m-d')]);

            return 0;
        } catch (\Exception $e) {
            $error = "Erro fatal ao processar recorrências: " . $e->getMessage();
            $this->error($error);
            Log::error($error, ['trace' => $e->getTraceAsString()]);

            return 1;
        } finally {
            // Reset test time
            if ($dateInput) {
                Carbon::setTestNow();
            }
        }
    }
}
