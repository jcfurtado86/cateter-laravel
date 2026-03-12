<?php

namespace App\Livewire;

use App\Models\Patient;
use Livewire\Component;
use Livewire\WithPagination;

class Patients extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?string $editingId = null;

    // form fields
    public string $fullName = '';
    public string $recordNumber = '';
    public string $birthDate = '';
    public string $sex = 'M';
    public string $race = 'NAO_INFORMADA';
    public string $phone = '';
    public string $formError = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function authorizeDoctor(): void
    {
        abort_if(auth()->user()->role !== 'DOCTOR', 403);
    }

    public function openNew(): void
    {
        $this->authorizeDoctor();
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(string $id): void
    {
        $this->authorizeDoctor();
        $patient = Patient::findOrFail($id);
        $this->editingId = $id;
        $this->fullName = $patient->full_name;
        $this->recordNumber = $patient->record_number;
        $this->birthDate = $patient->birth_date->format('Y-m-d');
        $this->sex = $patient->sex;
        $this->race = $patient->race;
        $this->phone = $patient->phone;
        $this->formError = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorizeDoctor();
        $this->formError = '';
        $this->validate([
            'fullName'     => 'required|string|max:255',
            'recordNumber' => 'required|string|max:50',
            'birthDate'    => 'required|date',
            'sex'          => 'required|in:M,F,OUTRO',
            'race'         => 'required',
            'phone'        => 'required|string|max:20',
        ]);

        try {
            $data = [
                'full_name'     => $this->fullName,
                'record_number' => $this->recordNumber,
                'birth_date'    => $this->birthDate,
                'sex'           => $this->sex,
                'race'          => $this->race,
                'phone'         => $this->phone,
            ];

            if ($this->editingId) {
                Patient::findOrFail($this->editingId)->update($data);
                $this->dispatch('toast', message: 'Paciente atualizado com sucesso!');
            } else {
                Patient::create(array_merge($data, ['created_by_id' => auth()->id()]));
                $this->dispatch('toast', message: 'Paciente cadastrado com sucesso!');
            }

            $this->showModal = false;
            $this->resetForm();
        } catch (\Exception $e) {
            $this->formError = str_contains($e->getMessage(), 'record_number')
                ? 'Número de prontuário já cadastrado'
                : 'Erro ao salvar paciente';
        }
    }

    private function resetForm(): void
    {
        $this->fullName = '';
        $this->recordNumber = '';
        $this->birthDate = '';
        $this->sex = 'M';
        $this->race = 'NAO_INFORMADA';
        $this->phone = '';
        $this->formError = '';
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.default';
    }

    public function render()
    {
        $patients = Patient::with(['catheterRecords' => fn($q) => $q->whereNull('removed_at')])
            ->when($this->search, fn($q) =>
                $q->where('full_name', 'ilike', "%{$this->search}%")
                  ->orWhere('record_number', 'ilike', "%{$this->search}%")
            )
            ->orderBy('full_name')
            ->paginate(20);

        return view('livewire.patients', compact('patients'))->layout('layouts.app');
    }
}
