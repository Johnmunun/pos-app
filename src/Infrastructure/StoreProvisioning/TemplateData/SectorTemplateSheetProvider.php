<?php

namespace Src\Infrastructure\StoreProvisioning\TemplateData;

/**
 * Source structurée des feuilles Excel (convertie en .xlsx par la commande artisan).
 * Colonnes alignées sur les tables réelles (codes métiers, pas d’ID SQL).
 */
final class SectorTemplateSheetProvider
{
    /** @return array<string, string> dossier => méthode statique */
    public static function sectors(): array
    {
        return [
            'pharmacy' => 'pharmacy',
            'hardware' => 'hardware',
            'global-commerce' => 'globalCommerce',
            'ecommerce' => 'ecommerce',
        ];
    }

    /**
     * @return array{currencies: array{header: list<string>, rows: list<list<string>>}, exchange_rates: array{header: list<string>, rows: list<list<string>>}, categories: array{header: list<string>, rows: list<list<string>>}, products: array{header: list<string>, rows: list<list<string>>}}
     */
    public static function pharmacy(string $today): array
    {
        $curH = ['code', 'name', 'symbol', 'is_default', 'is_active'];
        $curR = [
            ['USD', 'Dollar américain', '$', '1', '1'],
            ['CDF', 'Franc congolais', 'FC', '0', '1'],
            ['XAF', 'Franc CFA (CEMAC)', 'FCFA', '0', '1'],
        ];
        $xrH = ['from_currency_code', 'to_currency_code', 'rate', 'effective_date'];
        $xrR = [
            ['USD', 'CDF', '2850', $today],
            ['USD', 'XAF', '620', $today],
        ];

        $catH = ['category_code', 'name', 'description', 'parent_category_code', 'sort_order'];
        $catR = [
            ['RX-ANTIB', 'Antibiotiques et anti-infectieux', 'Prescription et OTC selon règlement local', '', '10'],
            ['RX-ANALG', 'Antalgiques et anti-inflammatoires', 'Douleur fièvre inflammation', '', '20'],
            ['PARA-VIT', 'Vitamines et minéraux', 'Compléments alimentaires', '', '30'],
            ['PARA-DERM', 'Dermatologie', 'Soins peau cuir chevelu', '', '40'],
            ['PARA-DIG', 'Digestion et métabolisme', 'Transit foie bile', '', '50'],
            ['MAT-PED', 'Puériculture et orthopédie légère', 'Petit matériel paramédical', '', '60'],
        ];

        $names = [
            ['AMX500', 'Amoxicilline acide clavulanique 500/125 mg — Boîte 12', 'MEDICINE', 'RX-ANTIB', '18.50', 'USD', '24', '1 cp matin et soir', '1', 'BOITE'],
            ['AZI250', 'Azithromycine 250 mg — Boîte 6 cp', 'MEDICINE', 'RX-ANTIB', '22.00', 'USD', '18', 'Selon prescription', '1', 'BOITE'],
            ['CEF100', 'Céfixime 100 mg suspension — Flacon', 'MEDICINE', 'RX-ANTIB', '15.75', 'USD', '10', 'Enfant selon poids', '1', 'FLACON'],
            ['MET400', 'Métronidazole 400 mg — Boîte 20', 'MEDICINE', 'RX-ANTIB', '12.40', 'USD', '30', '', '0', 'BOITE'],
            ['CIP500', 'Ciprofloxacine 500 mg — Boîte 10', 'MEDICINE', 'RX-ANTIB', '14.20', 'USD', '22', '', '1', 'BOITE'],
            ['PAR500', 'Paracétamol 500 mg — Boîte 20', 'MEDICINE', 'RX-ANALG', '4.20', 'USD', '120', 'Posologie adulte', '0', 'BOITE'],
            ['IBU400', 'Ibuprofène 400 mg — Boîte 20', 'MEDICINE', 'RX-ANALG', '6.80', 'USD', '80', 'À prendre au repas', '0', 'BOITE'],
            ['DIC50', 'Diclofénac 50 mg — Boîte 30', 'MEDICINE', 'RX-ANALG', '7.50', 'USD', '45', '', '1', 'BOITE'],
            ['MEL15', 'Méloxicam 15 mg — Boîte 10', 'MEDICINE', 'RX-ANALG', '11.00', 'USD', '25', '', '1', 'BOITE'],
            ['ASP100', 'Acide acétylsalicylique 100 mg — Boîte 30', 'MEDICINE', 'RX-ANALG', '3.90', 'USD', '60', 'Antiagrégant', '0', 'BOITE'],
            ['VITD3', 'Vitamine D3 1000 UI — Flacon 60 gélules', 'PARAPHARMACY', 'PARA-VIT', '9.90', 'USD', '40', '', '0', 'FLACON'],
            ['MAG-B6', 'Magnésium + vitamine B6 — Boîte 45', 'PARAPHARMACY', 'PARA-VIT', '8.20', 'USD', '35', '', '0', 'BOITE'],
            ['FER-FE', 'Fer bisglycinate 30 mg — Boîte 30', 'PARAPHARMACY', 'PARA-VIT', '10.50', 'USD', '28', '', '0', 'BOITE'],
            ['OM3-1000', 'Oméga-3 EPA/DHA 1000 mg — 60 capsules', 'PARAPHARMACY', 'PARA-VIT', '16.00', 'USD', '20', '', '0', 'FLACON'],
            ['PRO-ZINC', 'Zinc citrate 15 mg — 40 cp', 'PARAPHARMACY', 'PARA-VIT', '7.00', 'USD', '32', '', '0', 'BOITE'],
            ['CRE-HYD', 'Crème hydratante urée 10 % — Tube 100 ml', 'PARAPHARMACY', 'PARA-DERM', '5.50', 'USD', '48', 'Peaux sèches', '0', 'TUBE'],
            ['GEL-ALO', 'Gel aloès apaisant — 150 ml', 'PARAPHARMACY', 'PARA-DERM', '6.20', 'USD', '36', 'Après-soleil léger', '0', 'FLACON'],
            ['SHP-ANT', 'Shampooing antipelliculaire — 200 ml', 'PARAPHARMACY', 'PARA-DERM', '7.80', 'USD', '40', '', '0', 'FLACON'],
            ['SPF50', 'Écran solaire SPF 50 — 50 ml', 'PARAPHARMACY', 'PARA-DERM', '12.00', 'USD', '22', '', '0', 'TUBE'],
            ['CHAR-ACT', 'Charbon végétal activé — 40 gélules', 'PARAPHARMACY', 'PARA-DIG', '6.50', 'USD', '30', '', '0', 'BOITE'],
            ['PRO-DIG', 'Probiotiques 10 milliards UFC — 20 sachets', 'PARAPHARMACY', 'PARA-DIG', '14.50', 'USD', '18', 'Froid', '0', 'BOITE'],
            ['ESO-40', 'Esoméprazole 40 mg — Boîte 14', 'MEDICINE', 'PARA-DIG', '19.00', 'USD', '15', 'Sur ordonnance', '1', 'BOITE'],
            ['DOM10', 'Dompéridone 10 mg — Boîte 30', 'MEDICINE', 'PARA-DIG', '8.00', 'USD', '26', '', '0', 'BOITE'],
            ['TENS-CE', 'Tensiomètre brassard électronique', 'DEVICE', 'MAT-PED', '42.00', 'USD', '8', '', '0', 'UNITE'],
            ['GLU-ST', 'Lecteur glycémie + 50 bandelettes', 'DEVICE', 'MAT-PED', '28.00', 'USD', '12', '', '0', 'KIT'],
            ['THER-DIG', 'Thermomètre digital flexible', 'DEVICE', 'MAT-PED', '6.00', 'USD', '35', '', '0', 'UNITE'],
            ['PANSE-AS', 'Pansements assortis boîte familiale', 'PARAPHARMACY', 'MAT-PED', '4.50', 'USD', '50', '', '0', 'BOITE'],
        ];

        $pH = ['product_code', 'name', 'description', 'type', 'category_code', 'price_amount', 'price_currency', 'stock', 'dosage', 'requires_prescription', 'unit'];
        $pR = [];
        foreach ($names as $n) {
            $pR[] = [
                $n[0],
                $n[1],
                'Référence courante — stock rotation standard',
                $n[2],
                $n[3],
                $n[4],
                $n[5],
                $n[6],
                $n[7],
                $n[8],
                $n[9],
            ];
        }

        return [
            'currencies' => ['header' => $curH, 'rows' => $curR],
            'exchange_rates' => ['header' => $xrH, 'rows' => $xrR],
            'categories' => ['header' => $catH, 'rows' => $catR],
            'products' => ['header' => $pH, 'rows' => $pR],
        ];
    }

    public static function hardware(string $today): array
    {
        $curH = ['code', 'name', 'symbol', 'is_default', 'is_active'];
        $curR = [
            ['USD', 'Dollar américain', '$', '1', '1'],
            ['CDF', 'Franc congolais', 'FC', '0', '1'],
            ['XAF', 'Franc CFA (CEMAC)', 'FCFA', '0', '1'],
        ];
        $xrH = ['from_currency_code', 'to_currency_code', 'rate', 'effective_date'];
        $xrR = [
            ['USD', 'CDF', '2850', $today],
            ['USD', 'XAF', '620', $today],
        ];
        $catH = ['category_code', 'name', 'description', 'parent_category_code', 'sort_order'];
        $catR = [
            ['HW-OUT', 'Outillage à main', 'Marteaux tournevis pinces', '', '10'],
            ['HW-FIX', 'Visserie et fixation', 'Vis écrous chevilles', '', '20'],
            ['HW-PLU', 'Plomberie sanitaire', 'Raccords tuyaux robinetterie', '', '30'],
            ['HW-ELE', 'Électricité courant faible', 'Câbles gaines disjoncteurs domestiques', '', '40'],
            ['HW-PEI', 'Peinture et quincaillerie légère', 'Peintures accessoires', '', '50'],
        ];

        $rows = [
            ['VIS-4x40', 'Vis à bois 4×40 mm — Boîte 200', 'HW-FIX', '3.20', 'USD', '80', 'PIECE', '1', '1', '2'],
            ['VIS-5x50', 'Vis à bois 5×50 mm — Boîte 150', 'HW-FIX', '4.10', 'USD', '65', 'PIECE', '1', '1', '2'],
            ['ECR-M8', 'Écrous hexagonaux M8 — Sachet 50', 'HW-FIX', '2.80', 'USD', '120', 'PIECE', '1', '1', '1'],
            ['CHEV-8', 'Chevilles nylon Ø8 — Sachet 100', 'HW-FIX', '5.50', 'USD', '40', 'PIECE', '1', '1', '1'],
            ['MAR-16', 'Marteau menuisier 16 oz manche bois', 'HW-OUT', '11.90', 'USD', '15', 'UNITE', '1', '1', '1'],
            ['NIV-60', 'Niveau à bulle 60 cm aluminium', 'HW-OUT', '9.40', 'USD', '12', 'UNITE', '1', '1', '1'],
            ['PIN-COUP', 'Pince coupante 180 mm', 'HW-OUT', '7.20', 'USD', '20', 'UNITE', '1', '1', '1'],
            ['MET-5M', 'Mètre ruban 5 m magnétique', 'HW-OUT', '4.50', 'USD', '35', 'UNITE', '1', '1', '1'],
            ['SCI-B', 'Scie à métaux 300 mm', 'HW-OUT', '8.90', 'USD', '10', 'UNITE', '1', '1', '1'],
            ['CLE-8-22', 'Clé à molette 8–22 mm', 'HW-OUT', '6.60', 'USD', '18', 'UNITE', '1', '1', '1'],
            ['RAC-20-27', 'Raccord PER glissement 20×2,0', 'HW-PLU', '1.40', 'USD', '200', 'PIECE', '1', '1', '1'],
            ['COU-26', 'Coude cuivre 90° Ø26', 'HW-PLU', '2.10', 'USD', '90', 'PIECE', '1', '1', '1'],
            ['COL-MAP', 'Colle PVC plomberie 125 ml', 'HW-PLU', '3.80', 'USD', '45', 'TUBE', '1', '1', '1'],
            ['ROB-LAV', 'Robinet lavabo monotrou chrome', 'HW-PLU', '24.00', 'USD', '8', 'UNITE', '1', '1', '1'],
            ['TUY-PVC32', 'Tuyau PVC évacuation Ø32 — 2 m', 'HW-PLU', '4.20', 'USD', '30', 'BARRE', '1', '1', '1'],
            ['GAINE-16', 'Gaine ICTA Ø16 — Bobine 50 m', 'HW-ELE', '38.00', 'USD', '6', 'BOBINE', '1', '1', '1'],
            ['CAB-3G15', 'Câble électrique 3G1,5 — Vendu au mètre', 'HW-ELE', '1.10', 'USD', '500', 'METRE', '1', '1', '1'],
            ['DISJ-16A', 'Disjoncteur 1P+N 16 A courbe C', 'HW-ELE', '9.50', 'USD', '25', 'UNITE', '1', '1', '1'],
            ['PRI-2P', 'Prise de courant 2P+T encastrable', 'HW-ELE', '2.30', 'USD', '140', 'PIECE', '1', '1', '1'],
            ['INT-DO', 'Interrupteur va-et-vient encastrable', 'HW-ELE', '1.90', 'USD', '160', 'PIECE', '1', '1', '1'],
            ['PEI-BLANC', 'Peinture acrylique blanche 10 L', 'HW-PEI', '48.00', 'USD', '14', 'POT', '1', '1', '1'],
            ['PEI-SAT', 'Peinture façade satinée 2,5 L — Ton pierre', 'HW-PEI', '22.00', 'USD', '18', 'POT', '1', '1', '1'],
            ['RUB-MAS', 'Ruban de masquage 48 mm × 50 m', 'HW-PEI', '4.80', 'USD', '40', 'ROULEAU', '1', '1', '1'],
            ['BROS-100', 'Brosse plate fibres mixtes 100 mm', 'HW-PEI', '3.60', 'USD', '55', 'UNITE', '1', '1', '1'],
            ['CHAINE-6', 'Chaîne à maillons galvanisés 6 mm — Au mètre', 'HW-FIX', '2.20', 'USD', '70', 'METRE', '1', '1', '1'],
            ['SERR-CYL', 'Serrure cylindre européen 30/30', 'HW-FIX', '18.50', 'USD', '12', 'UNITE', '1', '1', '1'],
            ['LAM-LED', 'Lampe LED chantier portable 20 W', 'HW-ELE', '26.00', 'USD', '9', 'UNITE', '1', '1', '1'],
        ];
        $pH = ['product_code', 'name', 'description', 'category_code', 'price_amount', 'price_currency', 'stock', 'type_unite', 'quantite_par_unite', 'est_divisible', 'minimum_stock'];
        $pR = [];
        foreach ($rows as $r) {
            $pR[] = [
                $r[0],
                $r[1],
                'Référence fournisseur courante',
                $r[2],
                $r[3],
                $r[4],
                $r[5],
                $r[6],
                $r[7],
                $r[8],
                $r[9],
            ];
        }

        return [
            'currencies' => ['header' => $curH, 'rows' => $curR],
            'exchange_rates' => ['header' => $xrH, 'rows' => $xrR],
            'categories' => ['header' => $catH, 'rows' => $catR],
            'products' => ['header' => $pH, 'rows' => $pR],
        ];
    }

    public static function globalCommerce(string $today): array
    {
        return self::gcPack($today, false);
    }

    public static function ecommerce(string $today): array
    {
        return self::gcPack($today, true);
    }

    private static function gcPack(string $today, bool $ecom): array
    {
        $curH = ['code', 'name', 'symbol', 'is_default', 'is_active'];
        $curR = [
            ['USD', 'Dollar américain', '$', '1', '1'],
            ['CDF', 'Franc congolais', 'FC', '0', '1'],
            ['XAF', 'Franc CFA (CEMAC)', 'FCFA', '0', '1'],
        ];
        $xrH = ['from_currency_code', 'to_currency_code', 'rate', 'effective_date'];
        $xrR = [
            ['USD', 'CDF', '2850', $today],
            ['USD', 'XAF', '620', $today],
        ];
        $catH = ['category_code', 'name', 'description', 'parent_category_code', 'sort_order'];
        $catR = [
            ['GC-EPIC', 'Épicerie salée', 'Riz huile conserves', '', '10'],
            ['GC-SUCR', 'Sucré et petit-déjeuner', 'Café thé confitures', '', '20'],
            ['GC-BOIS', 'Boissons', 'Eaux jus sodas', '', '30'],
            ['GC-ENTR', 'Entretien maison', 'Produits ménagers', '', '40'],
            ['GC-EMB', 'Emballages et consommables caisse', 'Sacs étiquettes', '', '50'],
        ];

        $suffix = $ecom ? ' — expédition sous 48 h' : '';
        $rows = [
            ['RIZ-25', 'Riz parfumé 25 kg sac', 'GC-EPIC', '1.20', 'USD', '2.80', 'USD', '42', '5', '0', '0'],
            ['HUI-5L', 'Huile végétale 5 L', 'GC-EPIC', '6.50', 'USD', '8.90', 'USD', '28', '4', '0', '0'],
            ['TOM-400', 'Concentré tomate 400 g', 'GC-EPIC', '0.85', 'USD', '1.40', 'USD', '120', '24', '0', '0'],
            ['HAR-500', 'Haricots blancs secs 500 g', 'GC-EPIC', '1.10', 'USD', '1.80', 'USD', '60', '10', '0', '0'],
            ['SPAG-500', 'Spaghetti 500 g', 'GC-EPIC', '0.55', 'USD', '0.99', 'USD', '200', '8', '0', '0'],
            ['CAF-250', 'Café moulu arabica 250 g', 'GC-SUCR', '3.40', 'USD', '5.20', 'USD', '45', '6', '0', '0'],
            ['THE-100', 'Thé noir sachets ×100', 'GC-SUCR', '2.80', 'USD', '4.50', 'USD', '38', '5', '0', '0'],
            ['CHOC-200', 'Chocolat pâtissier 200 g', 'GC-SUCR', '1.90', 'USD', '3.10', 'USD', '52', '8', '0', '0'],
            ['CONF-370', 'Confiture fruits rouges 370 g', 'GC-SUCR', '1.60', 'USD', '2.70', 'USD', '44', '10', '0', '0'],
            ['BIS-SEC', 'Biscuits secs familiaux 800 g', 'GC-SUCR', '2.20', 'USD', '3.60', 'USD', '36', '4', '0', '0'],
            ['EAU-15L', 'Eau minérale 1,5 L — Pack 6', 'GC-BOIS', '1.80', 'USD', '3.20', 'USD', '90', '6', '0', '0'],
            ['JUS-1L', 'Jus de fruits 1 L', 'GC-BOIS', '1.20', 'USD', '1.90', 'USD', '70', '8', '0', '0'],
            ['SOD-33', 'Boisson gazeuse 33 cl — Pack 24', 'GC-BOIS', '8.00', 'USD', '12.50', 'USD', '22', '4', '0', '0'],
            ['LAV-LIQ', 'Lessive liquide 3 L', 'GC-ENTR', '4.80', 'USD', '7.90', 'USD', '30', '3', '0', '0'],
            ['JAV-1L', 'Eau de Javel concentrée 1 L', 'GC-ENTR', '0.90', 'USD', '1.50', 'USD', '100', '12', '0', '0'],
            ['SOL-MUL', 'Sol multi-surfaces 1 L', 'GC-ENTR', '1.70', 'USD', '2.80', 'USD', '55', '6', '0', '0'],
            ['EPON-L', 'Éponges abrasives — Lot 5', 'GC-ENTR', '1.10', 'USD', '1.80', 'USD', '80', '10', '0', '0'],
            ['SAC-KRA', 'Sacs kraft poignées — Lot 100', 'GC-EMB', '4.50', 'USD', '7.20', 'USD', '25', '2', '0', '0'],
            ['ROU-THER', 'Rouleau thermique 80 mm — Lot 10', 'GC-EMB', '6.20', 'USD', '9.80', 'USD', '18', '4', '0', '0'],
            ['ETQ-PRI', 'Étiquettes prix adhésives — Boîte 1000', 'GC-EMB', '3.80', 'USD', '6.10', 'USD', '14', '2', '0', '0'],
            ['FIL-ALI', 'Film alimentaire 300 m professionnel', 'GC-EMB', '7.50', 'USD', '11.90', 'USD', '12', '3', '0', '0'],
            ['SEL-1K', 'Sel iodé 1 kg', 'GC-EPIC', '0.45', 'USD', '0.79', 'USD', '150', '20', '0', '0'],
            ['POI-1K', 'Poivre noir moulu 1 kg', 'GC-EPIC', '4.20', 'USD', '6.80', 'USD', '15', '2', '0', '0'],
            ['LAIT-UHT', 'Lait UHT entier 1 L — Pack 6', 'GC-SUCR', '4.80', 'USD', '7.50', 'USD', '40', '5', '0', '0'],
            ['SUCRE-1K', 'Sucre cristallisé 1 kg', 'GC-SUCR', '0.95', 'USD', '1.55', 'USD', '110', '15', '0', '0'],
            ['CHIPS-150', 'Chips pomme de terre 150 g', 'GC-SUCR', '0.75', 'USD', '1.25', 'USD', '95', '10', '0', '0'],
            ['SERV-COU', 'Serviettes en papier — Lot 4 rouleaux', 'GC-ENTR', '3.20', 'USD', '5.10', 'USD', '33', '6', '0', '0'],
        ];

        $pH = ['sku', 'barcode', 'name', 'description', 'category_code', 'purchase_price_amount', 'purchase_price_currency', 'sale_price_amount', 'sale_price_currency', 'stock', 'minimum_stock', 'is_weighted', 'has_expiration'];
        $pR = [];
        foreach ($rows as $r) {
            $name = $r[1].$suffix;
            $bc = '377'.substr(str_pad((string) sprintf('%u', crc32($r[0])), 10, '0', STR_PAD_LEFT), 0, 10);
            $pR[] = [
                $r[0],
                $bc,
                $name,
                'Conditionnement standard grande distribution',
                $r[2],
                $r[3],
                $r[4],
                $r[5],
                $r[6],
                $r[7],
                $r[8],
                $r[9],
                $r[10],
            ];
        }

        return [
            'currencies' => ['header' => $curH, 'rows' => $curR],
            'exchange_rates' => ['header' => $xrH, 'rows' => $xrR],
            'categories' => ['header' => $catH, 'rows' => $catR],
            'products' => ['header' => $pH, 'rows' => $pR],
        ];
    }
}
