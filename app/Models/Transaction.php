<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'from_account_id',
        'card_id',
        'category_id',
        'card_invoice_id',
        'type',
        'value', // Valor TOTAL da compra
        'description',
        'date', // Data REAL da compra
        'time',
        'payment_method',
        'total_installments', // Quantidade de parcelas (1 = à vista)
        'installment_value', // Valor de cada parcela (calculado)
        'affects_balance',
        'status', // confirmada, pendente, estornada, cancelada
        'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'installment_value' => 'decimal:2',
        'date' => 'date:Y-m-d', // Força formato YYYY-MM-DD na serialização JSON
        'affects_balance' => 'boolean',
    ];

    protected $appends = ['is_installment'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CardInvoice::class, 'card_invoice_id');
    }

    /**
     * Parcelas desta compra (para compras no crédito)
     */
    public function cardInstallments(): HasMany
    {
        return $this->hasMany(CardInstallment::class)->orderBy('installment_number');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'transaction_tags');
    }

    /**
     * Verifica se é uma compra parcelada
     */
    public function getIsInstallmentAttribute(): bool
    {
        return $this->total_installments > 1;
    }

    /**
     * Verifica se é uma compra no crédito
     */
    public function isCreditPurchase(): bool
    {
        return $this->payment_method === 'credito' && $this->card_id !== null;
    }

    /**
     * Estorna a transação
     */
    public function cancel(): void
    {
        $this->status = 'estornada';
        $this->save();

        // Estornar todas as parcelas pendentes
        $this->cardInstallments()
            ->whereIn('status', ['pendente', 'em_fatura'])
            ->update(['status' => 'estornada']);
    }

    /**
     * Escopo para transações que afetam o saldo
     */
    public function scopeAffectsBalance($query)
    {
        return $query->where('affects_balance', true);
    }

    /**
     * Escopo para transações confirmadas
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmada');
    }

    /**
     * Escopo para transações de um período
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Escopo para transações ativas (não estornadas/canceladas)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['estornada', 'cancelada']);
    }
}
