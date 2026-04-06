<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande enregistree</title>
</head>
<body style="font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.5; color: #1e293b; max-width: 560px; margin: 0 auto; padding: 24px;">
    @if($recipientLabel === 'boutique')
        <p>Bonjour,</p>
        <p>Une nouvelle commande <strong>{{ $orderNumber }}</strong> a ete enregistree pour <strong>{{ $customerName }}</strong>.</p>
    @else
        <p>Bonjour {{ $customerName }},</p>
        <p>Votre commande <strong>{{ $orderNumber }}</strong> a bien ete enregistree.</p>
    @endif

    <p style="margin: 16px 0;">
        <strong>Montant total :</strong> {{ number_format($totalAmount, 0, ',', ' ') }} {{ $currency }}<br>
        <strong>Paiement :</strong> {{ $paymentStatus === 'paid' ? 'confirme' : 'en attente' }}
    </p>

    @if(!empty($note))
        <p style="margin: 16px 0; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
            {{ $note }}
        </p>
    @endif

    @if(!empty($lines))
        <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin: 16px 0;">
            <thead>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <th style="text-align: left; padding: 8px 0;">Produit</th>
                    <th style="text-align: right; padding: 8px 0;">Qte</th>
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
            <p style="margin: 0 0 12px; font-weight: 600;">Telechargements disponibles</p>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($digitalDownloads as $d)
                    <li style="margin: 8px 0;">
                        <a href="{{ $d['url'] }}" style="color: #d97706;">{{ $d['name'] }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <p style="margin-top: 24px; font-size: 13px; color: #64748b;">Merci.</p>
</body>
</html>
