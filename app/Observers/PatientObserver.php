<?php

namespace App\Observers;

use App\Helpers\AuditHelper;
use App\Models\Patient;

class PatientObserver
{
    public function created(Patient $patient): void
    {
        AuditHelper::logAction('patient.created', $patient, null, $patient->toArray());
    }

    public function deleted(Patient $patient): void
    {
        AuditHelper::logAction('patient.deleted', $patient, $patient->toArray(), null);
    }
}
