<?php

namespace App\Console\Commands;

use App\Models\CatheterRecord;
use App\Models\Notification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCatheterAlerts extends Command
{
    protected $signature   = 'catheters:send-alerts';
    protected $description = 'Envia notificações automáticas para cateteres próximos do prazo máximo de retirada';

    private const ALERT_DAYS = [
        3 => 'ALERT_3D',
        1 => 'ALERT_1D',
        0 => 'ALERT_DUE',
    ];

    public function handle(NotificationService $service): int
    {
        $today = Carbon::today();

        foreach (self::ALERT_DAYS as $daysAhead => $type) {
            $targetDate = $today->copy()->addDays($daysAhead)->toDateString();

            $alreadyNotified = Notification::where('type', $type)
                ->whereDate('sent_at', $today)
                ->pluck('patient_id');

            $records = CatheterRecord::with('patient')
                ->whereNull('removed_at')
                ->whereDate('max_removal_date', $targetDate)
                ->whereNotIn('patient_id', $alreadyNotified)
                ->get();

            foreach ($records as $record) {
                if (empty($record->patient->phone)) {
                    $this->warn("Paciente {$record->patient->full_name} sem telefone — ignorado.");
                    continue;
                }

                $service->sendAuto($record, $type);
                $this->info("[{$type}] {$record->patient->full_name} ({$record->patient->phone})");
            }
        }

        return Command::SUCCESS;
    }
}
