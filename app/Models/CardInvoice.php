<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_id',
        'reference_month',
        'period_start',
        'period_end',
        'closing_date',
        'due_date',
        'total_value',
        'paid_value',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'closing_date' => 'date',
        'due_date' => 'date',
        'total_value' => 'decimal:2',
        'paid_value' => 'decimal:2',
    ];

    protected $appends = ['remaining_value'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Parcelas vinculadas a esta fatura
     */
    public function installments(): HasMany
    {
        return $this->hasMany(CardInstallment::class)->whereNotIn('status', ['estornada']);
    }

    /**
     * Transações diretas (compras à vista no crédito)
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Valor restante a pagar
     */
    public function getRemainingValueAttribute(): float
    {
        return round($this->total_value - $this->paid_value, 2);
    }

    /**
     * Recalcula o valor total baseado nas parcelas
     */
    public function recalculateTotal(): void
    {
        // Soma das parcelas ativas nesta fatura (includes 'antecipada' since they moved here)
        $this->total_value = $this->installments()
            ->whereNotIn('status', ['estornada'])
            ->sum('value');

        $this->updateStatus();
        $this->save();
    }

    /**
     * Atualiza o status da fatura baseado nos valores
     */
    public function updateStatus(): void
    {
        if ($this->paid_value >= $this->total_value && $this->total_value > 0) {
            $this->status = 'paga';
        } elseif ($this->paid_value > 0) {
            $this->status = 'parcialmente_paga';
        } elseif ($this->due_date < now() && $this->paid_value < $this->total_value) {
            $this->status = 'vencida';
        } elseif ($this->closing_date <= now()) {
            $this->status = 'fechada';
        } else {
            $this->status = 'aberta';
        }
    }

    /**
     * Retorna todos os itens da fatura (parcelas com info da transação)
     */
    public function getItemsAttribute()
    {
        return $this->installments()
            ->with([
                'transaction' => function ($query) {
                    // Incluir transações excluídas para não quebrar a fatura
                    $query->withTrashed()->with('category', 'card');
                }
            ])
            ->get()
            ->map(function ($installment) {
                $transaction = $installment->transaction;

                return [
                    'id' => $installment->id,
                    'transaction_id' => $installment->transaction_id,
                    'description' => $transaction ? $transaction->description : 'Lançamento excluído',
                    'date' => $transaction ? $transaction->date : null,
                    'installment_number' => $installment->installment_number,
                    'total_installments' => $installment->total_installments,
                    'value' => $installment->value,
                    'status' => $installment->status,
                    'category' => $transaction ? $transaction->category : null,
                ];
            });
    }
}
