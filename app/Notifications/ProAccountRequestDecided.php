<?php

namespace App\Notifications;

use App\Models\ProAccountRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to the applicant when an admin accepts or rejects the request */
class ProAccountRequestDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProAccountRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $role = mb_strtolower($this->request->roleLabel());
        $mail = (new MailMessage)->greeting('Bonjour '.$notifiable->name.',');

        if ($this->request->status === 'approved') {
            $mail->subject('[Trace Verte] Votre compte '.$role.' est activé')
                ->line('Votre demande de compte '.$role.' pour « '.$this->request->company_name.' » a été acceptée.')
                ->line('Vous avez maintenant accès au back-office pour gérer vos données.');
            if ($this->request->admin_note) {
                $mail->line('Message de l’équipe : '.$this->request->admin_note);
            }

            return $mail->action('Accéder au back-office', route('admin.dashboard'));
        }

        return $mail->subject('[Trace Verte] Votre demande de compte '.$role)
            ->line('Votre demande de compte '.$role.' pour « '.$this->request->company_name.' » n’a pas été acceptée.')
            ->line('Motif : '.$this->request->admin_note)
            ->line('Vous pouvez corriger les informations et envoyer une nouvelle demande.')
            ->action('Nouvelle demande', route('pro-account.create'));
    }
}
