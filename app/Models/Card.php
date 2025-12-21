<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'primary_account_id',
        'name',
        'bank',
        'brand',
        'holder_name',
        'last_4_digits',
        'valid_thru',
        'credit_limit',
        'type',
        'closing_day',
        'due_day',
        'status',
        'icon',
        'color',
        'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'closing_day' => 'integer',
        'due_day' => 'integer',
    ];

    protected $appends = ['used_limit', 'available_limit'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Conta vinculada ao cartão (para pagamento de fatura)
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function primaryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'primary_account_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CardInvoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Calcula o limite usado = soma de todas as parcelas não pagas
     */
    public function getUsedLimitAttribute(): float
    {
        return CardInstallment::whereHas('transaction', function ($q) {
            $q->where('card_id', $this->id);
        })
            ->whereNotIn('status', ['paga', 'estornada'])
            ->sum('value');
    }

    /**
     * Limite disponível = limite total - limite usado
     */
    public function getAvailableLimitAttribute(): float
    {
        return round($this->credit_limit - $this->used_limit, 2);
    }

    /**
     * Retorna a fatura atual (aberta) do cartão
     */
    public function getCurrentInvoice(): ?CardInvoice
    {
        return $this->invoices()
            ->where('status', 'aberta')
            ->orderBy('reference_month', 'desc')
            ->first();
    }
}
