# Landing Page - POS SaaS

## Vue d'ensemble

Une landing page professionnelle et moderne pour présenter POS SaaS, inspirée du design de Chariow.com.

## Structure des fichiers

```
resources/js/
├── Pages/
│   ├── Landing.jsx          # Page principale (point d'entrée)
│   └── Welcome.jsx          # Page de connexion
└── Components/
    ├── Header.jsx           # En-tête avec navigation
    ├── Hero.jsx            # Section héro
    ├── Features.jsx        # Présentation des fonctionnalités
    ├── Testimonials.jsx    # Témoignages et partenaires
    ├── Pricing.jsx         # Tarifs et plans
    └── Footer.jsx          # Pied de page
```

## Fonctionnalités

### ✅ Design Responsive
- **Mobile**: Optimisé pour petits écrans
- **Tablette**: Layout adapté pour moyenne résolution
- **Desktop**: Expérience complète sur grands écrans

### ✅ Dark Mode Automatique
- Détecte automatiquement les préférences du système
- Écoute les changements en temps réel
- Tous les composants supportent le dark mode

### ✅ Sections
1. **Header** - Navigation avec menu mobile et CTA
2. **Hero** - Titre accrocheur, sous-titre, mockup et CTA
3. **Features** - 6 cartes de fonctionnalités avec icônes
4. **Testimonials** - 3 témoignages + logos partenaires + stats
5. **Pricing** - 3 plans avec plan populaire en évidence
6. **Footer** - Liens légaux, réseaux sociaux, contact

### ✅ Animations et Interactions
- Hover effects sur les cartes et boutons
- Smooth scroll vers les sections
- Transitions de couleur au passage du dark mode
- Animations d'apparition au chargement

## Installation

### 1. Ajouter la route dans Inertia
Modifiez [routes/web.php](../../routes/web.php):

```php
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing');
})->name('landing');
```

### 2. Utiliser la landing page
Accédez à `http://localhost:8000/` pour voir la landing page

## Personnalisation

### Changer les couleurs
Les couleurs utilisent les classes Tailwind `blue-600` et `indigo-600`. 
Pour changer la palette, modifiez dans chaque composant:

```jsx
// Avant
className="bg-blue-600 hover:bg-blue-700"

// Après
className="bg-purple-600 hover:bg-purple-700"
```

### Modifier les textes
Chaque section contient des constantes facilement modifiables:

```jsx
// Dans Features.jsx
const features = [
    {
        icon: '🛍️',
        title: 'Vente de produits digitaux',
        description: 'Modifiez ce texte',
    },
    // ...
];
```

### Ajouter des images réelles
Remplacez les mockups et placeholders:

```jsx
// Dans Hero.jsx
<img 
    src="https://votre-domaine.com/image.png" 
    alt="Dashboard"
    className="rounded-2xl shadow-2xl"
/>
```

### Modifier les tarifs
Éditez le tableau dans Pricing.jsx:

```jsx
const plans = [
    {
        name: 'Starter',
        price: '29',  // Changez le prix
        features: [
            'Votre fonctionnalité',
            // ...
        ],
    },
];
```

## Dark Mode

Le dark mode fonctionne automatiquement grâce à:

1. **Détection système** - Utilise `prefers-color-scheme`
2. **Classe `dark`** - Appliquée à `html` quand dark mode est actif
3. **Classes Tailwind** - `dark:` prefix pour les styles dark

Exemple dans un composant:
```jsx
<div className="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
    Contenu adapté au dark mode
</div>
```

## Navigation

La navigation utilise le smooth scroll vers les sections:

```jsx
// Header.jsx
const handleMenuClick = (id) => {
    onScrollToSection(id);  // Scroll vers #features, #pricing, etc.
};

// Landing.jsx
const handleScrollToSection = (id) => {
    const element = document.getElementById(id);
    // Scroll smooth vers l'élément
};
```

## CTA et Conversions

Les boutons CTA sont présents dans:
- Header (Commencer)
- Hero (Essayer gratuitement, Voir la démo)
- Features (Découvrir toutes les fonctionnalités)
- Pricing (Commencer maintenant)
- Footer (Essayer gratuitement, Nous contacter)

Connectez-les à vos endpoints:

```jsx
// Avant
<button onClick={() => alert('CTA clicked')}>
    Commencer maintenant
</button>

// Après
<button onClick={() => router.visit(route('login'))}>
    Commencer maintenant
</button>
```

## Performance

- Composants optimisés avec React hooks
- Aucun JavaScript inutile
- CSS Tailwind purifiée (production)
- Images optimisées
- Lazy loading possible

## Accessibilité

- Sémantique HTML correcte
- Contraste de couleurs suffisant
- Navigation au clavier supportée
- ARIA labels pour les icônes

## SEO

Pour améliorer le SEO, ajoutez des meta tags dans `Head`:

```jsx
import Head from '@inertiajs/react/Head';

<Head>
    <title>POS SaaS - Votre point de vente en ligne</title>
    <meta name="description" content="..." />
</Head>
```

## Support du navigateur

- Chrome/Edge (dernier)
- Firefox (dernier)
- Safari (dernier)
- Mobile browsers

## Prochaines étapes

1. ✅ Connecter les boutons CTA à de vraies routes
2. ✅ Ajouter des images/logos réels
3. ✅ Configurer les analytics (Google Analytics)
4. ✅ Tester les conversions
5. ✅ Optimiser les performances

## Troubleshooting

### Dark mode ne fonctionne pas
Vérifiez que la classe `dark` est appliquée à `html`:
```jsx
document.documentElement.classList.add('dark');
```

### Animations figées
Vérifiez les préférences utilisateur `prefers-reduced-motion`:
```css
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
    }
}
```

### Problèmes de scroll
Le smooth scroll est défini dans le style global de Landing.jsx:
```css
html {
    scroll-behavior: smooth;
}
```
