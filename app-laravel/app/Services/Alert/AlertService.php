<?php

namespace App\Services\Alert;

use App\Jobs\SendWebhookJob;
use App\Models\Alert;
use App\Models\Backlink;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * Crée une alerte pour un backlink perdu.
     *
     * @param Backlink $backlink
     * @param string|null $reason
     * @return Alert
     */
    public function createBacklinkLostAlert(Backlink $backlink, ?string $reason = null): Alert
    {
        $severity = $this->determineSeverityForLost($backlink);

        $alert = Alert::create([
            'backlink_id' => $backlink->id,
            'type' => Alert::TYPE_BACKLINK_LOST,
            'severity' => $severity,
            'title' => "Backlink perdu sur {$this->extractDomain($backlink->source_url)}",
            'message' => $this->generateLostMessage($backlink, $reason),
            'metadata' => [
                'previous_status' => 'active',
                'new_status' => 'lost',
                'source_url' => $backlink->source_url,
                'target_url' => $backlink->target_url,
                'anchor_text' => $backlink->anchor_text,
                'project_id' => $backlink->project_id,
                'project_name' => $backlink->project?->name,
                'tier_level' => $backlink->tier_level,
                'reason' => $reason,
            ],
        ]);

        Log::info('Alert created: Backlink lost', [
            'alert_id' => $alert->id,
            'backlink_id' => $backlink->id,
            'severity' => $severity,
        ]);

        $this->dispatchWebhookIfConfigured($alert, $backlink);

        return $alert;
    }

    /**
     * Crée une alerte pour un backlink modifié.
     *
     * @param Backlink $backlink
     * @param array $changes
     * @return Alert
     */
    public function createBacklinkChangedAlert(Backlink $backlink, array $changes): Alert
    {
        $severity = $this->determineSeverityForChanged($backlink, $changes);

        $alert = Alert::create([
            'backlink_id' => $backlink->id,
            'type' => Alert::TYPE_BACKLINK_CHANGED,
            'severity' => $severity,
            'title' => "Backlink modifié sur {$this->extractDomain($backlink->source_url)}",
            'message' => $this->generateChangedMessage($backlink, $changes),
            'metadata' => [
                'previous_status' => 'active',
                'new_status' => 'changed',
                'source_url' => $backlink->source_url,
                'target_url' => $backlink->target_url,
                'project_id' => $backlink->project_id,
                'project_name' => $backlink->project?->name,
                'tier_level' => $backlink->tier_level,
                'changes' => $changes,
            ],
        ]);

        Log::info('Alert created: Backlink changed', [
            'alert_id' => $alert->id,
            'backlink_id' => $backlink->id,
            'severity' => $severity,
            'changes' => array_keys($changes),
        ]);

        $this->dispatchWebhookIfConfigured($alert, $backlink);

        return $alert;
    }

    /**
     * Crée une alerte pour un backlink récupéré.
     *
     * @param Backlink $backlink
     * @return Alert
     */
    public function createBacklinkRecoveredAlert(Backlink $backlink): Alert
    {
        $alert = Alert::create([
            'backlink_id' => $backlink->id,
            'type' => Alert::TYPE_BACKLINK_RECOVERED,
            'severity' => Alert::SEVERITY_LOW,
            'title' => "Backlink récupéré sur {$this->extractDomain($backlink->source_url)}",
            'message' => $this->generateRecoveredMessage($backlink),
            'metadata' => [
                'previous_status' => 'lost',
                'new_status' => 'active',
                'source_url' => $backlink->source_url,
                'target_url' => $backlink->target_url,
                'anchor_text' => $backlink->anchor_text,
                'project_id' => $backlink->project_id,
                'project_name' => $backlink->project?->name,
                'tier_level' => $backlink->tier_level,
            ],
        ]);

        Log::info('Alert created: Backlink recovered', [
            'alert_id' => $alert->id,
            'backlink_id' => $backlink->id,
        ]);

        $this->dispatchWebhookIfConfigured($alert, $backlink);

        return $alert;
    }

    /**
     * Détermine la sévérité pour un backlink perdu.
     *
     * @param Backlink $backlink
     * @return string
     */
    protected function determineSeverityForLost(Backlink $backlink): string
    {
        // Tier 1 = plus important que Tier 2
        if ($backlink->tier_level === 'tier1') {
            return Alert::SEVERITY_CRITICAL;
        }

        // Backlinks payants = important
        if ($backlink->price && $backlink->price > 0) {
            return Alert::SEVERITY_HIGH;
        }

        return Alert::SEVERITY_MEDIUM;
    }

    /**
     * Détermine la sévérité pour un backlink modifié.
     *
     * @param Backlink $backlink
     * @param array $changes
     * @return string
     */
    protected function determineSeverityForChanged(Backlink $backlink, array $changes): string
    {
        // Si le lien est devenu nofollow -> critique
        if (isset($changes['is_dofollow']) && !$changes['is_dofollow']['new']) {
            return Alert::SEVERITY_CRITICAL;
        }

        // Si l'ancre a changé -> haute sévérité
        if (isset($changes['anchor_text'])) {
            return Alert::SEVERITY_HIGH;
        }

        // Si tier1 -> medium, tier2 -> low
        if ($backlink->tier_level === 'tier1') {
            return Alert::SEVERITY_MEDIUM;
        }

        return Alert::SEVERITY_LOW;
    }

    /**
     * Génère le message pour un backlink perdu.
     *
     * @param Backlink $backlink
     * @param string|null $reason
     * @return string
     */
    protected function generateLostMessage(Backlink $backlink, ?string $reason): string
    {
        $project = $backlink->project?->name ?? 'Projet inconnu';
        $domain = $this->extractDomain($backlink->source_url);
        $anchor = $backlink->anchor_text ?? 'sans ancre';

        $message = "Le backlink vers le projet \"{$project}\" n'a pas été trouvé sur {$domain}.";
        $message .= "\n\nAncre : {$anchor}";

        if ($reason) {
            $message .= "\n\nRaison : {$reason}";
        }

        return $message;
    }

    /**
     * Génère le message pour un backlink modifié.
     *
     * @param Backlink $backlink
     * @param array $changes
     * @return string
     */
    protected function generateChangedMessage(Backlink $backlink, array $changes): string
    {
        $project = $backlink->project?->name ?? 'Projet inconnu';
        $domain = $this->extractDomain($backlink->source_url);

        $message = "Le backlink vers le projet \"{$project}\" sur {$domain} a été modifié.";
        $message .= "\n\nModifications détectées :";

        foreach ($changes as $field => $change) {
            $label = $this->getFieldLabel($field);
            $old = $change['old'] ?? 'N/A';
            $new = $change['new'] ?? 'N/A';

            $message .= "\n- {$label} : {$old} → {$new}";
        }

        return $message;
    }

    /**
     * Génère le message pour un backlink récupéré.
     *
     * @param Backlink $backlink
     * @return string
     */
    protected function generateRecoveredMessage(Backlink $backlink): string
    {
        $project = $backlink->project?->name ?? 'Projet inconnu';
        $domain = $this->extractDomain($backlink->source_url);
        $anchor = $backlink->anchor_text ?? 'sans ancre';

        $message = "Bonne nouvelle ! Le backlink vers le projet \"{$project}\" a été retrouvé sur {$domain}.";
        $message .= "\n\nAncre : {$anchor}";

        return $message;
    }

    /**
     * Extrait le domaine d'une URL.
     *
     * @param string $url
     * @return string
     */
    protected function extractDomain(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? $url;
    }

    /**
     * Retourne le label d'un champ pour l'affichage.
     *
     * @param string $field
     * @return string
     */
    protected function getFieldLabel(string $field): string
    {
        return match($field) {
            'anchor_text' => 'Ancre',
            'is_dofollow' => 'Dofollow',
            'rel_attributes' => 'Attributs rel',
            'http_status' => 'Statut HTTP',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    /**
     * Dispatche le webhook si l'utilisateur en a configuré un pour ce type d'alerte.
     */
    protected function dispatchWebhookIfConfigured(Alert $alert, Backlink $backlink): void
    {
        $user = $backlink->createdBy;

        if (!$user || !$user->webhook_url) {
            return;
        }

        $subscribedEvents = $user->webhook_events ?? [];

        if (!empty($subscribedEvents) && !in_array($alert->type, $subscribedEvents)) {
            return;
        }

        SendWebhookJob::dispatch($alert, $user)->onQueue('default');
    }

    /**
     * Marque toutes les alertes d'un backlink comme lues.
     *
     * @param Backlink $backlink
     * @return int
     */
    public function markBacklinkAlertsAsRead(Backlink $backlink): int
    {
        return Alert::where('backlink_id', $backlink->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Supprime les anciennes alertes lues (nettoyage).
     *
     * @param int $daysOld
     * @return int
     */
    public function cleanupOldAlerts(int $daysOld = 30): int
    {
        $deleted = Alert::where('is_read', true)
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();

        Log::info('Cleaned up old alerts', [
            'days_old' => $daysOld,
            'deleted_count' => $deleted,
        ]);

        return $deleted;
    }
}
