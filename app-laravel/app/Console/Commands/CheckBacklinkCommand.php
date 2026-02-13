<?php

namespace App\Console\Commands;

use App\Models\Backlink;
use App\Services\Backlink\BacklinkCheckerService;
use App\Services\Alert\AlertService;
use Illuminate\Console\Command;

class CheckBacklinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-backlink
                            {id : The ID of the backlink to check}
                            {--verbose : Display detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check a single backlink manually and display results in real-time';

    /**
     * Execute the console command.
     */
    public function handle(BacklinkCheckerService $checkerService, AlertService $alertService)
    {
        $backlinkId = $this->argument('id');
        $verbose = $this->option('verbose');

        // Récupérer le backlink
        $backlink = Backlink::with('project')->find($backlinkId);

        if (!$backlink) {
            $this->error("❌ Backlink with ID {$backlinkId} not found.");
            return 1;
        }

        $this->info("🔍 Checking backlink #{$backlink->id}");
        $this->line("   Project: {$backlink->project?->name}");
        $this->line("   Source URL: {$backlink->source_url}");
        $this->line("   Target URL: {$backlink->target_url}");
        $this->newLine();

        try {
            // Vérifier le backlink
            $this->info("⏳ Fetching and analyzing page...");
            $result = $checkerService->check($backlink);

            // Afficher les résultats
            $this->newLine();
            $this->info("📊 Check Results:");
            $this->line("─────────────────────────────────────────");

            // Statut HTTP
            if ($result['http_status']) {
                $statusColor = $result['http_status'] >= 200 && $result['http_status'] < 300 ? 'green' : 'red';
                $this->line("   HTTP Status: <fg={$statusColor}>{$result['http_status']}</>");
            }

            // Présence du backlink
            if ($result['is_present']) {
                $this->line("   Backlink Found: <fg=green>✓ YES</>");
            } else {
                $this->line("   Backlink Found: <fg=red>✗ NO</>");
            }

            // Détails
            if ($result['is_present']) {
                $this->line("   Anchor Text: " . ($result['anchor_text'] ?: '<none>'));
                $this->line("   Rel Attributes: " . ($result['rel_attributes'] ?: '<none>'));
                $this->line("   Dofollow: " . ($result['is_dofollow'] ? '<fg=green>Yes</>' : '<fg=yellow>No (nofollow)</>'));
            } else {
                $this->line("   Error: <fg=red>{$result['error_message']}</>");
            }

            $this->line("─────────────────────────────────────────");
            $this->newLine();

            // Sauvegarder dans la base de données
            $this->info("💾 Saving check results...");

            $check = $backlink->checks()->create([
                'checked_at' => now(),
                'is_present' => $result['is_present'],
                'http_status' => $result['http_status'],
                'error_message' => $result['error_message'],
            ]);

            // Mettre à jour le backlink
            $updateData = [
                'last_checked_at' => now(),
            ];

            $oldStatus = $backlink->status;

            if ($result['is_present']) {
                if ($result['anchor_text'] !== null && $result['anchor_text'] !== $backlink->anchor_text) {
                    $updateData['anchor_text'] = $result['anchor_text'];
                    if ($verbose) {
                        $this->comment("   → Anchor text updated: {$backlink->anchor_text} → {$result['anchor_text']}");
                    }
                }

                $updateData['rel_attributes'] = $result['rel_attributes'];
                $updateData['is_dofollow'] = $result['is_dofollow'];
                $updateData['http_status'] = $result['http_status'];

                // Détection des changements de statut
                if ($backlink->status === 'lost') {
                    $updateData['status'] = 'active';
                    $this->info("   📈 Status changed: lost → active (backlink recovered!)");
                    $alertService->createBacklinkRecoveredAlert($backlink);
                } elseif ($backlink->status === 'active') {
                    $changes = $this->getAttributesChanges($backlink, $result);
                    if (!empty($changes)) {
                        $updateData['status'] = 'changed';
                        $this->warn("   ⚠️  Status changed: active → changed (attributes modified)");
                        if ($verbose) {
                            foreach ($changes as $field => $change) {
                                $this->line("      - {$field}: {$change['old']} → {$change['new']}");
                            }
                        }
                        $alertService->createBacklinkChangedAlert($backlink, $changes);
                    }
                }
            } else {
                if ($backlink->status !== 'lost') {
                    $updateData['status'] = 'lost';
                    $this->error("   📉 Status changed: {$backlink->status} → lost (backlink not found!)");
                    $alertService->createBacklinkLostAlert($backlink, $result['error_message']);
                }
            }

            $backlink->update($updateData);

            $this->newLine();
            $this->info("✅ Check completed successfully!");
            $this->line("   Check ID: #{$check->id}");
            $this->line("   Backlink Status: {$backlink->fresh()->status}");

            if ($oldStatus !== $backlink->fresh()->status) {
                $this->warn("   ⚡ Alert created due to status change");
            }

            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Check failed!");
            $this->error("   Error: {$e->getMessage()}");

            if ($verbose) {
                $this->newLine();
                $this->error("Stack trace:");
                $this->line($e->getTraceAsString());
            }

            // Créer un check avec erreur
            $backlink->checks()->create([
                'checked_at' => now(),
                'is_present' => false,
                'http_status' => null,
                'error_message' => 'Command failed: ' . $e->getMessage(),
            ]);

            return 1;
        }
    }

    /**
     * Récupère les changements d'attributs du backlink
     *
     * @param Backlink $backlink
     * @param array $result
     * @return array
     */
    protected function getAttributesChanges(Backlink $backlink, array $result): array
    {
        $changes = [];

        if ($result['anchor_text'] !== null && $backlink->anchor_text !== $result['anchor_text']) {
            $changes['anchor_text'] = [
                'old' => $backlink->anchor_text,
                'new' => $result['anchor_text'],
            ];
        }

        if ($backlink->rel_attributes !== $result['rel_attributes']) {
            $changes['rel_attributes'] = [
                'old' => $backlink->rel_attributes,
                'new' => $result['rel_attributes'],
            ];
        }

        if ($backlink->is_dofollow !== $result['is_dofollow']) {
            $changes['is_dofollow'] = [
                'old' => $backlink->is_dofollow ? 'Oui' : 'Non',
                'new' => $result['is_dofollow'] ? 'Oui' : 'Non',
            ];
        }

        return $changes;
    }
}
