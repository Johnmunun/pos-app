<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailTitle }}</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; padding: 20px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden;">
        <tr>
            <td style="background: #111827; color: #fff; padding: 16px 20px; font-size: 18px; font-weight: bold;">
                {{ $mailTitle }}
            </td>
        </tr>
        <tr>
            <td style="padding: 20px; color: #1f2937; font-size: 14px; line-height: 1.6;">
                <p style="margin: 0;">{{ $mailBody }}</p>
            </td>
        </tr>
    </table>
</body>
</html>

