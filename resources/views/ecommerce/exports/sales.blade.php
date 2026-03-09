<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des ventes E-commerce</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #1e40af; color: white; }
        .summary { margin: 15px 0; padding: 10px; background: #f8fafc; border-radius: 4px; }
        .align-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Rapport des ventes E-commerce</h1>
    <p>{{ $shop->name ?? 'Boutique' }} – Période du {{ $from->format('d/m/Y') }} au {{ $to->format('d/m/Y') }}</p>

    <div class="summary">
        <strong>Résumé :</strong> {{ $orders->count() }} commande(s) · Revenus (payés) : {{ number_format($revenue, 2, ',', ' ') }} {{ $currency ?? ($shop?->currency ?? 'CDF') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>N° Commande</th>
                <th>Client</th>
                <th class="align-right">Total</th>
                <th>Statut</th>
                <th>Paiement</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $o)
                <tr>
                    <td>{{ $o->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $o->order_number }}</td>
                    <td>{{ $o->customer_name }}</td>
                    <td class="align-right">{{ number_format($o->total_amount ?? 0, 2, ',', ' ') }} {{ $o->currency ?? ($currency ?? 'CDF') }}</td>
                    <td>{{ $o->status }}</td>
                    <td>{{ $o->payment_status }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Aucune commande.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 20px; font-size: 8px; color: #64748b;">
        Exporté le {{ now()->format('d/m/Y H:i') }} · E-commerce POS
    </p>
</body>
</html>
