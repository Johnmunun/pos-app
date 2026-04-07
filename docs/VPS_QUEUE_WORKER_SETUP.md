# VPS Queue Worker Setup (Laravel)

Ce guide explique comment exécuter `queue:work` proprement sur un VPS Linux avec Supervisor.

## Objectif

Lancer en continu ce worker pour la génération IA asynchrone:

```bash
php artisan queue:work --queue=default --tries=1 --timeout=180 --sleep=2 --max-time=3600
```

## Prérequis

- VPS Linux (Ubuntu/Debian recommandé)
- Projet Laravel déjà déployé
- Base de données migrée
- `QUEUE_CONNECTION=database` (ou autre queue configurée)
- `jobs` et `failed_jobs` disponibles (migrations Laravel)

## 1) Installer Supervisor

```bash
sudo apt update
sudo apt install -y supervisor
```

## 2) Créer la configuration du worker

Créer le fichier:

```bash
sudo nano /etc/supervisor/conf.d/pos-app-worker.conf
```

Contenu (utiliser un seul chemin de projet partout) :

```ini
[program:pos-app-worker]
process_name=%(program_name)s_%(process_num)02d
directory=/home/omnisolution.shop/public_html/pos-app
command=php artisan queue:work --queue=default --tries=1 --timeout=180 --sleep=2 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/home/omnisolution.shop/public_html/pos-app/storage/logs/worker.log
stopwaitsecs=200
```

Remplacer `/home/omnisolution.shop/public_html/pos-app` par le vrai chemin du projet si différent.

## 3) Activer et démarrer

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start pos-app-worker:*
sudo supervisorctl status
```

## 4) Vérifier les logs

```bash
tail -f /home/omnisolution.shop/public_html/pos-app/storage/logs/worker.log
```

## 5) Commandes à faire après déploiement

Dans le dossier du projet:

```bash
php artisan optimize:clear
php artisan migrate --force
```

Puis redémarrer le worker:

```bash
sudo supervisorctl restart pos-app-worker:*
```

## 6) Commandes utiles

- Voir statut:

```bash
sudo supervisorctl status
```

- Redémarrer:

```bash
sudo supervisorctl restart pos-app-worker:*
```

- Arrêter:

```bash
sudo supervisorctl stop pos-app-worker:*
```

- Relire config Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
```

## 7) Dépannage rapide

- Worker bloqué en `pending`:
  - vérifier `QUEUE_CONNECTION` dans `.env`
  - vérifier que Supervisor tourne (`status`)
  - vérifier les logs `worker.log`

- Jobs échouent:
  - vérifier `storage/logs/laravel.log`
  - vérifier accès API externe (OpenAI)
  - vérifier timeout/ressources VPS

- Changements de code non pris en compte:
  - redéployer
  - `php artisan optimize:clear`
  - redémarrer Supervisor

