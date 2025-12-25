<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Transaction::observe(AuditObserver::class);
        Transaction::observe(\App\Observers\BudgetNotificationObserver::class);
        CardInvoice::observe(AuditObserver::class);
        CardInvoice::observe(\App\Observers\InvoiceNotificationObserver::class);
        Card::observe(AuditObserver::class);
        \App\Models\Account::observe(AuditObserver::class);

        \Illuminate\Support\Facades\Gate::policy(Transaction::class, \App\Policies\TransactionPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\TransactionAttachment::class, \App\Policies\TransactionAttachmentPolicy::class);
    }
}
