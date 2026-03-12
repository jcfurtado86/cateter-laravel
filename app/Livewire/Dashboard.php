<?php

namespace App\Livewire;

use App\Models\CatheterRecord;
use App\Models\Notification;
use Illuminate\Support\Str;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = ['total' => 0, 'overdue' => 0, 'urgent' => 0, 'warning' => 0];
    public array $alerts = [];

    // Modal notificação
    public bool   $showNotifModal = false;
    public string $notifPatientId = '';
    public string $notifPhone     = '';
    public string $notifMessage   = '';
    public array $extraStats = [
        'inserted_today' => 0,
        'removed_today'  => 0,
        'avg_permanence' => null,
        'on_time_rate'   => null,
    ];
    public array $indicationStats    = [];
    public array $procedureStats     = [];

    public function mount(): void
    {
        $this->loadAlerts();
        $this->loadExtraStats();
    }

    public function loadAlerts(): void
    {
        $records = CatheterRecord::with('patient')
            ->whereNull('removed_at')
            ->get()
            ->map(function ($r) {
                $days = (int) ceil((strtotime($r->max_removal_date) - time()) / 86400);
                $level = match(true) {
                    $days <= 0 => 'overdue',
                    $days <= 1 => 'urgent',
                    $days <= 3 => 'warning',
                    default    => 'ok',
                };
                return array_merge($r->toArray(), [
                    'days_left'     => $days,
                    'alert_level'   => $level,
                    'patient_name'  => $r->patient->full_name,
                    'patient_id'    => $r->patient->id,
                    'record_number' => $r->patient->record_number,
                    'phone'         => $r->patient->phone,
                ]);
            });

        $this->stats = [
            'total'   => $records->count(),
            'overdue' => $records->where('alert_level', 'overdue')->count(),
            'urgent'  => $records->where('alert_level', 'urgent')->count(),
            'warning' => $records->where('alert_level', 'warning')->count(),
        ];

        $this->alerts = $records->where('alert_level', '!=', 'ok')->values()->toArray();
    }

    public function loadExtraStats(): void
    {
        // Operational stats
        $this->extraStats['inserted_today'] = CatheterRecord::whereDate('insertion_date', today())->count();
        $this->extraStats['removed_today']  = CatheterRecord::whereDate('removed_at', today())->count();

        // Average permanence (only removed catheters)
        $avg = CatheterRecord::whereNotNull('removed_at')
            ->selectRaw("AVG(EXTRACT(EPOCH FROM (removed_at - insertion_date)) / 86400) as avg_days")
            ->value('avg_days');
        $this->extraStats['avg_permanence'] = $avg !== null ? round($avg, 1) : null;

        // On-time removal rate
        $totalRemoved = CatheterRecord::whereNotNull('removed_at')->count();
        $onTime = CatheterRecord::whereNotNull('removed_at')
            ->whereColumn('removed_at', '<=', 'max_removal_date')
            ->count();
        $this->extraStats['on_time_rate'] = $totalRemoved > 0 ? round($onTime / $totalRemoved * 100) : null;

        // Indication distribution (active catheters, top 5)
        $total = $this->stats['total'];
        $this->indicationStats = CatheterRecord::whereNull('removed_at')
            ->selectRaw('indication, COUNT(*) as count')
            ->groupBy('indication')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'indication' => $r->indication,
                'count'      => $r->count,
                'pct'        => $total > 0 ? round($r->count / $total * 100) : 0,
            ])
            ->toArray();

        // Procedure type distribution (active catheters)
        $this->procedureStats = CatheterRecord::whereNull('removed_at')
            ->selectRaw('procedure_type, COUNT(*) as count')
            ->groupBy('procedure_type')
            ->orderByDesc('count')
            ->get()
            ->toArray();

    }

    public function openNotifModal(string $recordId): void
    {
        $r = CatheterRecord::with('patient')->findOrFail($recordId);
        $this->notifPatientId = $r->patient->id;
        $this->notifPhone     = $r->patient->phone ?? '';

        $name           = $r->patient->full_name;
        $insertionDate  = \Carbon\Carbon::parse($r->insertion_date)->format('d/m/Y');
        $maxRemovalDate = \Carbon\Carbon::parse($r->max_removal_date)->format('d/m/Y');
        $days           = (int) ceil((strtotime($r->max_removal_date) - time()) / 86400);

        if ($days <= 0) {
            $status = "O prazo de retirada está VENCIDO desde {$maxRemovalDate}. A retirada é urgente.";
        } elseif ($days === 1) {
            $status = "O prazo de retirada é AMANHÃ ({$maxRemovalDate}). A retirada deve ser agendada com urgência.";
        } elseif ($days <= 3) {
            $status = "O prazo de retirada vence em {$days} dias ({$maxRemovalDate}). Por favor, agende a retirada.";
        } else {
            $status = "O prazo máximo para retirada é {$maxRemovalDate} ({$days} dias restantes).";
        }

        $this->notifMessage   = "Olá! Este é um aviso do Hospital referente ao paciente {$name}.\n\nCateter inserido em {$insertionDate} — indicação: {$r->indication}.\n{$status}";
        $this->showNotifModal = true;
    }

    public function sendNotification(): void
    {
        $this->validate([
            'notifPhone'   => 'required|string',
            'notifMessage' => 'required|string',
        ]);

        Notification::create([
            'id'         => Str::uuid(),
            'patient_id' => $this->notifPatientId,
            'sent_by_id' => auth()->id(),
            'phone'      => $this->notifPhone,
            'type'       => 'MANUAL',
            'message'    => $this->notifMessage,
            'status'     => 'SENT',
            'sent_at'    => now(),
        ]);

        $this->showNotifModal = false;
        $this->dispatch('toast', message: 'Notificação registrada com sucesso!');
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }
}
