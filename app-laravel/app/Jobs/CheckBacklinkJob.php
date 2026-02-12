<?php

namespace App\Jobs;

use App\Models\Backlink;
use App\Services\Backlink\BacklinkCheckerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckBacklinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Le nombre de fois que le job peut être tenté.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Le nombre de secondes avant timeout du job.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Le backlink à vérifier.
     *
     * @var Backlink
     */
    public Backlink $backlink;

    /**
     * Create a new job instance.
     */
    public function __construct(Backlink $backlink)
    {
        $this->backlink = $backlink;
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(BacklinkCheckerService $checkerService): void
    {
        Log::info('Starting backlink check', [
            'backlink_id' => $this->backlink->id,
            'source_url' => $this->backlink->source_url,
        ]);

        try {
            // 1. Vérifier le backlink avec le service
            $result = $checkerService->check($this->backlink);

            // 2. Créer un enregistrement BacklinkCheck avec les résultats
            $check = $this->backlink->checks()->create([
                'checked_at' => now(),
                'is_present' => $result['is_present'],
                'http_status' => $result['http_status'],
                'error_message' => $result['error_message'],
            ]);

            // 3. Mettre à jour le backlink avec les nouvelles données
            $updateData = [
                'last_checked_at' => now(),
            ];

            // Si le backlink est trouvé, mettre à jour les infos extraites
            if ($result['is_present']) {
                // Mettre à jour anchor_text seulement s'il a changé
                if ($result['anchor_text'] !== null && $result['anchor_text'] !== $this->backlink->anchor_text) {
                    $updateData['anchor_text'] = $result['anchor_text'];
                }

                $updateData['rel_attributes'] = $result['rel_attributes'];
                $updateData['is_dofollow'] = $result['is_dofollow'];
                $updateData['http_status'] = $result['http_status'];

                // Si le backlink était perdu, le remettre en actif
                if ($this->backlink->status === 'lost') {
                    $updateData['status'] = 'active';
                    Log::info('Backlink retrouvé - changement de statut lost → active', [
                        'backlink_id' => $this->backlink->id,
                    ]);
                } elseif ($this->backlink->status === 'active') {
                    // Vérifier si les attributs ont changé
                    if ($this->hasAttributesChanged($result)) {
                        $updateData['status'] = 'changed';
                        Log::info('Attributs du backlink modifiés - changement de statut active → changed', [
                            'backlink_id' => $this->backlink->id,
                        ]);
                    }
                }
            } else {
                // Backlink non trouvé
                if ($this->backlink->status !== 'lost') {
                    $updateData['status'] = 'lost';
                    Log::warning('Backlink non trouvé - changement de statut → lost', [
                        'backlink_id' => $this->backlink->id,
                        'error_message' => $result['error_message'],
                    ]);
                }
            }

            $this->backlink->update($updateData);

            Log::info('Backlink check completed successfully', [
                'backlink_id' => $this->backlink->id,
                'check_id' => $check->id,
                'is_present' => $result['is_present'],
                'status' => $this->backlink->fresh()->status,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check backlink', [
                'backlink_id' => $this->backlink->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Créer un check avec erreur
            $this->backlink->checks()->create([
                'checked_at' => now(),
                'is_present' => false,
                'http_status' => null,
                'error_message' => 'Job failed: ' . $e->getMessage(),
            ]);

            // Re-lancer l'exception pour que Laravel gère les retries
            throw $e;
        }
    }

    /**
     * Vérifie si les attributs du backlink ont changé
     *
     * @param array $result
     * @return bool
     */
    protected function hasAttributesChanged(array $result): bool
    {
        // Comparer rel_attributes
        if ($this->backlink->rel_attributes !== $result['rel_attributes']) {
            return true;
        }

        // Comparer is_dofollow
        if ($this->backlink->is_dofollow !== $result['is_dofollow']) {
            return true;
        }

        return false;
    }

    /**
     * Gère l'échec du job après toutes les tentatives.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckBacklinkJob failed after all retries', [
            'backlink_id' => $this->backlink->id,
            'exception' => $exception->getMessage(),
        ]);

        // Optionnel : envoyer une notification à l'admin
    }
}
