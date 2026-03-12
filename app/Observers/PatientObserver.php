<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Patient;

class PatientObserver
{
    public function created(Patient $patient): void
    {
        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'patient.created',
            'model_type' => 'Patient',
            'model_id'   => $patient->id,
            'new_values' => $patient->toArray(),
            'ip_address' => request()->ip(),
        ]);
    }

    public function updated(Patient $patient): void
    {
        $changes = $patient->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) return;

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'patient.updated',
            'model_type' => 'Patient',
            'model_id'   => $patient->id,
            'old_values' => array_intersect_key($patient->getOriginal(), $changes),
            'new_values' => $changes,
            'ip_address' => request()->ip(),
        ]);
    }

    public function deleted(Patient $patient): void
    {
        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'patient.deleted',
            'model_type' => 'Patient',
            'model_id'   => $patient->id,
            'old_values' => $patient->toArray(),
            'ip_address' => request()->ip(),
        ]);
    }
}
