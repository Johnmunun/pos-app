<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de Commande - {{ $header['company_name'] ?? 'Pharmacie' }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 4mm 3mm;
        }

        * { 
            box-sizing: border-box; 
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            background: #fff;
            width: 74mm;
            margin: 0 auto;
        }

        .center { 
            text-align: center; 
        }
        
        .right { 
            text-align: right; 
        }
        
        .left {
            text-align: left;
        }
        
        .bold { 
            font-weight: bold; 
        }
        
        hr { 
            border: none; 
            border-top: 1px dashed #000; 
            margin: 4px 0; 
        }
        
        .divider {
            border: none;
            border-top: 1px solid #000;
            margin: 4px 0;
        }

        .header {
            margin-bottom: 6px;
        }

        .company-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .company-info {
            font-size: 9px;
            color: #333;
            margin-bottom: 3px;
        }

        .doc-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 4px 0;
        }

        .meta-line {
            font-size: 10px;
            margin: 2px 0;
            display: flex;
            justify-content: space-between;
        }

        .meta-label {
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border: 1px solid #000;
            font-size: 9px;
            font-weight: bold;
            margin-top: 2px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin: 4px 0;
        }

        .table th,
        .table td {
            padding: 3px 2px;
            text-align: left;
            border-bottom: 1px dotted #666;
        }

        .table th {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            border-bottom: 1px solid #000;
        }

        .table .col-name {
            width: 45%;
            max-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .table .col-qty {
            width: 12%;
            text-align: center;
        }

        .table .col-received {
            width: 12%;
            text-align: center;
        }

        .table .col-price {
            width: 15%;
            text-align: right;
        }

        .table .col-total {
            width: 16%;
            text-align: right;
        }

        .total-section {
            margin-top: 4px;
            padding-top: 4px;
            border-top: 2px solid #000;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin: 2px 0;
        }

        .total-amount {
            font-size: 13px;
            font-weight: bold;
        }

        .footer {
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px dashed #000;
            font-size: 8px;
            text-align: center;
            color: #666;
        }

        .logo-img {
            max-width: 60px;
            max-height: 35px;
            object-fit: contain;
            display: block;
            margin: 0 auto 4px;
        }

        @media print {
            body {
                width: 74mm;
            }
        }
    </style>
</head>
<body>
    <div class="header center">
        @if(!empty($header['logo_base64']))
            <img src="{{ $header['logo_base64'] }}" alt="Logo" class="logo-img">
        @elseif(!empty($header['logo_url']))
            <img src="{{ $header['logo_url'] }}" alt="Logo" class="logo-img">
        @endif
        <div class="company-name">{{ $header['company_name'] ?? 'Pharmacie' }}</div>
        @php
            $addressParts = array_filter([
                $header['street'] ?? null,
                $header['city'] ?? null,
                $header['postal_code'] ?? null,
                $header['country'] ?? null,
            ]);
        @endphp
        @if(!empty($addressParts))
            <div class="company-info">{{ implode(', ', $addressParts) }}</div>
        @endif
        @if(!empty($header['phone']))
            <div class="company-info">Tél: {{ $header['phone'] }}</div>
        @endif
        @if(!empty($header['email']))
            <div class="company-info">Email: {{ $header['email'] }}</div>
        @endif
        @if(!empty($header['id_nat']) || !empty($header['rccm']) || !empty($header['tax_number']))
            <div class="company-info" style="font-size: 8px; margin-top: 2px;">
                @if(!empty($header['id_nat']))ID NAT: {{ $header['id_nat'] }}@endif
                @if(!empty($header['rccm'])) · RCCM: {{ $header['rccm'] }}@endif
                @if(!empty($header['tax_number'])) · TVA: {{ $header['tax_number'] }}@endif
            </div>
        @endif
    </div>

    <hr>

    <div class="center">
        <div class="doc-title">Bon de Commande</div>
        <div class="meta-line">
            <span class="meta-label">Réf:</span>
            <span>{{ $purchase_order['reference'] ?? '—' }}</span>
        </div>
    </div>

    <hr>

    <div class="meta-line">
        <span class="meta-label">Fournisseur:</span>
        <span>{{ $purchase_order['supplier_name'] ?? '—' }}</span>
    </div>
    @if(!empty($purchase_order['supplier_phone']))
        <div class="meta-line">
            <span class="meta-label">Tél:</span>
            <span>{{ $purchase_order['supplier_phone'] }}</span>
        </div>
    @endif

    <div class="meta-line">
        <span class="meta-label">Date:</span>
        <span>{{ $purchase_order['ordered_at'] ?? $purchase_order['created_at'] ?? '—' }}</span>
    </div>

    @if(!empty($purchase_order['expected_at']))
        <div class="meta-line">
            <span class="meta-label">Livraison:</span>
            <span>{{ $purchase_order['expected_at'] }}</span>
        </div>
    @endif

    @if(!empty($purchase_order['received_at']))
        <div class="meta-line">
            <span class="meta-label">Reçu le:</span>
            <span>{{ $purchase_order['received_at'] }}</span>
        </div>
    @endif

    <div class="meta-line">
        <span class="meta-label">Statut:</span>
        <span class="status-badge">{{ $purchase_order['status_label'] ?? $purchase_order['status'] }}</span>
    </div>

    <hr>

    <table class="table">
        <thead>
            <tr>
                <th class="col-name">Produit</th>
                <th class="col-qty">Cmd</th>
                <th class="col-received">Reçu</th>
                <th class="col-price">P.unit</th>
                <th class="col-total">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $line)
                <tr>
                    <td class="col-name" title="{{ $line['product_name'] ?? '—' }}">
                        {{ $line['product_name'] ?? '—' }}
                        @if(!empty($line['product_code']))
                            <br><span style="font-size: 8px; color: #666;">{{ $line['product_code'] }}</span>
                        @endif
                    </td>
                    <td class="col-qty">{{ number_format($line['ordered_quantity'] ?? 0, 0, ',', ' ') }}</td>
                    <td class="col-received">
                        @php
                            $received = $line['received_quantity'] ?? 0;
                            $ordered = $line['ordered_quantity'] ?? 0;
                        @endphp
                        {{ number_format($received, 0, ',', ' ') }}
                    </td>
                    <td class="col-price">{{ number_format($line['unit_cost'] ?? 0, 0, ',', ' ') }}</td>
                    <td class="col-total bold">{{ number_format($line['line_total'] ?? 0, 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center" style="padding: 8px; color: #666;">Aucune ligne</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-line">
            <span class="bold">TOTAL</span>
            <span class="total-amount">{{ number_format($purchase_order['total_amount'] ?? 0, 0, ',', ' ') }} {{ $purchase_order['currency'] ?? 'CDF' }}</span>
        </div>
    </div>

    <hr>

    <div class="footer">
        {{ $header['company_name'] ?? 'Pharmacie' }}<br>
        Généré le {{ ($header['exported_at'] ?? now())->format('d/m/Y H:i') }}
        @if(!empty($header['exported_by']))
            <br>Par: {{ $header['exported_by'] }}
        @endif
    </div>
</body>
</html>
