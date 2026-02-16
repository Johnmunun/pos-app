<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Produits - {{ $header['company_name'] ?? 'Boutique' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 14mm 18mm 14mm;
        }

        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #1e293b;
            margin: 0;
        }

        /* En-tête boutique professionnel */
        .doc-header {
            display: table;
            width: 100%;
            margin-bottom: 0;
        }
        .doc-header-left {
            display: table-cell;
            vertical-align: top;
            width: 28%;
        }
        .doc-header-right {
            display: table-cell;
            vertical-align: top;
            width: 72%;
            padding-left: 16px;
        }
        .logo {
            max-width: 100px;
            max-height: 50px;
            object-fit: contain;
        }
        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }
        .company-info {
            font-size: 9px;
            color: #475569;
            line-height: 1.6;
        }
        .company-info div {
            margin-bottom: 2px;
        }
        .legal-ids {
            font-size: 8px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Séparation et métadonnées */
        .doc-divider {
            border-bottom: 1px solid #cbd5e1;
            margin: 12px 0 10px;
        }
        .doc-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .doc-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .doc-date {
            font-size: 9px;
            color: #64748b;
        }

        /* Tableau produits */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .products-table th,
        .products-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            text-align: left;
            font-size: 9px;
        }
        .products-table thead th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
        }
        .products-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .products-table tbody tr:hover td {
            background-color: #f1f5f9;
        }
        .products-table .align-right {
            text-align: right;
        }
        .products-table .align-center {
            text-align: center;
        }

        /* Badges statut */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 600;
        }
        .badge-active {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Pied de page */
        .doc-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
            text-align: right;
        }

        thead { display: table-header-group; }
        tbody { display: table-row-group; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    {{-- En-tête boutique --}}
    <div class="doc-header">
        <div class="doc-header-left">
            @if(!empty($header['logo_url']))
                <img src="{{ $header['logo_url'] }}" alt="Logo" class="logo">
            @endif
        </div>
        <div class="doc-header-right">
            <div class="company-name">{{ $header['company_name'] ?? 'Boutique' }}</div>
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
        <span class="doc-title">Liste des produits</span>
        <span class="doc-date">Export du {{ ($header['exported_at'] ?? now())->format('d/m/Y à H:i') }}</span>
    </div>

    <table class="products-table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Code</th>
                <th>Catégorie</th>
                <th>Unité</th>
                <th class="align-right">Prix de vente</th>
                <th class="align-right">Prix de revient</th>
                <th class="align-right">Stock</th>
                <th class="align-center">Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product['name'] }}</td>
                    <td>{{ $product['code'] }}</td>
                    <td>{{ $product['category'] ?? '—' }}</td>
                    <td>{{ $product['unit'] ?? '—' }}</td>
                    <td class="align-right">
                        {{ number_format($product['price_amount'] ?? 0, 2) }} {{ $product['price_currency'] ?? 'USD' }}
                    </td>
                    <td class="align-right">
                        @if(!is_null($product['cost_amount']) && $product['cost_amount'] > 0)
                            {{ number_format($product['cost_amount'], 2) }} {{ $product['price_currency'] ?? 'USD' }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="align-right">{{ $product['stock'] ?? 0 }}</td>
                    <td class="align-center">
                        @if(!empty($product['is_active']))
                            <span class="badge badge-active">Actif</span>
                        @else
                            <span class="badge badge-inactive">Inactif</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 16px;">Aucun produit trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="doc-footer">
        Document généré par le module Pharmacie — {{ $header['company_name'] ?? 'Boutique' }}
    </div>
</body>
</html>
