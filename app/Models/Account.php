<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'initial_balance',
        'currency',
        'icon',
        'color',
        'bank',
        'agency',
        'account_number',
        'notes',
        'is_active',
        'status',
        'exclude_from_totals',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'exclude_from_totals' => 'boolean',
    ];

    protected $appends = ['current_balance'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_account_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'account_id');
    }

    /**
     * Verifica se a conta está arquivada
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Arquiva a conta (não exclui)
     */
    public function archive(): void
    {
        $this->status = 'archived';
        $this->save();
    }

    /**
     * Verifica se a conta pode ser excluída fisicamente
     * (apenas se não tiver lançamentos)
     */
    public function canBeDeleted(): bool
    {
        return $this->transactions()->count() === 0
            && $this->outgoingTransfers()->count() === 0;
    }

    /**
     * Escopo para contas ativas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Escopo para contas que devem ser incluídas nos totais
     */
    public function scopeIncludedInTotals($query)
    {
        return $query->where('exclude_from_totals', false);
    }

    /**
     * Calcula o saldo atual da conta em tempo real
     */
    public function getCurrentBalanceAttribute(): float
    {
        $balance = $this->initial_balance;

        // Receitas
        $credits = $this->transactions()
            ->where('affects_balance', true)
            ->where('status', 'confirmada')
            ->whereIn('type', ['receita', 'ajuste', 'devolucao'])
            ->sum('value');

        // Transferências recebidas (esta conta é o destino)
        $transfersIn = Transaction::where('from_account_id', $this->id)
            ->where('affects_balance', true)
            ->where('status', 'confirmada')
            ->where('type', 'transferencia')
            ->sum('value');

        // Despesas
        $debits = $this->transactions()
            ->where('affects_balance', true)
            ->where('status', 'confirmada')
            ->where('type', 'despesa')
            ->sum('value');

        // Transferências enviadas (esta conta é a origem)
        $transfersOut = $this->transactions()
            ->where('affects_balance', true)
            ->where('status', 'confirmada')
            ->where('type', 'transferencia')
            ->sum('value');

        return round($balance + $credits + $transfersIn - $debits - $transfersOut, 2);
    }
}
