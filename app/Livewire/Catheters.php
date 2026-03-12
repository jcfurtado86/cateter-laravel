<?php

namespace App\Livewire;

use App\Helpers\AuditHelper;
use App\Livewire\Concerns\HasNotificationModal;
use App\Models\CatheterRecord;
use Illuminate\Support\Facades\Gate;
use App\Services\CatheterAlertService;
use App\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class Catheters extends Component
{
    use WithPagination, HasNotificationModal;

    public string $filter = 'all';

    public function openNotifModal(string $recordId): void
    {
        $record = CatheterRecord::with('patient')->findOrFail($recordId);
        $this->notifPatientId = $record->patient->id;
        $this->notifPhone     = $record->patient->phone ?? '';
        $this->notifMessage   = app(NotificationService::class)->buildMessage($record);
        $this->showNotifModal = true;
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function remove(string $id): void
    {
        Gate::authorize('manage', CatheterRecord::class);

        $catheter = CatheterRecord::findOrFail($id);
        $catheter->update(['removed_at' => now()]);

        AuditHelper::logAction('catheter.removed', $catheter, ['removed_at' => null], ['removed_at' => $catheter->removed_at->toDateTimeString()]);

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

        $alertService = app(CatheterAlertService::class);
        $all = $query->get()->map(function ($record) use ($alertService) {
            $record->days_left   = $alertService->daysRemaining($record);
            $record->alert_level = $alertService->alertLevel($record);
            return $record;
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
