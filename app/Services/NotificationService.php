<?php

namespace App\Services;

use App\Models\CatheterRecord;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NotificationService
{
    public function buildMessage(CatheterRecord $record): string
    {
        $name           = $record->patient->full_name;
        $insertionDate  = Carbon::parse($record->insertion_date)->format('d/m/Y');
        $maxRemovalDate = Carbon::parse($record->max_removal_date)->format('d/m/Y');
        $days           = (int) Carbon::today()->diffInDays(Carbon::parse($record->max_removal_date)->startOfDay(), false);

        if ($days <= 0) {
            $status = "O prazo de retirada está VENCIDO desde {$maxRemovalDate}. A retirada é urgente.";
        } elseif ($days === 1) {
            $status = "O prazo de retirada é AMANHÃ ({$maxRemovalDate}). A retirada deve ser agendada com urgência.";
        } elseif ($days <= 3) {
            $status = "O prazo de retirada vence em {$days} dias ({$maxRemovalDate}). Por favor, agende a retirada.";
        } else {
            $status = "O prazo máximo para retirada é {$maxRemovalDate} ({$days} dias restantes).";
        }

        return "Olá! Este é um aviso do Hospital referente ao paciente {$name}.\n\nCateter inserido em {$insertionDate} — indicação: {$record->indication}.\n{$status}";
    }

    public function send(string $patientId, string $phone, string $message): Notification
    {
        return Notification::create([
            'id'         => Str::uuid(),
            'patient_id' => $patientId,
            'sent_by_id' => auth()->id(),
            'phone'      => $phone,
            'type'       => 'MANUAL',
            'message'    => $message,
            'status'     => 'SENT',
            'sent_at'    => now(),
        ]);
    }

    public function sendAuto(CatheterRecord $record, string $type): Notification
    {
        return Notification::create([
            'id'         => Str::uuid(),
            'patient_id' => $record->patient_id,
            'sent_by_id' => null,
            'phone'      => $record->patient->phone,
            'type'       => $type,
            'message'    => $this->buildMessage($record),
            'status'     => 'SENT',
            'sent_at'    => now(),
        ]);
    }
}
