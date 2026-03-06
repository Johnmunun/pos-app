<?php

declare(strict_types=1);

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Pharmacy\DTO\CreateSupplierDTO;
use Src\Application\Pharmacy\DTO\UpdateSupplierDTO;
use Src\Application\Pharmacy\UseCases\Supplier\ActivateSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\CreateSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\DeactivateSupplierUseCase;
use Src\Application\Pharmacy\UseCases\Supplier\UpdateSupplierUseCase;
use Src\Infrastructure\Pharmacy\Models\SupplierModel;
use Src\Infrastructure\Pharmacy\Models\SupplierProductPriceModel;
use Src\Infrastructure\Pharmacy\Models\ProductModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

/**
 * Controller: SupplierController
 *
 * Gère les requêtes HTTP pour le module Fournisseurs.
 * Utilisable depuis Pharmacy ou Hardware selon le préfixe de route.
 */
class SupplierController extends Controller
{
    private function getModule(): string
    {
        $prefix = request()->route()?->getPrefix();
        return $prefix === 'hardware' ? 'Hardware' : 'Pharmacy';
    }

    public function __construct(
        private readonly CreateSupplierUseCase $createSupplierUseCase,
        private readonly UpdateSupplierUseCase $updateSupplierUseCase,
        private readonly ActivateSupplierUseCase $activateSupplierUseCase,
        private readonly DeactivateSupplierUseCase $deactivateSupplierUseCase
    ) {
    }

    /**
     * Affiche la liste des fournisseurs.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;
        $isRoot = $user->type === 'ROOT';

        // Paramètres de filtrage
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $perPage = (int) $request->input('per_page', 15);

        // Query builder
        /** @var \Illuminate\Database\Eloquent\Builder<SupplierModel> $query */
        $query = SupplierModel::query();

        if (!$isRoot) {
            $query->where('shop_id', $shopId);
        }

        // Appliquer les filtres
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Pagination
        $suppliers = $query->orderBy('name')
            ->paginate($perPage)
            ->through(fn ($supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_person' => $supplier->contact_person,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'status' => $supplier->status,
                'total_orders' => $supplier->total_orders,
                'created_at' => $supplier->created_at->format('d/m/Y'),
            ]);

        return Inertia::render($this->getModule() . '/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Crée un nouveau fournisseur.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;

        try {
            $dto = new CreateSupplierDTO(
                shopId: (int) $shopId,
                name: $validated['name'],
                contactPerson: $validated['contact_person'] ?? null,
                phone: $validated['phone'] ?? null,
                email: $validated['email'] ?? null,
                address: $validated['address'] ?? null
            );

            $supplier = $this->createSupplierUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur créé avec succès.',
                'supplier' => [
                    'id' => $supplier->getId(),
                    'name' => $supplier->getName(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur création fournisseur', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'shopId' => $shopId,
                'name' => $validated['name'] ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du fournisseur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Affiche les détails d'un fournisseur.
     */
    public function show(Request $request, string $id): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;
        $isRoot = $user->type === 'ROOT';

        /** @var SupplierModel|null $supplierModel */
        $supplierModel = SupplierModel::query()
            ->with(['purchaseOrders' => function ($q) {
                $q->orderByDesc('created_at')->limit(10);
            }])
            ->find($id);

        if (!$supplierModel) {
            abort(404, 'Fournisseur introuvable.');
        }

        // Vérifier l'accès
        if (!$isRoot && $supplierModel->shop_id !== $shopId) {
            abort(403, 'Accès non autorisé.');
        }

        // Formater les commandes récentes
        $recentOrders = [];
        $purchaseOrders = $supplierModel->purchaseOrders;
        $totalOrders = $purchaseOrders->count();
        foreach ($purchaseOrders as $po) {
            /** @var object $po */
            $recentOrders[] = [
                'id' => $po->id ?? '',
                'reference' => $po->reference ?? "PO-" . ($po->id ?? ''),
                'status' => $po->status ?? '',
                'total_amount' => $po->total_amount ?? 0,
                'created_at' => isset($po->created_at) && $po->created_at ? $po->created_at->format('d/m/Y') : '',
            ];
        }

        $supplier = [
            'id' => $supplierModel->id,
            'name' => $supplierModel->name,
            'contact_person' => $supplierModel->contact_person,
            'phone' => $supplierModel->phone,
            'email' => $supplierModel->email,
            'address' => $supplierModel->address,
            'status' => $supplierModel->status,
            'total_orders' => $totalOrders,
            'created_at' => $supplierModel->created_at->format('d/m/Y H:i'),
            'updated_at' => $supplierModel->updated_at->format('d/m/Y H:i'),
            'recent_orders' => $recentOrders,
        ];

        // Récupérer les prix fournisseur pour ce fournisseur
        $supplierPrices = SupplierProductPriceModel::query()
            ->where('supplier_id', $id)
            ->where('is_active', true)
            ->with('product:id,name,code,price_amount,price_currency')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($p) {
                /** @var ProductModel|null $product */
                $product = $p->product;
                return [
                    'id' => $p->id,
                    'product_id' => $p->product_id,
                    'product_name' => $product?->name ?? '',
                    'product_code' => $product?->code ?? '',
                    'product_price' => $product?->price_amount ?? 0,
                    'normal_price' => (float) $p->normal_price,
                    'agreed_price' => $p->agreed_price !== null ? (float) $p->agreed_price : null,
                    'effective_price' => (float) $p->effective_price,
                    'tax_rate' => (float) ($p->tax_rate ?? 0),
                    'price_with_tax' => (float) ($p->price_with_tax ?? $p->effective_price),
                    'effective_from' => $p->effective_from?->format('Y-m-d'),
                ];
            })
            ->toArray();

        // Récupérer les produits pour le drawer d'ajout de prix
        $products = ProductModel::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'price_amount', 'price_currency'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'price' => (float) $p->price_amount,
                'currency' => $p->price_currency,
            ])
            ->toArray();

        return Inertia::render($this->getModule() . '/Suppliers/Show', [
            'supplier' => $supplier,
            'supplierPrices' => $supplierPrices,
            'products' => $products,
        ]);
    }

    /**
     * Met à jour un fournisseur.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        try {
            $dto = new UpdateSupplierDTO(
                id: $id,
                name: $validated['name'],
                contactPerson: $validated['contact_person'] ?? null,
                phone: $validated['phone'] ?? null,
                email: $validated['email'] ?? null,
                address: $validated['address'] ?? null
            );

            $supplier = $this->updateSupplierUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur mis à jour avec succès.',
                'supplier' => [
                    'id' => $supplier->getId(),
                    'name' => $supplier->getName(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du fournisseur.',
            ], 500);
        }
    }

    /**
     * Active un fournisseur.
     */
    public function activate(Request $request, string $id): JsonResponse
    {
        try {
            $supplier = $this->activateSupplierUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur activé avec succès.',
                'supplier' => [
                    'id' => $supplier->getId(),
                    'status' => $supplier->getStatus()->getValue(),
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation du fournisseur.',
            ], 500);
        }
    }

    /**
     * Désactive un fournisseur.
     */
    public function deactivate(Request $request, string $id): JsonResponse
    {
        try {
            $supplier = $this->deactivateSupplierUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur désactivé avec succès.',
                'supplier' => [
                    'id' => $supplier->getId(),
                    'status' => $supplier->getStatus()->getValue(),
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation du fournisseur.',
            ], 500);
        }
    }

    /**
     * Retourne la liste des fournisseurs actifs (pour les selects/dropdowns).
     */
    public function listActive(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;

        $suppliers = SupplierModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'contact_person', 'phone', 'email']);

        return response()->json([
            'success' => true,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Modèle Excel pour l'import de fournisseurs.
     */
    public function importTemplate(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modèle Fournisseurs');

        $headers = [
            'nom',
            'personne_contact',
            'telephone',
            'email',
            'adresse',
        ];

        $colIndex = 1;
        foreach ($headers as $header) {
            $columnLetter = chr(ord('A') + $colIndex - 1);
            $sheet->setCellValue($columnLetter . '1', $header);
            $colIndex++;
        }

        // Exemple de ligne
        $sheet->setCellValue('A2', 'Fournisseur Démo');
        $sheet->setCellValue('B2', 'Contact Démo');
        $sheet->setCellValue('C2', '0990000000');
        $sheet->setCellValue('D2', 'fournisseur@example.com');
        $sheet->setCellValue('E2', 'Adresse du fournisseur');

        $filename = 'modele_import_fournisseurs_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Aperçu de l'import de fournisseurs (validation sans insertion).
     */
    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:10240',
        ], [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.mimes' => 'Le fichier doit être au format .xlsx ou .csv.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;
        if (!$shopId) {
            return response()->json(['message' => 'Shop ID introuvable. Veuillez sélectionner un dépôt.'], 403);
        }

        $file = $request->file('file');
        $path = $file->getRealPath();

        try {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'csv' || $ext === 'txt') {
                $reader = new Csv();
                $line = fgets(fopen($path, 'r'));
                $delimiter = strpos($line, ';') !== false ? ';' : ',';
                $reader->setDelimiter($delimiter);
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            Log::error('Supplier import preview: parse error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Impossible de lire le fichier : ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'total' => 0,
                'valid' => 0,
                'invalid' => 0,
                'errors' => [
                    ['line' => 1, 'field' => null, 'message' => 'Fichier vide.'],
                ],
                'sample' => [
                    'header' => [],
                    'rows' => [],
                ],
            ]);
        }

        $headerRow = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $dataRows = array_slice($rows, 1);

        if (!in_array('nom', $headerRow, true)) {
            return response()->json([
                'total' => count($dataRows),
                'valid' => 0,
                'invalid' => count($dataRows),
                'errors' => [
                    [
                        'line' => 1,
                        'field' => 'nom',
                        'message' => "Colonne obligatoire manquante : 'nom'.",
                    ],
                ],
                'sample' => [
                    'header' => $rows[0] ?? [],
                    'rows' => array_slice($dataRows, 0, 20),
                ],
            ]);
        }

        $existing = SupplierModel::query()
            ->where('shop_id', $shopId)
            ->get(['name', 'phone', 'email']);

        $existingKeyed = [];
        foreach ($existing as $s) {
            $key = mb_strtolower(trim($s->name)) . '|' . mb_strtolower(trim((string) $s->phone));
            $existingKeyed[$key] = true;
            if ($s->email) {
                $existingKeyed['email|' . mb_strtolower(trim((string) $s->email))] = true;
            }
        }

        $seenInFile = [];
        $valid = 0;
        $invalid = 0;
        $errors = [];

        foreach ($dataRows as $index => $row) {
            $lineNum = $index + 2;
            $rowAssoc = [];
            foreach ($headerRow as $i => $key) {
                $rowAssoc[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
            }

            if (!array_filter($rowAssoc, fn ($v) => $v !== '' && $v !== null)) {
                continue;
            }

            $lineErrors = [];

            $name = $rowAssoc['nom'] ?? '';
            if ($name === '') {
                $lineErrors[] = 'Nom obligatoire.';
            }

            $phone = $rowAssoc['telephone'] ?? '';
            $email = $rowAssoc['email'] ?? '';

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $lineErrors[] = 'Email invalide.';
            }

            if ($name !== '') {
                $key = mb_strtolower($name) . '|' . mb_strtolower($phone);
                if (isset($existingKeyed[$key])) {
                    $lineErrors[] = 'Fournisseur déjà existant (nom + téléphone).';
                }
                if (isset($seenInFile[$key])) {
                    $lineErrors[] = 'Fournisseur en double dans le fichier (nom + téléphone).';
                }
            }
            if ($email !== '') {
                $ekey = 'email|' . mb_strtolower($email);
                if (isset($existingKeyed[$ekey])) {
                    $lineErrors[] = 'Email déjà utilisé par un autre fournisseur.';
                }
                if (isset($seenInFile[$ekey])) {
                    $lineErrors[] = 'Email en double dans le fichier.';
                }
            }

            if (!empty($lineErrors)) {
                $invalid++;
                $errors[] = [
                    'line' => $lineNum,
                    'field' => null,
                    'message' => implode(' | ', $lineErrors),
                ];
            } else {
                $valid++;
                if ($name !== '') {
                    $seenInFile[mb_strtolower($name) . '|' . mb_strtolower($phone)] = true;
                }
                if ($email !== '') {
                    $seenInFile['email|' . mb_strtolower($email)] = true;
                }
            }
        }

        $total = $valid + $invalid;

        return response()->json([
            'total' => $total,
            'valid' => $valid,
            'invalid' => $invalid,
            'errors' => $errors,
            'sample' => [
                'header' => $rows[0] ?? [],
                'rows' => array_slice($dataRows, 0, 20),
            ],
        ]);
    }

    /**
     * Import effectif des fournisseurs.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:10240',
        ], [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.mimes' => 'Le fichier doit être au format .xlsx ou .csv.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;
        if (!$shopId) {
            return response()->json(['message' => 'Shop ID introuvable. Veuillez sélectionner un dépôt.'], 403);
        }

        $file = $request->file('file');
        $path = $file->getRealPath();

        try {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'csv' || $ext === 'txt') {
                $reader = new Csv();
                $line = fgets(fopen($path, 'r'));
                $delimiter = strpos($line, ';') !== false ? ';' : ',';
                $reader->setDelimiter($delimiter);
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            Log::error('Supplier import: parse error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Impossible de lire le fichier : ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'message' => 'Fichier vide.',
                'success' => 0,
                'failed' => 0,
                'total' => 0,
                'errors' => [],
            ]);
        }

        $headerRow = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $dataRows = array_slice($rows, 1);

        if (!in_array('nom', $headerRow, true)) {
            $failed = count($dataRows);
            return response()->json([
                'message' => "Colonne obligatoire manquante : 'nom'.",
                'success' => 0,
                'failed' => $failed,
                'total' => $failed,
                'errors' => ["Ligne 1: Colonne obligatoire manquante : 'nom'."],
            ], 422);
        }

        $existing = SupplierModel::query()
            ->where('shop_id', $shopId)
            ->get(['name', 'phone', 'email']);

        $existingKeyed = [];
        foreach ($existing as $s) {
            $key = mb_strtolower(trim($s->name)) . '|' . mb_strtolower(trim((string) $s->phone));
            $existingKeyed[$key] = true;
            if ($s->email) {
                $existingKeyed['email|' . mb_strtolower(trim((string) $s->email))] = true;
            }
        }

        $seenInFile = [];
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($dataRows as $index => $row) {
            $lineNum = $index + 2;
            $rowAssoc = [];
            foreach ($headerRow as $i => $key) {
                $rowAssoc[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
            }

            if (!array_filter($rowAssoc, fn ($v) => $v !== '' && $v !== null)) {
                continue;
            }

            $lineErrors = [];

            $name = $rowAssoc['nom'] ?? '';
            if ($name === '') {
                $lineErrors[] = 'Nom obligatoire.';
            }

            $phone = $rowAssoc['telephone'] ?? '';
            $email = $rowAssoc['email'] ?? '';
            $contact = $rowAssoc['personne_contact'] ?? '';
            $address = $rowAssoc['adresse'] ?? '';

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $lineErrors[] = 'Email invalide.';
            }

            if ($name !== '') {
                $key = mb_strtolower($name) . '|' . mb_strtolower($phone);
                if (isset($existingKeyed[$key])) {
                    $lineErrors[] = 'Fournisseur déjà existant (nom + téléphone).';
                }
                if (isset($seenInFile[$key])) {
                    $lineErrors[] = 'Fournisseur en double dans le fichier (nom + téléphone).';
                }
            }
            if ($email !== '') {
                $ekey = 'email|' . mb_strtolower($email);
                if (isset($existingKeyed[$ekey])) {
                    $lineErrors[] = 'Email déjà utilisé par un autre fournisseur.';
                }
                if (isset($seenInFile[$ekey])) {
                    $lineErrors[] = 'Email en double dans le fichier.';
                }
            }

            if (!empty($lineErrors)) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . implode(' | ', $lineErrors);
                continue;
            }

            try {
                $dto = new CreateSupplierDTO(
                    shopId: (int) $shopId,
                    name: $name,
                    contactPerson: $contact !== '' ? $contact : null,
                    phone: $phone !== '' ? $phone : null,
                    email: $email !== '' ? $email : null,
                    address: $address !== '' ? $address : null
                );

                $supplier = $this->createSupplierUseCase->execute($dto);

                if ($name !== '') {
                    $seenInFile[mb_strtolower($name) . '|' . mb_strtolower($phone)] = true;
                }
                if ($email !== '') {
                    $seenInFile['email|' . mb_strtolower($email)] = true;
                }

                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . $e->getMessage();
            }
        }

        $total = $success + $failed;

        return response()->json([
            'message' => 'Import fournisseurs terminé.',
            'success' => $success,
            'failed' => $failed,
            'total' => $total,
            'errors' => $errors,
        ]);
    }
}
