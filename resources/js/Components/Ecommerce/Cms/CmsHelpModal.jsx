import Modal from '@/Components/Modal';

const HELP_CONTENT = {
    pages: {
        title: 'Tutoriel : Pages CMS',
        items: [
            { title: 'Qu&apos;est-ce qu&apos;une page ?', desc: 'Une page est un contenu affiché sur votre site : À propos, Contact, FAQ, Conditions de vente, etc.' },
            { title: 'Titre', desc: 'Exemples : "À propos", "Contact", "Politique de confidentialité". Visible dans le menu et en tête de page.' },
            { title: 'Slug', desc: 'Partie de l\'URL. Ex : "a-propos" → /page/a-propos. Laissez vide pour générer automatiquement à partir du titre.' },
            { title: 'Contenu', desc: 'Le corps de la page. Utilisez la barre d\'outils pour gras, listes, liens. Écrivez comme dans un éditeur de texte.' },
            { title: 'Image', desc: 'Uploadez d\'abord une image dans CMS > Médias, puis copiez le chemin indiqué (ex : ecommerce/cms/media/xxx/image.jpg).' },
            { title: 'Publier', desc: 'Cochez "Publier" pour rendre la page visible. Définissez une date de publication si vous voulez programmer l\'affichage.' },
        ],
    },
    banners: {
        title: 'Tutoriel : Bannières',
        items: [
            { title: 'Qu&apos;est-ce qu&apos;une bannière ?', desc: 'Une image promotionnelle affichée sur votre site : hero, slider, bandeaux.' },
            { title: 'Position', desc: 'Homepage = image principale, Slider = carrousel, Promotion = bandeau promo.' },
            { title: 'Image', desc: 'Uploadez dans Médias puis indiquez le chemin. Ou une URL externe.' },
            { title: 'Lien', desc: 'URL de destination quand on clique sur la bannière.' },
        ],
    },
    blog: {
        title: 'Tutoriel : Blog',
        items: [
            { title: 'Articles', desc: 'Contenu marketing pour le SEO : actualités, conseils, nouveautés.' },
            { title: 'Catégories', desc: 'Créez des catégories (Actualités, Conseils) puis assignez-les aux articles.' },
            { title: 'Extrait', desc: 'Résumé court affiché dans la liste des articles.' },
            { title: 'Publication', desc: 'Définissez la date pour programmer la publication.' },
        ],
    },
    media: {
        title: 'Tutoriel : Médias',
        items: [
            { title: 'Bibliothèque', desc: 'Uploadez images et documents. Réutilisez-les dans les pages, bannières et articles.' },
            { title: 'Utilisation', desc: 'Après upload, le chemin s\'affiche. Copiez-le dans le champ "Image" ou "Chemin" des autres modules.' },
        ],
    },
};

export default function CmsHelpModal({ show, onClose, module = 'pages' }) {
    const content = HELP_CONTENT[module] ?? HELP_CONTENT.pages;

    return (
        <Modal show={show} onClose={onClose} maxWidth="lg">
            <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">{content.title}</h3>
                <ol className="space-y-4">
                    {content.items.map((item, i) => (
                        <li key={i}>
                            <h4 className="font-medium text-gray-800 dark:text-gray-200 text-sm">{item.title}</h4>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-0.5">{item.desc}</p>
                        </li>
                    ))}
                </ol>
                <div className="mt-6 flex justify-end">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700"
                    >
                        Compris
                    </button>
                </div>
            </div>
        </Modal>
    );
}
