<?php

namespace App\Livewire\Concerns;

use App\Services\NotificationService;

trait HasNotificationModal
{
    public bool   $showNotifModal = false;
    public string $notifPatientId = '';
    public string $notifPhone     = '';
    public string $notifMessage   = '';

    public function sendNotification(): void
    {
        $this->validate([
            'notifPhone'   => 'required|string',
            'notifMessage' => 'required|string',
        ]);

        app(NotificationService::class)->send($this->notifPatientId, $this->notifPhone, $this->notifMessage);

        $this->showNotifModal = false;
        $this->dispatch('toast', message: 'Notificação registrada com sucesso!');
    }
}
