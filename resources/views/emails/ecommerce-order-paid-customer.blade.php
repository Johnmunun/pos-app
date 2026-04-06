<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.5; color: #1e293b; max-width: 560px; margin: 0 auto; padding: 24px;">
    <p>Bonjour {{ $customerName }},</p>
    <p>Nous avons bien enregistré le <strong>paiement</strong> de votre commande <strong>{{ $orderNumber }}</strong>.</p>

    <p style="margin: 16px 0;">
        <strong>Montant total :</strong> {{ number_format($totalAmount, 0, ',', ' ') }} {{ $currency }}
    </p>

    @if(!empty($lines))
        <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin: 16px 0;">
            <thead>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <th style="text-align: left; padding: 8px 0;">Produit</th>
                    <th style="text-align: right; padding: 8px 0;">Qté</th>
                    <th style="text-align: right; padding: 8px 0;">Sous-total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $row)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 8px 0;">{{ $row['name'] }}</td>
                        <td style="text-align: right; padding: 8px 0;">{{ $row['quantity'] }}</td>
                        <td style="text-align: right; padding: 8px 0;">{{ number_format($row['line_total'], 0, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($digitalDownloads))
        <div style="margin: 24px 0; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <p style="margin: 0 0 12px; font-weight: 600;">Téléchargements (produits numériques)</p>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($digitalDownloads as $d)
                    <li style="margin: 8px 0;">
                        <a href="{{ $d['url'] }}" style="color: #d97706;">{{ $d['name'] }}</a>
                    </li>
                @endforeach
            </ul>
            <p style="margin: 12px 0 0; font-size: 13px; color: #64748b;">Les liens sont personnels ; en cas de problème, contactez la boutique.</p>
        </div>
    @endif

    @if(!empty($successPageUrl))
        <p style="margin: 16px 0;">
            <a href="{{ $successPageUrl }}" style="display: inline-block; padding: 10px 18px; background: #0f172a; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;">Voir ma commande et mes téléchargements</a>
        </p>
    @endif

    <p style="margin-top: 24px; font-size: 13px; color: #64748b;">Merci pour votre achat.</p>
</body>
</html>
