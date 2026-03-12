<?php

namespace App\Services;

use App\Models\CatheterRecord;

class CatheterAlertService
{
    public function daysRemaining(CatheterRecord $record): int
    {
        return (int) ceil((strtotime($record->max_removal_date) - time()) / 86400);
    }

    public function alertLevel(CatheterRecord $record): string
    {
        $days = $this->daysRemaining($record);

        return match(true) {
            $days <= 0 => 'overdue',
            $days <= 1 => 'urgent',
            $days <= 3 => 'warning',
            default    => 'ok',
        };
    }
}
