<?php

namespace App\Livewire;

use App\Models\Patient;
use App\Models\CatheterRecord;
use Illuminate\Support\Facades\Hash;
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
        $this->insertionDate     = now()->format('Y-m-d');
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
        $this->insertionDate       = \Carbon\Carbon::parse($r->insertion_date)->format('Y-m-d');
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

    public function render()
    {
        return view('livewire.patient-detail')->layout('layouts.app');
    }
}
