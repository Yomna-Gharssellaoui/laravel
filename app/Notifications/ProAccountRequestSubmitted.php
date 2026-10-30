<?php

namespace App\Notifications;

use App\Models\ProAccountRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to admins when a consumer asks for a professional account */
class ProAccountRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProAccountRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Trace Verte] Nouvelle demande de compte '.mb_strtolower($this->request->roleLabel()))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->request->user->name.' ('.$this->request->user->email.') demande un compte '.mb_strtolower($this->request->roleLabel()).'.')
            ->line('Entreprise : '.$this->request->company_name.' — '.$this->request->location)
            ->action('Examiner la demande', route('admin.pro-requests.show', $this->request));
    }
}
