<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Finance</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .subtitle { font-size: 11px; color: #6b7280; margin-bottom: 12px; }
        .summary-grid { display: table; width: 100%; margin-bottom: 10px; }
        .summary-box { display: table-cell; width: 25%; padding-right: 8px; padding-bottom: 8px; }
        .summary-title { font-size: 11px; text-transform: uppercase; color: #4b5563; margin-bottom: 4px; }
        .summary-value { font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $d = $dashboard ?? [];
        $currency = $d['currency'] ?? 'CDF';
        $from = $filters['from'] ?? $d['period_from'] ?? null;
        $to = $filters['to'] ?? $d['period_to'] ?? null;
    @endphp

    <h1>Dashboard Finance</h1>
    <div class="subtitle">
        @if($from || $to)
            Période : {{ $from ?? '—' }} au {{ $to ?? '—' }}
        @else
            Période : toutes les dates
        @endif
    </div>

    <div class="summary-grid">
        <div class="summary-box">
            <div class="summary-title">Total revenus</div>
            <div class="summary-value">
                {{ number_format($d['total_revenue'] ?? 0, 2, ',', ' ') }} {{ $currency }}
            </div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Total dépenses</div>
            <div class="summary-value">
                {{ number_format($d['total_expenses'] ?? 0, 2, ',', ' ') }} {{ $currency }}
            </div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Bénéfice brut</div>
            <div class="summary-value">
                {{ number_format($d['gross_profit'] ?? 0, 2, ',', ' ') }} {{ $currency }}
            </div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Marge bénéficiaire</div>
            <div class="summary-value">
                {{ number_format($d['margin_percent'] ?? 0, 2, ',', ' ') }} %
            </div>
        </div>
    </div>

    <div class="summary-grid" style="margin-top: 6px;">
        <div class="summary-box">
            <div class="summary-title">Dettes clients</div>
            <div class="summary-value">
                {{ number_format($d['debts_client_total'] ?? 0, 2, ',', ' ') }} {{ $currency }}
            </div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Dettes fournisseurs</div>
            <div class="summary-value">
                {{ number_format($d['debts_supplier_total'] ?? 0, 2, ',', ' ') }} {{ $currency }}
            </div>
        </div>
    </div>
</body>
</html>

