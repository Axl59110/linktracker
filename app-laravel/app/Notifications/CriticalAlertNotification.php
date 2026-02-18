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

    public function __construct(public readonly Alert $alert)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $backlink = $this->alert->backlink;
        $project  = $backlink?->project;

        $severity = strtoupper($this->alert->severity);

        return (new MailMessage())
            ->subject("[{$severity}] {$this->alert->title}")
            ->greeting('Alerte LinkTracker')
            ->line("**{$this->alert->title}**")
            ->line("Projet : {$project?->name}")
            ->line("URL source : {$backlink?->source_url}")
            ->when($backlink?->target_url, fn($m) => $m->line("URL cible : {$backlink->target_url}"))
            ->line("Sévérité : {$severity}")
            ->action('Voir l\'alerte', route('alerts.index'))
            ->line('Vous recevez cet email car les notifications d\'alertes critiques sont activées dans vos paramètres.');
    }
}
