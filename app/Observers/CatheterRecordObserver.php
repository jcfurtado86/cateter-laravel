<?php

namespace App\Observers;

use App\Helpers\AuditHelper;
use App\Models\CatheterRecord;

class CatheterRecordObserver
{
    public function created(CatheterRecord $record): void
    {
        AuditHelper::logAction('catheter.inserted', $record, null, $record->toArray());
    }

}
