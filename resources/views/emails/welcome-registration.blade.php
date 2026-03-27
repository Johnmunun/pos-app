<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; padding: 20px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden;">
        <tr>
            <td style="background: #d97706; color: #fff; padding: 16px 20px; font-size: 20px; font-weight: bold;">
                Bienvenue sur OmniPOS
            </td>
        </tr>
        <tr>
            <td style="padding: 20px; color: #1f2937; font-size: 14px; line-height: 1.6;">
                <p style="margin-top: 0;">Bonjour {{ $user->name }},</p>
                <p>Votre compte a bien ete cree{{ $companyName ? ' pour '.$companyName : '' }}.</p>
                <p>Vous pouvez des maintenant vous connecter et commencer la configuration de votre boutique.</p>
                <p style="margin-bottom: 0;">Merci pour votre confiance,<br>L'equipe OmniPOS</p>
            </td>
        </tr>
    </table>
</body>
</html>

