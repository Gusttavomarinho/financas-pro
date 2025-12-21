<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'card_invoice_id',
        'installment_number',
        'total_installments',
        'value',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'value' => 'decimal:2',
    ];

    /**
     * Transação (compra) a que pertence esta parcela
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Fatura em que esta parcela está incluída
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CardInvoice::class, 'card_invoice_id');
    }

    /**
     * Verifica se a parcela está paga
     */
    public function isPaid(): bool
    {
        return $this->status === 'paga';
    }

    /**
     * Verifica se pode ser antecipada
     */
    public function canBeAnticipated(): bool
    {
        return in_array($this->status, ['pendente', 'em_fatura']);
    }
}
