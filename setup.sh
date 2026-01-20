#!/bin/bash
# Script de test complet du système POS SaaS

echo "🚀 Démarrage du test du système POS SaaS..."
echo "=============================================="
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 1. Vérifier que PHP est installé
echo -e "${BLUE}1. Vérification de PHP...${NC}"
if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ PHP n'est pas installé${NC}"
    exit 1
fi
echo -e "${GREEN}✓ PHP trouvé: $(php --version | head -n1)${NC}"
echo ""

# 2. Vérifier que Node.js est installé
echo -e "${BLUE}2. Vérification de Node.js...${NC}"
if ! command -v npm &> /dev/null; then
    echo -e "${RED}✗ Node.js/npm n'est pas installé${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Node.js trouvé: $(node --version)${NC}"
echo ""

# 3. Installer les dépendances PHP
echo -e "${BLUE}3. Installation des dépendances PHP...${NC}"
if [ ! -d "vendor" ]; then
    echo "composer install --no-interaction"
    composer install --no-interaction
else
    echo -e "${GREEN}✓ Dépendances PHP déjà installées${NC}"
fi
echo ""

# 4. Installer les dépendances NPM
echo -e "${BLUE}4. Installation des dépendances NPM...${NC}"
if [ ! -d "node_modules" ]; then
    echo "npm install"
    npm install
else
    echo -e "${GREEN}✓ Dépendances NPM déjà installées${NC}"
fi
echo ""

# 5. Vérifier le fichier .env
echo -e "${BLUE}5. Vérification du fichier .env...${NC}"
if [ ! -f ".env" ]; then
    echo "cp .env.example .env"
    cp .env.example .env
    echo -e "${YELLOW}⚠ Fichier .env créé. Veuillez configurer votre base de données.${NC}"
else
    echo -e "${GREEN}✓ Fichier .env trouvé${NC}"
fi
echo ""

# 6. Générer la clé APP
echo -e "${BLUE}6. Génération de la clé APP...${NC}"
php artisan key:generate
echo -e "${GREEN}✓ Clé APP générée${NC}"
echo ""

# 7. Exécuter les migrations
echo -e "${BLUE}7. Exécution des migrations...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Migrations exécutées${NC}"
echo ""

# 8. Créer le ROOT user
echo -e "${BLUE}8. Création de l'utilisateur ROOT...${NC}"
php artisan db:seed --class=CreateRootUserSeeder
echo -e "${GREEN}✓ Utilisateur ROOT créé${NC}"
echo ""

# 9. Construire les assets
echo -e "${BLUE}9. Compilation des assets...${NC}"
npm run build
echo -e "${GREEN}✓ Assets compilés${NC}"
echo ""

# 10. Afficher le résumé
echo -e "${BLUE}=============================================="
echo "✅ Installation terminée!"
echo "=============================================${NC}"
echo ""
echo -e "${YELLOW}Identifiants ROOT pour la connexion:${NC}"
echo "Email: root@pos-saas.local"
echo "Mot de passe: RootPassword123"
echo ""
echo -e "${YELLOW}Prochaines étapes:${NC}"
echo "1. php artisan serve       # Démarrer le serveur"
echo "2. http://localhost:8000   # Accéder à l'application"
echo ""
echo -e "${YELLOW}Routes utiles:${NC}"
echo "- Landing: http://localhost:8000/"
echo "- Login: http://localhost:8000/login"
echo "- Register: http://localhost:8000/register"
echo "- Admin (ROOT): http://localhost:8000/admin/select-tenant"
echo ""
