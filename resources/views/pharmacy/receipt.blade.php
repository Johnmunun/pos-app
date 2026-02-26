<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reçu #{{ $sale['id'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.3;
            margin: 0;
            padding: 12px 8px;
            color: #000;
            background: #f3f4f6;
        }
        .receipt-container {
            max-width: 100%;
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            padding: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
        .shop { font-size: 14px; margin-bottom: 4px; }
        .meta { font-size: 11px; color: #333; margin-bottom: 8px; }
        .line { display: flex; justify-content: space-between; margin: 2px 0; }
        .receipt-table { width: 100%; border-collapse: collapse; font-size: 11px; margin: 6px 0; }
        .receipt-table th, .receipt-table td { border: 1px solid #000; padding: 3px 4px; text-align: left; }
        .receipt-table th { background: #f0f0f0; font-weight: bold; }
        .receipt-table .col-name { width: 40%; max-width: 0; overflow: hidden; text-overflow: ellipsis; }
        .receipt-table .col-qty { width: 12%; text-align: center; white-space: nowrap; }
        .receipt-table .col-punit { width: 22%; text-align: right; white-space: nowrap; }
        .receipt-table .col-total { width: 26%; text-align: right; white-space: nowrap; }
        .logo-img { max-width: 100%; max-height: 36px; object-fit: contain; display: block; margin: 0 auto 6px; }
        .total-row { margin-top: 4px; padding-top: 4px; border-top: 1px solid #000; }
        @media print {
            body {
                padding: 4px;
                background: #fff;
            }
            .receipt-container {
                width: 80mm;
                max-width: 80mm;
                padding: 4px;
                box-shadow: none;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        @if(!empty($logo_url))
        <div class="center">
            <img src="{{ $logo_url }}" alt="Logo" class="logo-img">
        </div>
        @endif
        <div class="center shop bold">{{ $shop_name }}</div>
        <div class="center meta">Reçu #{{ $sale['id'] }} · {{ $sale['created_at'] }}</div>
        @if($customer)
        <div class="meta">Client: {{ $customer }}</div>
        @endif
        <div class="meta">Vendeur: {{ $sale['seller_name'] }}</div>
        <hr>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th class="col-name">Nom</th>
                    <th class="col-qty">Qté</th>
                    <th class="col-punit">P.unit</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                <tr>
                    <td class="col-name" title="{{ $line['product_name'] }}">{{ $line['product_name'] }}</td>
                    <td class="col-qty">{{ $line['quantity'] }}</td>
                    <td class="col-punit">{{ number_format($line['unit_price'], 2, ',', ' ') }} {{ $line['currency'] }}</td>
                    <td class="col-total">{{ number_format($line['line_total'], 2, ',', ' ') }} {{ $line['currency'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <hr>
        <div class="line total-row bold">
            <span>Total</span>
            <span>{{ number_format($sale['total_amount'], 2, ',', ' ') }} {{ $sale['currency'] }}</span>
        </div>
        <div class="line">
            <span>Payé</span>
            <span>{{ number_format($sale['paid_amount'], 2, ',', ' ') }} {{ $sale['currency'] }}</span>
        </div>
        @if($sale['balance_amount'] > 0)
        <div class="line">
            <span>Reste dû</span>
            <span>{{ number_format($sale['balance_amount'], 2, ',', ' ') }} {{ $sale['currency'] }}</span>
        </div>
        @endif
        <hr>
        <div class="center meta">Merci de votre achat</div>

        <p class="center no-print" style="margin-top: 16px;">
            <button type="button" onclick="window.print();" style="padding: 8px 16px; font-size: 14px; cursor: pointer;">
                Imprimer (thermique)
            </button>
        </p>
    </div>

    <script>
        // Ouverture dans un nouvel onglet : proposer l'impression (certaines imprimantes thermiques utilisent la fenêtre d'impression)
        if (window.opener) {
            window.onload = function() { window.print(); };
        }
    </script>
</body>
</html>
