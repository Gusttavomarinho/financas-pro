<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Transaction;
use App\Models\CardInvoice;
use App\Models\Card;
use App\Observers\AuditObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Transaction::observe(AuditObserver::class);
        CardInvoice::observe(AuditObserver::class);
        Card::observe(AuditObserver::class);
        \App\Models\Account::observe(AuditObserver::class);
    }
}
