<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    public function created(Model $model)
    {
        $this->logAction('create', $model);
    }

    public function updated(Model $model)
    {
        // Ignore timestamps updates only? No, log everything for now
        $changes = $model->getChanges();

        // Remove updated_at/created_at noise if only those changed?
        // But for critical models, every update matters.
        // We can capture original/changes.

        $original = $model->getOriginal();

        $this->logAction('update', $model, [
            'changes' => $changes,
            'original' => array_intersect_key($original, $changes)
        ]);
    }

    public function deleted(Model $model)
    {
        $this->logAction('delete', $model);
    }

    // Custom events like 'paid', 'refunded' are handled manually in Service/Controller.
    // This Observer handles standard CRUD.

    protected function logAction(string $action, Model $model, array $details = [])
    {
        // Only log if user is authenticated (system actions might be filtered or user_id 0)
        // Requirement: "Autoria" (Audit).

        $userId = Auth::id() ?? $model->user_id ?? null; // Fallback to model owner

        if (!$userId)
            return;

        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model' => class_basename($model),
            'model_id' => $model->id,
            'details' => json_encode($details),
            'ip_address' => request()->ip(),
        ]);
    }
}
