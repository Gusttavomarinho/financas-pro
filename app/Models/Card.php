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
     * Calcula o limite usado = soma do valor restante de todas as faturas não pagas
     * (total_value - paid_value) de faturas com status != 'paga'
     */
    public function getUsedLimitAttribute(): float
    {
        // SQLite não tem GREATEST, usar CASE WHEN para compatibilidade
        return $this->invoices()
            ->whereIn('status', ['aberta', 'parcialmente_paga', 'fechada', 'vencida'])
            ->selectRaw('SUM(CASE WHEN total_value > paid_value THEN total_value - paid_value ELSE 0 END) as remaining')
            ->value('remaining') ?? 0;
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

    /**
     * Verifica se o cartão está arquivado
     */
    public function isArchived(): bool
    {
        return $this->status === 'arquivado';
    }

    /**
     * Arquiva o cartão (não exclui)
     */
    public function archive(): void
    {
        $this->status = 'arquivado';
        $this->save();
    }

    /**
     * Verifica se o cartão pode ser excluído fisicamente
     * (apenas se não tiver lançamentos)
     */
    public function canBeDeleted(): bool
    {
        return $this->transactions()->count() === 0;
    }
}
