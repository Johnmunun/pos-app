# Comment tester les vendeurs

## Rôles système (une fois par l’admin)

Pour que les **propriétaires** puissent assigner un rôle sans créer de rôles, l’admin (ROOT) exécute une fois :

```bash
php artisan db:seed --class=DefaultSectorRolesSeeder
```

Cela crée le rôle **« Vendeur Pharmacie »** (global). Il apparaît pour tous les tenants Pharmacie. Le propriétaire peut uniquement l’**assigner** à ses vendeurs.

## Test manuel (navigateur)

1. **Se connecter** avec un compte admin tenant (secteur défini : pharmacy, butchery, etc.)

2. **Aller sur** : `http://localhost:8000/pharmacy/sellers`

3. **Créer un vendeur** :
   - Cliquer "Ajouter un vendeur"
   - Remplir : prénom, nom, email, mot de passe
   - **Assigner un rôle** : cliquer sur un rôle pour l’assigner (bleu). Les rôles « Rôle système » sont créés par l’admin. Récliquer pour retirer.
   - Enregistrer

4. **Vérifier** :
   - Le vendeur apparaît dans la liste
   - Seuls les rôles avec permissions de votre secteur sont visibles
   - Modification et désactivation fonctionnent

## Test PHPUnit

```bash
php artisan test tests/Feature/Pharmacy/SellerControllerTest.php
```

## Vérification PHPStan

```bash
./vendor/bin/phpstan analyse src/Infrastructure/Pharmacy/Http/Controllers/SellerController.php --level=5
```
