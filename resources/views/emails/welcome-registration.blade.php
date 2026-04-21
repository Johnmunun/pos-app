<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Bienvenue</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#d97706 0%,#ea580c 100%);padding:28px 32px;text-align:center;">
                            <div style="display:inline-block;background:rgba(255,255,255,0.2);border-radius:12px;padding:10px 16px;margin-bottom:12px;">
                                <span style="color:#fff;font-weight:700;font-size:18px;letter-spacing:0.02em;">{{ config('app.name', 'OmniPOS') }}</span>
                            </div>
                            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;line-height:1.3;">
                                Bienvenue, {{ $user->name }}
                            </h1>
                            <p style="margin:12px 0 0;color:rgba(255,255,255,0.95);font-size:15px;line-height:1.5;">
                                Votre espace marchand est prêt.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.6;">
                                Merci pour votre inscription
                                @if($companyName)
                                    pour <strong>{{ $companyName }}</strong>
                                @endif
                                .
                            </p>
                            @if(!empty($storeStartMode))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;">
                                    <tr>
                                        <td style="padding:16px 20px;">
                                            <p style="margin:0 0 6px;color:#92400e;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">Mode de démarrage</p>
                                            <p style="margin:0;color:#78350f;font-size:15px;font-weight:600;">
                                                @if($storeStartMode === 'preconfigured_store')
                                                    Boutique pré-configurée — pack métier appliqué selon votre secteur.
                                                @else
                                                    Boutique vide — structure minimale, vous ajoutez vos produits.
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                            <p style="margin:0 0 24px;color:#6b7280;font-size:14px;line-height:1.6;">
                                Vous pouvez vous connecter pour finaliser la configuration, suivre la validation de votre compte et accéder à votre tableau de bord.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                <tr>
                                    <td style="border-radius:10px;background:linear-gradient(135deg,#d97706 0%,#ea580c 100%);">
                                        <a href="{{ url('/login') }}" style="display:inline-block;padding:14px 28px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;border-radius:10px;">
                                            Se connecter
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px;">
                            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;text-align:center;">
                                Cet email a été envoyé automatiquement. En cas de question, répondez à ce message ou contactez le support depuis votre espace.
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
