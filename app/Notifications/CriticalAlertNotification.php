<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CriticalAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Alert $alert)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Trace Verte] Alerte '.$this->alert->severity.' : '.$this->alert->title)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Une alerte de sévérité « '.$this->alert->severity.' » vient d’être ouverte.')
            ->line('Produit : '.($this->alert->product?->name ?? '—'))
            ->line($this->alert->description ?? '')
            ->action('Voir l’alerte', route('admin.alerts.show', $this->alert));
    }
}
