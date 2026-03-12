<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\CatheterRecord;
use App\Models\Notification;
use App\Models\Patient;
use Illuminate\Support\Str;
use Livewire\Component;

class PatientDetail extends Component
{
    public string $patientId;
    public ?Patient $patient = null;

    // Modal cateter
    public bool $showCatheterModal = false;
    public ?string $editingCatheterId = null;
    public string $insertionDate = '';
    public string $procedureType = 'ELETIVO';
    public string $indication = '';
    public string $caliber = '';
    public string $insertionSide = 'DIREITO';
    public string $passageType = '';
    public bool $safetyWire = false;
    public bool $hadPreviousCatheter = false;
    public int $minDays = 7;
    public int $maxDays = 14;
    public string $formError = '';

    // Modal detalhes cateter inativo
    public bool $showDetailModal = false;
    public ?array $detailRecord = null;

    // Modal notificação
    public bool $showNotifModal = false;
    public string $notifPhone   = '';
    public string $notifMessage = '';

    public function mount(string $id): void
    {
        $this->patientId = $id;
        $this->loadPatient();
    }

    public function loadPatient(): void
    {
        $this->patient = Patient::with(['createdBy', 'catheterRecords.createdBy', 'catheterRecords.removedBy'])->findOrFail($this->patientId);
    }

    private function authorizeDoctor(): void
    {
        abort_if(auth()->user()->role !== 'DOCTOR', 403);
    }

    public function openNewCatheter(): void
    {
        $this->authorizeDoctor();
        $this->editingCatheterId = null;
        $this->insertionDate     = now()->format('Y-m-d\TH:i');
        $this->procedureType     = 'ELETIVO';
        $this->indication        = '';
        $this->caliber           = '';
        $this->insertionSide     = 'DIREITO';
        $this->passageType       = '';
        $this->safetyWire        = false;
        $this->hadPreviousCatheter = false;
        $this->minDays           = 7;
        $this->maxDays           = 14;
        $this->formError         = '';
        $this->showCatheterModal = true;
    }

    public function openEditCatheter(string $id): void
    {
        $this->authorizeDoctor();
        $r = CatheterRecord::findOrFail($id);
        $this->editingCatheterId   = $id;
        $this->insertionDate       = \Carbon\Carbon::parse($r->insertion_date)->format('Y-m-d\TH:i');
        $this->procedureType       = $r->procedure_type;
        $this->indication          = $r->indication;
        $this->caliber             = $r->caliber;
        $this->insertionSide       = $r->insertion_side;
        $this->passageType         = $r->passage_type;
        $this->safetyWire          = (bool) $r->safety_wire;
        $this->hadPreviousCatheter = (bool) $r->had_previous_catheter;
        $this->minDays             = $r->min_days;
        $this->maxDays             = $r->max_days;
        $this->formError           = '';
        $this->showCatheterModal   = true;
    }

    public function saveCatheter(): void
    {
        $this->authorizeDoctor();
        $this->formError = '';
        $this->validate([
            'insertionDate'  => 'required|date',
            'indication'     => 'required|string',
            'caliber'        => 'required|string',
            'passageType'    => 'required|string',
            'minDays'        => 'required|integer|min:1',
            'maxDays'        => 'required|integer|min:1',
        ]);

        $data = [
            'insertion_date'       => $this->insertionDate,
            'procedure_type'       => $this->procedureType,
            'indication'           => $this->indication,
            'caliber'              => $this->caliber,
            'insertion_side'       => $this->insertionSide,
            'passage_type'         => $this->passageType,
            'safety_wire'          => $this->safetyWire,
            'had_previous_catheter'=> $this->hadPreviousCatheter,
            'min_days'             => $this->minDays,
            'max_days'             => $this->maxDays,
            'min_removal_date'     => \Carbon\Carbon::parse($this->insertionDate)->addDays($this->minDays),
            'max_removal_date'     => \Carbon\Carbon::parse($this->insertionDate)->addDays($this->maxDays),
        ];

        if ($this->editingCatheterId) {
            CatheterRecord::findOrFail($this->editingCatheterId)->update($data);
            $this->dispatch('toast', message: 'Cateter atualizado com sucesso!');
        } else {
            CatheterRecord::create(array_merge($data, [
                'patient_id'    => $this->patientId,
                'created_by_id' => auth()->id(),
            ]));
            $this->dispatch('toast', message: 'Cateter registrado com sucesso!');
        }

        $this->showCatheterModal = false;
        $this->loadPatient();
    }

    public function removeCatheter(string $id): void
    {
        $this->authorizeDoctor();
        CatheterRecord::findOrFail($id)->update(['removed_at' => now(), 'removed_by_id' => auth()->id()]);
        $this->loadPatient();
        $this->dispatch('toast', message: 'Retirada registrada com sucesso!');
    }

    public function openDetailModal(string $id): void
    {
        $r = CatheterRecord::with(['createdBy', 'removedBy'])->findOrFail($id);
        $this->detailRecord = [
            'insertion_date'        => \Carbon\Carbon::parse($r->insertion_date)->format('d/m/Y H:i'),
            'procedure_type'        => $r->procedure_type === 'ELETIVO' ? 'Eletivo' : 'Urgência',
            'indication'            => $r->indication,
            'caliber'               => $r->caliber,
            'insertion_side'        => $r->insertion_side === 'DIREITO' ? 'Direito' : 'Esquerdo',
            'passage_type'          => $r->passage_type,
            'safety_wire'           => $r->safety_wire ? 'Sim' : 'Não',
            'had_previous_catheter' => $r->had_previous_catheter ? 'Sim' : 'Não',
            'min_removal_date'      => \Carbon\Carbon::parse($r->min_removal_date)->format('d/m/Y'),
            'max_removal_date'      => \Carbon\Carbon::parse($r->max_removal_date)->format('d/m/Y'),
            'removed_at'            => \Carbon\Carbon::parse($r->removed_at)->format('d/m/Y H:i'),
            'created_by'            => $r->createdBy->name,
            'removed_by'            => $r->removedBy?->name ?? '—',
        ];
        $this->showDetailModal = true;
    }

    public function openNotifModal(): void
    {
        $this->notifPhone   = $this->patient->phone ?? '';
        $this->notifMessage = $this->buildMessage();
        $this->showNotifModal = true;
    }

    private function buildMessage(): string
    {
        $name   = $this->patient->full_name;
        $active = $this->patient->catheterRecords->whereNull('removed_at')->first();

        if (!$active) {
            return "Olá! Este é um aviso do Hospital referente ao paciente {$name}. Não há cateter ativo no momento.";
        }

        $insertionDate  = \Carbon\Carbon::parse($active->insertion_date)->format('d/m/Y');
        $maxRemovalDate = \Carbon\Carbon::parse($active->max_removal_date)->format('d/m/Y');
        $days           = (int) ceil((strtotime($active->max_removal_date) - time()) / 86400);

        if ($days <= 0) {
            $status = "O prazo de retirada está VENCIDO desde {$maxRemovalDate}. A retirada é urgente.";
        } elseif ($days === 1) {
            $status = "O prazo de retirada é AMANHÃ ({$maxRemovalDate}). A retirada deve ser agendada com urgência.";
        } elseif ($days <= 3) {
            $status = "O prazo de retirada vence em {$days} dias ({$maxRemovalDate}). Por favor, agende a retirada.";
        } else {
            $status = "O prazo máximo para retirada é {$maxRemovalDate} ({$days} dias restantes).";
        }

        return "Olá! Este é um aviso do Hospital referente ao paciente {$name}.\n\nCateter inserido em {$insertionDate} — indicação: {$active->indication}.\n{$status}";
    }

    public function sendNotification(): void
    {
        $this->validate([
            'notifPhone'   => 'required|string',
            'notifMessage' => 'required|string',
        ]);

        Notification::create([
            'id'         => Str::uuid(),
            'patient_id' => $this->patientId,
            'sent_by_id' => auth()->id(),
            'phone'      => $this->notifPhone,
            'type'       => 'MANUAL',
            'message'    => $this->notifMessage,
            'status'     => 'SENT',
            'sent_at'    => now(),
        ]);

        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => 'notification.sent',
            'model_type' => 'Patient',
            'model_id'   => $this->patientId,
            'new_values' => ['phone' => $this->notifPhone, 'message' => $this->notifMessage],
            'ip_address' => request()->ip(),
        ]);

        $this->showNotifModal = false;
        $this->dispatch('toast', message: 'Notificação registrada com sucesso!');
    }

    public function render()
    {
        return view('livewire.patient-detail')->layout('layouts.app');
    }
}
