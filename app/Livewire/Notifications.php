<?php

namespace App\Livewire;

use App\Models\Notification;
use Livewire\Component;
use Livewire\WithPagination;

class Notifications extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_if(!auth()->check(), 401);
    }

    public function render()
    {
        $notifications = Notification::with(['patient', 'sentBy'])
            ->orderByDesc('sent_at')
            ->paginate(30);

        return view('livewire.notifications', compact('notifications'))->layout('layouts.app');
    }
}
