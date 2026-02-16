<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Export') - {{ $header['company_name'] ?? 'Pharmacie' }}</title>
    <style>
        @page {
            size: @yield('page-size', 'A4 portrait');
            margin: 15mm 12mm 20mm 12mm;
        }

        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.4;
            color: #1e293b;
            margin: 0;
        }

        /* ===== EN-TÊTE ENTREPRISE ===== */
        .doc-header {
            display: table;
            width: 100%;
            margin-bottom: 0;
        }
        .doc-header-left {
            display: table-cell;
            vertical-align: top;
            width: 22%;
        }
        .doc-header-right {
            display: table-cell;
            vertical-align: top;
            width: 78%;
            padding-left: 12px;
        }
        .logo {
            max-width: 90px;
            max-height: 45px;
            object-fit: contain;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }
        .company-info {
            font-size: 8px;
            color: #475569;
            line-height: 1.5;
        }
        .company-info div {
            margin-bottom: 1px;
        }
        .legal-ids {
            font-size: 7px;
            color: #64748b;
            margin-top: 3px;
        }

        /* ===== SÉPARATION ET MÉTADONNÉES ===== */
        .doc-divider {
            border-bottom: 2px solid #1e40af;
            margin: 10px 0 8px;
        }
        .doc-meta {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .doc-meta-left {
            display: table-cell;
            vertical-align: middle;
            width: 60%;
        }
        .doc-meta-right {
            display: table-cell;
            vertical-align: middle;
            width: 40%;
            text-align: right;
        }
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
        }
        .doc-subtitle {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-date {
            font-size: 8px;
            color: #64748b;
        }
        .doc-user {
            font-size: 8px;
            color: #475569;
            font-weight: 500;
        }

        /* ===== TABLEAU DE DONNÉES ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
            font-size: 8px;
        }
        .data-table thead th {
            background-color: #1e40af;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
            letter-spacing: 0.5px;
        }
        .data-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .data-table .align-right {
            text-align: right;
        }
        .data-table .align-center {
            text-align: center;
        }
        .data-table .nowrap {
            white-space: nowrap;
        }

        /* ===== BADGES ET STATUTS ===== */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: 600;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-neutral {
            background-color: #f1f5f9;
            color: #475569;
        }

        /* ===== RÉSUMÉ / TOTAUX ===== */
        .summary-box {
            margin-top: 12px;
            padding: 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .summary-title {
            font-size: 9px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 6px;
        }
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }
        .summary-label {
            display: table-cell;
            width: 70%;
            font-size: 8px;
            color: #64748b;
        }
        .summary-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-size: 8px;
            font-weight: 600;
            color: #1e293b;
        }
        .summary-total {
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            margin-top: 4px;
        }
        .summary-total .summary-label,
        .summary-total .summary-value {
            font-size: 10px;
            font-weight: bold;
            color: #1e40af;
        }

        /* ===== PIED DE PAGE ===== */
        .doc-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px 12mm;
            border-top: 1px solid #e2e8f0;
            font-size: 7px;
            color: #94a3b8;
            display: table;
            width: 100%;
        }
        .doc-footer-left {
            display: table-cell;
            text-align: left;
        }
        .doc-footer-center {
            display: table-cell;
            text-align: center;
        }
        .doc-footer-right {
            display: table-cell;
            text-align: right;
        }

        /* ===== UTILITAIRES ===== */
        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tr { page-break-inside: avoid; }
        .page-break { page-break-after: always; }
        .no-break { page-break-inside: avoid; }
        .text-muted { color: #94a3b8; }
        .text-success { color: #166534; }
        .text-danger { color: #dc2626; }
        .text-warning { color: #d97706; }
        .font-bold { font-weight: bold; }
        .mt-2 { margin-top: 8px; }
        .mb-2 { margin-bottom: 8px; }
    </style>
    @yield('styles')
</head>
<body>
    {{-- En-tête entreprise --}}
    <div class="doc-header">
        <div class="doc-header-left">
            @if(!empty($header['logo_url']))
                <img src="{{ $header['logo_url'] }}" alt="Logo" class="logo">
            @endif
        </div>
        <div class="doc-header-right">
            <div class="company-name">{{ $header['company_name'] ?? 'Pharmacie' }}</div>
            <div class="company-info">
                @php
                    $addressParts = array_filter([
                        $header['street'] ?? null,
                        $header['city'] ?? null,
                        $header['postal_code'] ?? null,
                        $header['country'] ?? null,
                    ]);
                @endphp
                @if(!empty($addressParts))
                    <div>{{ implode(', ', $addressParts) }}</div>
                @endif
                @if(!empty($header['phone']))
                    <div>Tél: {{ $header['phone'] }}</div>
                @endif
                @if(!empty($header['email']))
                    <div>Email: {{ $header['email'] }}</div>
                @endif
                @if(!empty($header['id_nat']) || !empty($header['rccm']) || !empty($header['tax_number']))
                    <div class="legal-ids">
                        @if(!empty($header['id_nat'])) ID NAT: {{ $header['id_nat'] }} @endif
                        @if(!empty($header['rccm'])) · RCCM: {{ $header['rccm'] }} @endif
                        @if(!empty($header['tax_number'])) · N° Tva: {{ $header['tax_number'] }} @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="doc-divider"></div>

    <div class="doc-meta">
        <div class="doc-meta-left">
            <div class="doc-title">@yield('title', 'Export')</div>
            @hasSection('subtitle')
                <div class="doc-subtitle">@yield('subtitle')</div>
            @endif
        </div>
        <div class="doc-meta-right">
            <div class="doc-date">Export du {{ ($header['exported_at'] ?? now())->format('d/m/Y à H:i') }}</div>
            @if(!empty($header['exported_by']))
                <div class="doc-user">Par: {{ $header['exported_by'] }}</div>
            @endif
        </div>
    </div>

    @yield('content')

    {{-- Pied de page --}}
    <div class="doc-footer">
        <div class="doc-footer-left">
            Module Pharmacie — {{ $header['company_name'] ?? 'Pharmacie' }}
        </div>
        <div class="doc-footer-center">
            @yield('footer-center')
        </div>
        <div class="doc-footer-right">
            Devise: {{ $header['currency'] ?? 'CDF' }}
        </div>
    </div>
</body>
</html>
