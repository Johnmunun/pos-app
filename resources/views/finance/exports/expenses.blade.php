<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dépenses - Rapport</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .subtitle { font-size: 11px; color: #6b7280; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 6px 4px; border-bottom: 1px solid #e5e7eb; }
        th { text-align: left; background-color: #f9fafb; font-size: 10px; text-transform: uppercase; color: #4b5563; }
        .text-right { text-align: right; }
        .summary { margin-top: 12px; padding: 8px; background-color: #f9fafb; border-radius: 4px; }
        .summary-label { color: #4b5563; font-size: 11px; }
        .summary-value { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Rapport des Dépenses</h1>
    <div class="subtitle">
        @php
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;
        @endphp
        @if($from || $to)
            Période : {{ $from ?? '—' }} au {{ $to ?? '—' }}
        @else
            Période : toutes les dates
        @endif
    </div>

    <div class="summary">
        <span class="summary-label">Nombre de dépenses :</span>
        <span class="summary-value">{{ $summary['count'] ?? 0 }}</span>
        &nbsp; · &nbsp;
        <span class="summary-label">Montant total :</span>
        <span class="summary-value">
            {{ number_format($summary['total_amount'] ?? 0, 2, ',', ' ') }}
            {{ $summary['currency'] ?? 'CDF' }}
        </span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Catégorie</th>
                <th>Description</th>
                <th>Statut</th>
                <th class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item['created_at'] ?? '' }}</td>
                    <td>{{ $item['category'] ?? '' }}</td>
                    <td>{{ $item['description'] ?: '—' }}</td>
                    <td>{{ $item['status'] ?? '' }}</td>
                    <td class="text-right">
                        {{ number_format($item['amount'] ?? 0, 2, ',', ' ') }}
                        {{ $item['currency'] ?? '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

