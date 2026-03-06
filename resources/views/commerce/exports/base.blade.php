<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Export') - {{ $header['company_name'] ?? 'Global Commerce' }}</title>
    <style>
        @page {
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
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table tbody tr:hover {
            background-color: #e5f0ff;
        }

        .align-right { text-align: right; }
        .align-center { text-align: center; }
        .nowrap { white-space: nowrap; }

        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 9999px;
            font-size: 7px;
            font-weight: 600;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-neutral {
            background-color: #e5e7eb;
            color: #374151;
        }

        .summary-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            background-color: #f8fafc;
        }
        .summary-title {
            font-size: 9px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            margin-bottom: 2px;
        }
        .summary-label {
            color: #64748b;
        }
        .summary-value {
            font-weight: 600;
            color: #0f172a;
        }

        .text-success { color: #16a34a; }
        .text-warning { color: #d97706; }
        .text-danger { color: #b91c1c; }
        .text-muted { color: #9ca3af; }

        .font-bold { font-weight: 600; }
    </style>
</head>
<body>
    <div class="doc-header">
        <div class="doc-header-left">
            @if(!empty($header['logo_base64']))
                <img src="{{ $header['logo_base64'] }}" alt="Logo" class="logo">
            @endif
        </div>
        <div class="doc-header-right">
            <div class="company-name">{{ $header['company_name'] ?? 'Global Commerce' }}</div>
            <div class="company-info">
                @if(!empty($header['street']) || !empty($header['city']))
                    <div>{{ $header['street'] ?? '' }} {{ $header['city'] ?? '' }}</div>
                @endif
                @if(!empty($header['country']) || !empty($header['postal_code']))
                    <div>{{ $header['country'] ?? '' }} {{ $header['postal_code'] ?? '' }}</div>
                @endif
                @if(!empty($header['phone']) || !empty($header['email']))
                    <div>
                        @if(!empty($header['phone'])) Tel: {{ $header['phone'] }} @endif
                        @if(!empty($header['email'])) · Email: {{ $header['email'] }} @endif
                    </div>
                @endif
            </div>
            @if(!empty($header['id_nat']) || !empty($header['rccm']) || !empty($header['tax_number']))
                <div class="legal-ids">
                    @if(!empty($header['id_nat'])) ID NAT: {{ $header['id_nat'] }} · @endif
                    @if(!empty($header['rccm'])) RCCM: {{ $header['rccm'] }} · @endif
                    @if(!empty($header['tax_number'])) N° Impôt: {{ $header['tax_number'] }} @endif
                </div>
            @endif
        </div>
    </div>

    <div class="doc-divider"></div>

    <div class="doc-meta">
        <div class="doc-meta-left">
            <div class="doc-title">@yield('title', 'Export')</div>
            <div class="doc-subtitle">@yield('subtitle')</div>
        </div>
        <div class="doc-meta-right">
            <div class="doc-date">
                Exporté le {{ ($header['exported_at'] ?? now())->format('d/m/Y H:i') }}
            </div>
            <div class="doc-user">
                Par {{ $header['exported_by'] ?? 'Utilisateur' }}
            </div>
        </div>
    </div>

    @yield('content')
</body>
</html>

