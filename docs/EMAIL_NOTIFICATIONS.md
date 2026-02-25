# Notifications par email

L'application envoie des alertes par email pour la pharmacie (expirations des lots et stock faible).  
Elle utilise aussi l’email pour **mot de passe oublié** (lien de réinitialisation).

## Configuration mail (.env)

Pour envoyer de vrais emails, configurez dans `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votredomaine.com
MAIL_FROM_NAME="${APP_NAME}"
```
token=e75fd674677bba422685402b76f8ba18
En développement sans SMTP, les emails sont écrits dans les logs :

```env
MAIL_MAILER=log
```

**Mailtrap** (dev/test) : créez un inbox sur [mailtrap.io](https://mailtrap.io), récupérez les identifiants SMTP dans l’inbox, puis dans `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxx
MAIL_PASSWORD=xxx
MAIL_FROM_ADDRESS=noreply@votredomaine.local
MAIL_FROM_NAME="${APP_NAME}"
```

Les emails (réinitialisation mot de passe, alertes pharmacie) apparaîtront dans Mailtrap sans être envoyés en vrai.

Consultez `config/mail.php` pour les autres options (Mailgun, Postmark, SES, Resend, etc.).

## Types de notifications

| Notification        | Description                                                                 | Destinataires |
|---------------------|-----------------------------------------------------------------------------|----------------|
| **Expiration lots** | Lots expirés ou expirant dans les 30 jours                                 | Utilisateurs du tenant avec `pharmacy.expiration.view` ou `pharmacy.batch.view`, ou ROOT |
| **Stock faible**    | Produits dont le stock ≤ minimum défini                                    | Utilisateurs du tenant avec permission stock / produit, ou ROOT |

## Commandes Artisan

- **Alertes expirations** : `php artisan pharmacy:send-expiration-alerts`
- **Alertes stock faible** : `php artisan pharmacy:send-low-stock-alerts`

Vous pouvez les lancer manuellement pour tester.

## Planification (cron)

Pour exécuter les alertes automatiquement chaque jour, ajoutez dans la crontab du serveur :

```bash
* * * * * cd /chemin/vers/pos-app && php artisan schedule:run >> /dev/null 2>&1
```

Par défaut, les alertes sont programmées dans `routes/console.php` :

- Expirations : tous les jours à 07:00
- Stock faible : tous les jours à 07:15

Pour modifier l’heure, éditez `routes/console.php` (ex. `->dailyAt('08:00')`).

## File d'attente (recommandé en production)

Les notifications implémentent `ShouldQueue` : les emails sont mis en queue et envoyés par les workers. En production, lancez des workers :

```bash
php artisan queue:work
```

Sans worker, les emails sont envoyés de façon synchrone lors de l’exécution de la commande.

## Vérification

1. Configurer `MAIL_MAILER=log` et lancer `php artisan pharmacy:send-expiration-alerts`.
2. Vérifier le contenu dans `storage/logs/laravel.log` (section mail).
3. Passer à un vrai SMTP et relancer pour un envoi réel.
