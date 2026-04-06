<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Prochaine étape</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);padding:28px 32px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;line-height:1.3;">
                                Débloquez l’accès complet
                            </h1>
                            <p style="margin:12px 0 0;color:rgba(255,255,255,0.95);font-size:14px;line-height:1.5;">
                                Bonjour {{ $user->name }},
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">
                                Pour commencer à utiliser {{ config('app.name', 'OmniPOS') }} en conditions réelles (caisse, stock, modules selon votre formule), vous devez souscrire à un plan et finaliser le paiement.
                            </p>
                            @if(!empty($companyName))
                                <p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">
                                    Compte associé à <strong>{{ $companyName }}</strong>.
                                </p>
                            @endif
                            <p style="margin:0 0 24px;color:#6b7280;font-size:14px;line-height:1.6;">
                                Jusqu’à cette étape, votre accès peut rester limité (validation, paiement ou configuration). Cliquez sur le bouton ci-dessous pour choisir votre formule et activer votre espace.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                <tr>
                                    <td style="border-radius:10px;background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);">
                                        <a href="{{ $paymentUrl }}" style="display:inline-block;padding:14px 28px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:10px;">
                                            Choisir une formule et payer
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px;">
                            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;text-align:center;">
                                Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
                                <span style="word-break:break-all;color:#6b7280;">{{ $paymentUrl }}</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;color:#9ca3af;font-size:11px;text-align:center;">
                                © {{ date('Y') }} {{ config('app.name', 'OmniPOS') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
