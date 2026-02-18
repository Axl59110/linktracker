<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport — {{ $project->name }}</title>
    <style>
        /* ===== Styles généraux ===== */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #1a1a2e;
            background: #fff;
            font-size: 13px;
            line-height: 1.5;
        }

        .container { max-width: 1100px; margin: 0 auto; padding: 24px; }

        /* ===== En-tête ===== */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .report-title { font-size: 22px; font-weight: 700; color: #1e40af; }
        .report-subtitle { font-size: 14px; color: #6b7280; margin-top: 4px; }
        .report-meta { text-align: right; color: #9ca3af; font-size: 11px; }

        /* ===== Stats cards ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            text-align: center;
        }

        .stat-value { font-size: 28px; font-weight: 700; }
        .stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-top: 4px; }
        .stat-card.active .stat-value { color: #16a34a; }
        .stat-card.lost .stat-value { color: #dc2626; }
        .stat-card.changed .stat-value { color: #d97706; }
        .stat-card.total .stat-value { color: #2563eb; }

        /* ===== Section titre ===== */
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* ===== Tableau ===== */
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th {
            text-align: left;
            padding: 8px 10px;
            background: #f1f5f9;
            font-weight: 600;
            color: #374151;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 2px solid #e2e8f0;
        }
        td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        /* Badges statut */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
        }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-lost { background: #fee2e2; color: #dc2626; }
        .badge-changed { background: #fef3c7; color: #d97706; }

        .url-cell { max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .url-cell a { color: #2563eb; text-decoration: none; }

        /* ===== Footer ===== */
        .report-footer {
            margin-top: 32px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
        }

        /* ===== Bouton impression (non imprimé) ===== */
        .print-bar {
            background: #1e40af;
            color: white;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .print-bar strong { font-size: 14px; }
        .print-bar small { opacity: 0.8; }

        .btn-print {
            background: white;
            color: #1e40af;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-back {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 13px;
            margin-right: 16px;
        }

        /* ===== Impression ===== */
        @media print {
            .print-bar { display: none !important; }
            body { font-size: 11px; }
            .container { padding: 0; max-width: none; }
            .stats-grid { grid-template-columns: repeat(4, 1fr); }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    {{-- Barre d'outils (non imprimée) --}}
    <div class="print-bar">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="btn-back">← Retour au projet</a>
            <strong>Rapport : {{ $project->name }}</strong>
        </div>
        <button onclick="window.print()" class="btn-print">🖨️ Imprimer / Enregistrer PDF</button>
    </div>

    <div class="container">

        {{-- En-tête du rapport --}}
        <div class="report-header">
            <div>
                <div class="report-title">{{ $project->name }}</div>
                <div class="report-subtitle">
                    Rapport de suivi des backlinks
                    @if($project->url)
                        — <a href="{{ $project->url }}" style="color: #3b82f6;">{{ $project->url }}</a>
                    @endif
                </div>
            </div>
            <div class="report-meta">
                <div>Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</div>
                <div>LinkTracker — Rapport confidentiel</div>
            </div>
        </div>

        {{-- Stats résumé --}}
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label">Total backlinks</div>
            </div>
            <div class="stat-card active">
                <div class="stat-value">{{ $stats['active'] }}</div>
                <div class="stat-label">Actifs</div>
            </div>
            <div class="stat-card lost">
                <div class="stat-value">{{ $stats['lost'] }}</div>
                <div class="stat-label">Perdus</div>
            </div>
            <div class="stat-card changed">
                <div class="stat-value">{{ $stats['changed'] }}</div>
                <div class="stat-label">Modifiés</div>
            </div>
        </div>

        {{-- Tableau des backlinks --}}
        <h2 class="section-title">Liste des backlinks ({{ $stats['total'] }})</h2>

        @if($project->backlinks->isEmpty())
            <p style="color: #6b7280; font-style: italic;">Aucun backlink pour ce projet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>URL source</th>
                        <th>Ancre</th>
                        <th>Tier</th>
                        <th>Statut</th>
                        <th>DA</th>
                        <th>Dernière vérif.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->backlinks as $backlink)
                    @php
                        $domain = \App\Models\DomainMetric::extractDomain($backlink->source_url);
                        $metric = $domainMetrics->get($domain);
                        $da = $metric?->domain_authority ?? '—';
                    @endphp
                    <tr>
                        <td class="url-cell">
                            <a href="{{ $backlink->source_url }}" target="_blank" title="{{ $backlink->source_url }}">
                                {{ Str::limit($backlink->source_url, 55) }}
                            </a>
                        </td>
                        <td>{{ $backlink->anchor_text ?: '—' }}</td>
                        <td>{{ $backlink->tier_level === 'tier1' ? 'T1' : 'T2' }}</td>
                        <td>
                            <span class="badge badge-{{ $backlink->status }}">
                                {{ ucfirst($backlink->status) }}
                            </span>
                        </td>
                        <td>{{ $da }}</td>
                        <td>
                            {{ $backlink->last_checked_at ? $backlink->last_checked_at->format('d/m/Y') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="report-footer">
            Rapport généré automatiquement par LinkTracker • {{ $generatedAt->format('d/m/Y H:i') }} •
            {{ $stats['total'] }} backlink(s) suivis pour {{ $project->name }}
        </div>

    </div>

</body>
</html>
