/**
 * Script pour générer les icônes PWA
 * 
 * Ce script nécessite sharp pour générer les icônes
 * Installation: npm install --save-dev sharp
 * 
 * Usage: node scripts/generate-pwa-icons.js
 */

const fs = require('fs');
const path = require('path');

// Tailles d'icônes requises
const sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// SVG de base pour l'icône (à remplacer par votre logo)
const svgIcon = `
<svg width="512" height="512" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
  <rect width="512" height="512" rx="80" fill="#f59e0b"/>
  <text x="256" y="320" font-family="Arial, sans-serif" font-size="200" font-weight="bold" fill="white" text-anchor="middle">POS</text>
</svg>
`;

console.log('📱 Génération des icônes PWA...');
console.log('⚠️  Ce script nécessite sharp pour générer les PNG.');
console.log('💡 Pour l\'instant, créez manuellement les icônes ou utilisez un outil en ligne.');
console.log('');
console.log('Tailles requises:');
sizes.forEach(size => {
    console.log(`  - icon-${size}x${size}.png (${size}x${size}px)`);
});
console.log('');
console.log('📝 Placez les icônes dans: public/icons/');



