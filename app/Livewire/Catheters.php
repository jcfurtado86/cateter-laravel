<?php

namespace App\Livewire;

use App\Models\CatheterRecord;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class Catheters extends Component
{
    use WithPagination;

    public string $filter = 'all';

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
