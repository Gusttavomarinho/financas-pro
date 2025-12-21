<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class BusinessAuditService
{
    /**
     * Log a specific business event with before/after snapshots.
     *
     * @param string $event The business event name (e.g., 'edit_transaction', 'pay_invoice')
     * @param Model $target The model being affected
     * @param array $changes Array containing ['before' => [...], 'after' => [...]]
     */
    public function log(string $event, Model $target, array $changes): void
    {
        // Ensure structure matches requirements
        $structuredChanges = [
            'event_type' => 'business_logic',
            'details' => $changes // Expecting ['before' => ..., 'after' => ...]
        ];

        AuditLog::create([
            'user_id' => auth()->id() ?? $target->user_id, // Fallback to owner if no auth
            'action' => $event,
            'model' => class_basename($target),
            'model_id' => $target->id,
            'changes' => $structuredChanges,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
