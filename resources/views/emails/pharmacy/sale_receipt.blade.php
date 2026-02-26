<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu #{{ $saleId }}</title>
</head>
<body>
    <p>Bonjour {{ $customerName ?? 'client' }},</p>

    <p>
        Vous trouverez ci-dessous le récapitulatif de votre achat chez
        <strong>{{ $shopName }}</strong>.
    </p>

    <p>
        <strong>Reçu n°:</strong> {{ $saleId }}<br>
        <strong>Date:</strong> {{ $saleDate }}<br>
        <strong>Total:</strong> {{ number_format($totalAmount, 2, ',', ' ') }} {{ $currency }}
    </p>

    <p>
        Pour consulter le reçu détaillé ou l'imprimer, cliquez sur le lien suivant :<br>
        <a href="{{ $receiptUrl }}" target="_blank" rel="noopener noreferrer">
            Voir mon reçu
        </a>
    </p>

    <p>
        Merci pour votre confiance.
    </p>
</body>
</html>

