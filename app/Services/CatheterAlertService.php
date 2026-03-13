<?php

namespace App\Services;

use App\Models\CatheterRecord;
use Carbon\Carbon;

class CatheterAlertService
{
    public function daysRemaining(CatheterRecord $record): int
    {
        return (int) Carbon::today()->diffInDays(
            Carbon::parse($record->max_removal_date)->startOfDay(),
            false
        );
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
