<?php

namespace Src\Infrastructure\Ecommerce\Services;

use Illuminate\Support\Facades\Schema;
use Src\Infrastructure\Ecommerce\Models\CmsPageModel;

final class DefaultCmsPagesService
{
    /**
     * Crée les pages CMS par défaut si aucune n'existe pour ce shop.
     *
     * @return int Nombre de pages créées
     */
    public static function createIfEmpty(int $shopId): int
    {
        if (!Schema::hasTable('ecommerce_cms_pages')) {
            return 0;
        }

        if (CmsPageModel::where('shop_id', $shopId)->exists()) {
            return 0;
        }

        $defaults = self::getDefaultPagesContent();
        foreach ($defaults as $order => $pageData) {
            CmsPageModel::create(array_merge($pageData, ['shop_id' => $shopId, 'sort_order' => $order]));
        }

        return count($defaults);
    }

    /** @return array<int, array<string, mixed>> */
    public static function getDefaultPagesContent(): array
    {
        return [
            [
                'title' => 'À propos',
                'slug' => 'a-propos',
                'template' => 'standard',
                'content' => '<p>Bienvenue sur notre boutique en ligne.</p><p>Nous sommes une équipe passionnée qui souhaite vous offrir les meilleurs produits et services. Depuis notre création, nous nous engageons à satisfaire nos clients avec une qualité irréprochable et un service personnalisé.</p><p><strong>Notre mission</strong></p><p>Proposer une gamme de produits soignée, à des prix compétitifs, avec une livraison rapide et un service client réactif.</p><p><strong>Nos valeurs</strong></p><ul><li>Qualité des produits</li><li>Transparence et confiance</li><li>Service client à l\'écoute</li><li>Développement durable</li></ul><p>N\'hésitez pas à nous contacter pour toute question.</p>',
                'image_path' => null,
                'metadata' => null,
                'is_active' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'template' => 'contact',
                'content' => '<p>Une question, une suggestion ou besoin d\'aide ? N\'hésitez pas à nous contacter. Notre équipe vous répond dans les plus brefs délais.</p>',
                'image_path' => null,
                'metadata' => [
                    'address' => '123 Avenue de la République, Ville',
                    'phone' => '+243 XXX XXX XXX',
                    'email' => 'contact@votreboutique.com',
                    'hours' => 'Lun - Ven : 9h - 18h | Sam : 9h - 13h',
                ],
                'is_active' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Conditions générales de vente',
                'slug' => 'cgv',
                'template' => 'standard',
                'content' => '<h2>1. Objet et champ d\'application</h2><p>Les présentes conditions générales de vente (CGV) régissent les ventes de produits effectuées par notre boutique. En passant commande, le client accepte sans réserve ces conditions.</p><h2>2. Produits et prix</h2><p>Les produits sont présentés avec leur description et leur prix. Les prix sont indiqués en devise locale, toutes taxes comprises (TVA incluse). Nous nous réservons le droit de modifier nos prix à tout moment.</p><h2>3. Commande et paiement</h2><p>La commande est validée après confirmation du paiement. Nous acceptons les moyens de paiement suivants : Mobile Money, carte bancaire, espèces à la livraison.</p><h2>4. Livraison</h2><p>Les délais de livraison sont indiqués à titre indicatif. En cas de retard, nous vous en informerons. Les frais de livraison sont calculés lors du passage de commande.</p><h2>5. Retours et garanties</h2><p>Conformément à la législation en vigueur, vous disposez d\'un délai de rétractation. Les produits doivent être retournés dans leur état d\'origine. Contactez-nous pour toute réclamation.</p>',
                'image_path' => null,
                'metadata' => null,
                'is_active' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Politique de confidentialité',
                'slug' => 'confidentialite',
                'template' => 'standard',
                'content' => '<h2>1. Collecte des données</h2><p>Nous collectons les informations que vous nous fournissez lors de la création de compte et du passage de commande : nom, adresse, email, téléphone. Ces données sont nécessaires pour traiter vos commandes et vous contacter.</p><h2>2. Utilisation des données</h2><p>Vos données personnelles sont utilisées uniquement pour : la gestion des commandes, l\'envoi d\'informations relatives à votre achat, l\'amélioration de nos services. Nous ne vendons pas vos données à des tiers.</p><h2>3. Sécurité</h2><p>Nous mettons en œuvre des mesures techniques et organisationnelles pour protéger vos données contre tout accès non autorisé.</p><h2>4. Vos droits</h2><p>Conformément à la réglementation, vous disposez d\'un droit d\'accès, de rectification et de suppression de vos données. Contactez-nous pour exercer ces droits.</p>',
                'image_path' => null,
                'metadata' => null,
                'is_active' => true,
                'published_at' => now(),
            ],
        ];
    }
}
