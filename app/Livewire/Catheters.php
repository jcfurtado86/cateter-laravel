<?php

namespace App\Livewire;

use App\Models\CatheterRecord;
use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Catheters extends Component
{
    use WithPagination;

    public string $filter = 'all';

    // Modal notificação
    public bool   $showNotifModal = false;
    public string $notifPatientId = '';
    public string $notifPhone     = '';
    public string $notifMessage   = '';

    public function openNotifModal(string $recordId): void
    {
        $r = CatheterRecord::with('patient')->findOrFail($recordId);
        $this->notifPatientId = $r->patient->id;
        $this->notifPhone     = $r->patient->phone ?? '';
        $this->notifMessage   = $this->buildMessage($r);
        $this->showNotifModal = true;
    }

    private function buildMessage(CatheterRecord $r): string
    {
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

        return "Olá! Este é um aviso do Hospital referente ao paciente {$name}.\n\nCateter inserido em {$insertionDate} — indicação: {$r->indication}.\n{$status}";
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

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function remove(string $id): void
    {
        CatheterRecord::findOrFail($id)->update(['removed_at' => now()]);
        $this->dispatch('toast', message: 'Retirada registrada com sucesso!');
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.default';
    }

    public function render()
    {
        $query = CatheterRecord::with(['patient', 'createdBy'])
            ->whereNull('removed_at');

        $all = $query->get()->map(function ($r) {
            $days = (int) ceil((strtotime($r->max_removal_date) - time()) / 86400);
            $level = match(true) {
                $days <= 0 => 'overdue',
                $days <= 1 => 'urgent',
                $days <= 3 => 'warning',
                default    => 'ok',
            };
            $r->days_left = $days;
            $r->alert_level = $level;
            return $r;
        });

        $counts = [
            'all'     => $all->count(),
            'overdue' => $all->where('alert_level', 'overdue')->count(),
            'urgent'  => $all->where('alert_level', 'urgent')->count(),
            'warning' => $all->where('alert_level', 'warning')->count(),
            'ok'      => $all->where('alert_level', 'ok')->count(),
        ];

        $filtered = $this->filter === 'all'
            ? $all
            : $all->where('alert_level', $this->filter);

        $perPage = 20;
        $page    = $this->getPage();
        $items   = $filtered->values()->forPage($page, $perPage);

        $records = new LengthAwarePaginator(
            $items,
            $filtered->count(),
            $perPage,
            $page,
            ['pageName' => 'page', 'path' => request()->url()]
        );

        return view('livewire.catheters', [
            'records' => $records,
            'counts'  => $counts,
            'total'   => $filtered->count(),
        ])->layout('layouts.app');
    }
}
