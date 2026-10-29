<?php

namespace App\Observers;

use App\Models\Alert;
use App\Models\User;
use App\Notifications\CriticalAlertNotification;
use Illuminate\Support\Facades\Notification;

class AlertObserver
{
    public function created(Alert $alert): void
    {
        if (in_array($alert->severity, ['high', 'critical'], true)) {
            Notification::send(
                User::where('role', 'admin')->whereNull('blocked_at')->get(),
                new CriticalAlertNotification($alert)
            );
        }
    }
}
