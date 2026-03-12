<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\CatheterRecord;

class CatheterRecordObserver
{
    public function created(CatheterRecord $record): void
    {
        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'catheter.inserted',
            'model_type' => 'CatheterRecord',
            'model_id'   => $record->id,
            'new_values' => $record->toArray(),
            'ip_address' => request()->ip(),
        ]);
    }

    public function updated(CatheterRecord $record): void
    {
        $changes = $record->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) return;

        $action = isset($changes['removed_at']) ? 'catheter.removed' : 'catheter.updated';

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'model_type' => 'CatheterRecord',
            'model_id'   => $record->id,
            'old_values' => array_intersect_key($record->getOriginal(), $changes),
            'new_values' => $changes,
            'ip_address' => request()->ip(),
        ]);
    }
}
