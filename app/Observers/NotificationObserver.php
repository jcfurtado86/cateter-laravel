<?php

namespace App\Observers;

use App\Helpers\AuditHelper;
use App\Models\Notification;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        AuditHelper::logAction(
            'notification.sent',
            $notification,
            null,
            $notification->only(['phone', 'type', 'message', 'status'])
        );
    }
}
