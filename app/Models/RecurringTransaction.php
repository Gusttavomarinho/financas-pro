<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * RecurringTransaction - Regra geradora de transações
 * 
 * IMPORTANTE:
 * - Recorrência é apenas uma REGRA, não uma transação
 * - Cada transação gerada é INDEPENDENTE
 * - Histórico imutável preservado
 * - Nunca gera retroativos
 */
class RecurringTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'value',
        'description',
        'category_id',
        'account_id',
        'card_id',
        'payment_method',
        'notes',
        'frequency',
        'frequency_value',
        'start_date',
        'end_date',
        'next_occurrence',
        'last_generated_at',
        'status',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'frequency_value' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_occurrence' => 'date',
        'last_generated_at' => 'date',
    ];

    // ============ RELATIONSHIPS ============

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ============ HELPERS ============

    /**
     * Verifica se deve gerar transação (para processamento automático)
     */
    public function shouldGenerate(): bool
    {
        return $this->getBlockReason() === null;
    }

    /**
     * Retorna o motivo pelo qual a recorrência não pode ser gerada AUTOMATICAMENTE,
     * ou null se pode gerar. Usado pelo job/cron.
     */
    public function getBlockReason(): ?string
    {
        // Não gerar se pausada ou encerrada
        if ($this->status !== 'ativa') {
            return "Recorrência está com status '{$this->status}'. Apenas recorrências ativas podem gerar transações.";
        }

        // Não gerar se já gerou hoje (evita duplicação no mesmo dia)
        if ($this->last_generated_at && $this->last_generated_at->isToday()) {
            return "Já foi gerada uma transação hoje ({$this->last_generated_at->format('d/m/Y')}). Aguarde até amanhã.";
        }

        // Não gerar se next_occurrence é no futuro
        if ($this->next_occurrence->isFuture()) {
            return "A próxima cobrança está programada para {$this->next_occurrence->format('d/m/Y')}. Ainda não é a data.";
        }

        // Não gerar se passou do end_date
        if ($this->end_date && $this->next_occurrence->gt($this->end_date)) {
            return "A recorrência passou da data de término ({$this->end_date->format('d/m/Y')}).";
        }

        return null;
    }

    /**
     * Retorna o motivo pelo qual a recorrência não pode ser gerada MANUALMENTE,
     * ou null se pode gerar. Usado pelo botão "Gerar Agora".
     * 
     * Geração manual é mais permissiva: apenas bloqueia se pausada/encerrada.
     */
    public function getBlockReasonForManual(): ?string
    {
        // Não gerar se pausada ou encerrada
        if ($this->status !== 'ativa') {
            return "Recorrência está com status '{$this->status}'. Retome a recorrência antes de gerar.";
        }

        // Não gerar se passou do end_date e já encerrou
        if ($this->end_date && $this->next_occurrence->gt($this->end_date)) {
            return "A recorrência passou da data de término ({$this->end_date->format('d/m/Y')}).";
        }

        return null;
    }

    /**
     * Calcula a próxima ocorrência baseado na frequência
     */
    public function calculateNextOccurrence(): Carbon
    {
        $current = $this->next_occurrence->copy();

        switch ($this->frequency) {
            case 'semanal':
                return $current->addWeeks($this->frequency_value);

            case 'mensal':
                return $current->addMonths($this->frequency_value);

            case 'anual':
                return $current->addYears($this->frequency_value);

            case 'personalizada':
                // Personalizada = a cada X dias
                return $current->addDays($this->frequency_value);

            default:
                return $current->addMonth();
        }
    }

    /**
     * Descrição amigável da frequência
     */
    public function getFrequencyLabelAttribute(): string
    {
        $value = $this->frequency_value;

        switch ($this->frequency) {
            case 'semanal':
                return $value == 1 ? 'Semanal' : "A cada {$value} semanas";

            case 'mensal':
                return $value == 1 ? 'Mensal' : "A cada {$value} meses";

            case 'anual':
                return $value == 1 ? 'Anual' : "A cada {$value} anos";

            case 'personalizada':
                return $value == 1 ? 'Diário' : "A cada {$value} dias";

            default:
                return 'Mensal';
        }
    }

    /**
     * Verifica se a recorrência está ativa
     */
    public function isActive(): bool
    {
        return $this->status === 'ativa';
    }

    /**
     * Verifica se a recorrência está pausada
     */
    public function isPaused(): bool
    {
        return $this->status === 'pausada';
    }

    /**
     * Verifica se a recorrência foi encerrada
     */
    public function isEnded(): bool
    {
        return $this->status === 'encerrada';
    }

    // ============ BUTTON STATE HELPERS ============

    /**
     * Calcula as datas do período atual baseado na frequência da recorrência.
     * Retorna [start_date, end_date] do período correspondente.
     */
    public function getCurrentPeriodDates(): array
    {
        $now = Carbon::now();

        switch ($this->frequency) {
            case 'semanal':
                // Semana atual
                return [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek(),
                ];

            case 'mensal':
                // Mês atual
                return [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ];

            case 'anual':
                // Ano atual
                return [
                    $now->copy()->startOfYear(),
                    $now->copy()->endOfYear(),
                ];

            case 'personalizada':
                // Para frequência personalizada, consideramos o período desde a última geração
                // ou desde o início da recorrência até now
                $start = $this->last_generated_at
                    ? $this->last_generated_at->copy()
                    : $this->start_date->copy();
                return [$start, $now];

            default:
                return [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ];
        }
    }

    /**
     * Verifica se já existe uma transação gerada para esta recorrência no período atual.
     */
    public function hasTransactionInCurrentPeriod(): bool
    {
        [$startDate, $endDate] = $this->getCurrentPeriodDates();

        return $this->transactions()
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNotIn('status', ['estornada', 'cancelada'])
            ->exists();
    }

    /**
     * Retorna o estado do botão "Gerar Agora":
     * - 'normal': pode gerar sem aviso (não existe lançamento no período)
     * - 'alert': existe lançamento no período, requer confirmação
     * - 'blocked': não pode gerar (pausada/encerrada/antes da data início)
     */
    public function getGenerateButtonState(): string
    {
        // Verificar bloqueios
        if ($this->status !== 'ativa') {
            return 'blocked';
        }

        // Data atual antes da data de início
        if ($this->start_date && Carbon::now()->lt($this->start_date)) {
            return 'blocked';
        }

        // Data de término já passou
        if ($this->end_date && Carbon::now()->gt($this->end_date)) {
            return 'blocked';
        }

        // Verificar se já existe lançamento no período
        if ($this->hasTransactionInCurrentPeriod()) {
            return 'alert';
        }

        return 'normal';
    }

    /**
     * Retorna a tooltip para o botão bloqueado, ou null se não bloqueado.
     */
    public function getBlockedButtonTooltip(): ?string
    {
        if ($this->status === 'pausada') {
            return 'Esta recorrência está pausada';
        }

        if ($this->status === 'encerrada' || $this->status === 'concluida') {
            return 'Esta recorrência está encerrada';
        }

        if ($this->start_date && Carbon::now()->lt($this->start_date)) {
            return "A recorrência ainda não iniciou (início em {$this->start_date->format('d/m/Y')})";
        }

        if ($this->end_date && Carbon::now()->gt($this->end_date)) {
            return 'A recorrência passou da data de término';
        }

        return null;
    }

    /**
     * Retorna informações sobre as últimas gerações para exibição no modal.
     */
    public function getGenerationInfo(): array
    {
        $lastAutomatic = $this->transactions()
            ->where('generated_manually', false)
            ->whereNotIn('status', ['estornada', 'cancelada'])
            ->orderBy('date', 'desc')
            ->first();

        $lastManual = $this->transactions()
            ->where('generated_manually', true)
            ->whereNotIn('status', ['estornada', 'cancelada'])
            ->orderBy('date', 'desc')
            ->first();

        return [
            'last_automatic' => $lastAutomatic ? $lastAutomatic->date->format('d/m/Y') : null,
            'last_manual' => $lastManual ? $lastManual->date->format('d/m/Y') : null,
            'next_occurrence' => $this->next_occurrence?->format('d/m/Y'),
        ];
    }
}
