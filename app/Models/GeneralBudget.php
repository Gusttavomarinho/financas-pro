<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralBudget extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'type',
        'category_ids',
        'include_future_categories',
        'month',
        'year',
        'is_active',
        'alert_80_sent',
        'alert_100_sent',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'category_ids' => 'array',
        'include_future_categories' => 'boolean',
        'is_active' => 'boolean',
        'alert_80_sent' => 'boolean',
        'alert_100_sent' => 'boolean',
    ];

    protected $appends = ['spent', 'percentage', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the amount spent against this budget.
     */
    public function getSpentAttribute(): float
    {
        $query = Transaction::where('user_id', $this->user_id)
            ->where('type', 'despesa');

        // Date filter based on type
        if ($this->type === 'mensal' && $this->month) {
            $startDate = sprintf('%04d-%02d-01', $this->year, $this->month);
            $lastDay = date('t', strtotime($startDate));
            $endDate = sprintf('%04d-%02d-%02d', $this->year, $this->month, $lastDay);
            $query->whereBetween('date', [$startDate, $endDate]);
        } else {
            // Annual
            $query->whereYear('date', $this->year);
        }

        // Category filter
        if (!$this->include_future_categories && !empty($this->category_ids)) {
            $query->whereIn('category_id', $this->category_ids);
        }
        // If include_future_categories = true or category_ids is empty, include all categories

        return (float) $query->sum('value');
    }

    /**
     * Get spending percentage.
     */
    public function getPercentageAttribute(): float
    {
        if ($this->amount <= 0)
            return 0;
        return min(($this->spent / $this->amount) * 100, 150); // Cap at 150%
    }

    /**
     * Get budget status (within, warning, exceeded).
     */
    public function getStatusAttribute(): string
    {
        $percentage = $this->percentage;
        if ($percentage >= 100)
            return 'exceeded';
        if ($percentage >= 80)
            return 'warning';
        return 'within';
    }

    /**
     * Scope for current month.
     */
    public function scopeCurrentMonth($query)
    {
        $now = now();
        return $query->where('type', 'mensal')
            ->where('month', $now->month)
            ->where('year', $now->year);
    }

    /**
     * Scope for current year.
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('type', 'anual')
            ->where('year', now()->year);
    }
}
