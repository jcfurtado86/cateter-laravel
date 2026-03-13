<?php

namespace App\Livewire;

use App\Helpers\AuditHelper;
use App\Livewire\Concerns\HasNotificationModal;
use App\Models\CatheterRecord;
use App\Models\Patient;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PatientDetail extends Component
{
    use HasNotificationModal;

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
        Gate::authorize('manage', CatheterRecord::class);
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
        $catheter = CatheterRecord::findOrFail($id);
        $this->editingCatheterId   = $id;
        $this->insertionDate       = \Carbon\Carbon::parse($catheter->insertion_date)->format('Y-m-d\TH:i');
        $this->procedureType       = $catheter->procedure_type;
        $this->indication          = $catheter->indication;
        $this->caliber             = $catheter->caliber;
        $this->insertionSide       = $catheter->insertion_side;
        $this->passageType         = $catheter->passage_type;
        $this->safetyWire          = (bool) $catheter->safety_wire;
        $this->hadPreviousCatheter = (bool) $catheter->had_previous_catheter;
        $this->minDays             = $catheter->min_days;
        $this->maxDays             = $catheter->max_days;
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
            'min_removal_date'     => \Carbon\Carbon::parse($this->insertionDate)->startOfDay()->addDays($this->minDays),
            'max_removal_date'     => \Carbon\Carbon::parse($this->insertionDate)->startOfDay()->addDays($this->maxDays),
        ];

        if ($this->editingCatheterId) {
            $catheter  = CatheterRecord::findOrFail($this->editingCatheterId);
            $oldValues = $catheter->only(array_keys($data));
            $catheter->update($data);

            AuditHelper::logAction('catheter.updated', $catheter, $oldValues, $data);

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
        $catheter = CatheterRecord::findOrFail($id);
        $catheter->update(['removed_at' => now(), 'removed_by_id' => auth()->id()]);

        AuditHelper::logAction('catheter.removed', $catheter, ['removed_at' => null], ['removed_at' => $catheter->removed_at->toDateTimeString()]);

        $this->loadPatient();
        $this->dispatch('toast', message: 'Retirada registrada com sucesso!');
    }

    public function openDetailModal(string $id): void
    {
        $catheter = CatheterRecord::with(['createdBy', 'removedBy'])->findOrFail($id);
        $this->detailRecord = [
            'insertion_date'        => \Carbon\Carbon::parse($catheter->insertion_date)->format('d/m/Y H:i'),
            'procedure_type'        => $catheter->procedure_type === 'ELETIVO' ? 'Eletivo' : 'Urgência',
            'indication'            => $catheter->indication,
            'caliber'               => $catheter->caliber,
            'insertion_side'        => $catheter->insertion_side === 'DIREITO' ? 'Direito' : 'Esquerdo',
            'passage_type'          => $catheter->passage_type,
            'safety_wire'           => $catheter->safety_wire ? 'Sim' : 'Não',
            'had_previous_catheter' => $catheter->had_previous_catheter ? 'Sim' : 'Não',
            'min_removal_date'      => \Carbon\Carbon::parse($catheter->min_removal_date)->format('d/m/Y'),
            'max_removal_date'      => \Carbon\Carbon::parse($catheter->max_removal_date)->format('d/m/Y'),
            'removed_at'            => \Carbon\Carbon::parse($catheter->removed_at)->format('d/m/Y H:i'),
            'created_by'            => $catheter->createdBy->name,
            'removed_by'            => $catheter->removedBy?->name ?? '—',
        ];
        $this->showDetailModal = true;
    }

    public function openNotifModal(): void
    {
        $active = $this->patient->catheterRecords->whereNull('removed_at')->first();

        $this->notifPatientId = $this->patientId;
        $this->notifPhone     = $this->patient->phone ?? '';

        if (!$active) {
            $name = $this->patient->full_name;
            $this->notifMessage = "Olá! Este é um aviso do Hospital referente ao paciente {$name}. Não há cateter ativo no momento.";
        } else {
            $this->notifMessage = app(NotificationService::class)->buildMessage($active);
        }

        $this->showNotifModal = true;
    }

    public function render()
    {
        return view('livewire.patient-detail')->layout('layouts.app');
    }
}
