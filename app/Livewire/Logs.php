<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\AuthLog;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Logs extends Component
{
    use WithPagination;

    public string $tab        = 'auth';
    public string $dateFrom   = '';
    public string $dateTo     = '';
    public string $search     = '';

    public function mount(): void
    {
        Gate::authorize('admin-only');
    }

    public function updatingTab(): void    { $this->resetPage(); }
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void  { $this->resetPage(); }

    public function paginationView(): string
    {
        return 'vendor.pagination.default';
    }

    public function render()
    {
        if ($this->tab === 'auth') {
            $query = AuthLog::with('user')->orderByDesc('created_at');

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('email', 'ilike', "%{$this->search}%")
                      ->orWhereHas('user', fn($user) => $user->where('name', 'ilike', "%{$this->search}%"));
                });
            }
            if ($this->dateFrom) $query->whereDate('created_at', '>=', $this->dateFrom);
            if ($this->dateTo)   $query->whereDate('created_at', '<=', $this->dateTo);

            $logs = $query->paginate(30, ['*'], 'page')->withPath(request()->url());
        } else {
            $query = AuditLog::with('user')->orderByDesc('created_at');

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('action', 'ilike', "%{$this->search}%")
                      ->orWhereHas('user', fn($user) => $user->where('name', 'ilike', "%{$this->search}%"));
                });
            }
            if ($this->dateFrom) $query->whereDate('created_at', '>=', $this->dateFrom);
            if ($this->dateTo)   $query->whereDate('created_at', '<=', $this->dateTo);

            $logs = $query->paginate(30, ['*'], 'page')->withPath(request()->url());
        }

        return view('livewire.logs', ['logs' => $logs])->layout('layouts.app');
    }
}
