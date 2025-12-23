<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/**
 * Scheduler para geração automática de recorrências
 * 
 * Executa diariamente às 00:05 (para garantir que mudou o dia)
 * Idempotente: pode rodar múltiplas vezes sem duplicar
 * 
 * Para ativar em produção, adicione ao crontab:
 * * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
 */
Schedule::command('recurring:generate')
    ->daily()
    ->at('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/recurring.log'))
    ->description('Gera transações de recorrências ativas');

/**
 * Job para verificar faturas próximas do vencimento
 * 
 * Executa diariamente às 08:00 (horário comercial)
 * Envia notificações para faturas que vencem em 3 dias
 */
Schedule::job(new \App\Jobs\CheckInvoicesDueSoon())
    ->daily()
    ->at('08:00')
    ->withoutOverlapping()
    ->description('Notifica sobre faturas próximas do vencimento');
