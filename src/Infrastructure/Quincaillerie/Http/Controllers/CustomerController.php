<?php

declare(strict_types=1);

namespace Src\Infrastructure\Quincaillerie\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Quincaillerie\DTO\CreateCustomerDTO;
use Src\Application\Quincaillerie\DTO\UpdateCustomerDTO;
use Src\Application\Quincaillerie\UseCases\Customer\CreateCustomerUseCase;
use Src\Application\Quincaillerie\UseCases\Customer\UpdateCustomerUseCase;
use Src\Application\Quincaillerie\UseCases\Customer\ActivateCustomerUseCase;
use Src\Application\Quincaillerie\UseCases\Customer\DeactivateCustomerUseCase;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Infrastructure\Quincaillerie\Models\CustomerModel;
use Src\Application\Quincaillerie\Services\DepotFilterService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

/**
 * Controller: CustomerController
 *
 * Gère les requêtes HTTP pour le module Clients Quincaillerie.
 * Module complètement séparé de Pharmacy.
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly CreateCustomerUseCase $createCustomerUseCase,
        private readonly UpdateCustomerUseCase $updateCustomerUseCase,
        private readonly ActivateCustomerUseCase $activateCustomerUseCase,
        private readonly DeactivateCustomerUseCase $deactivateCustomerUseCase,
        private readonly DepotFilterService $depotFilterService,
        private readonly FeatureLimitService $featureLimitService,
    ) {
    }

    /**
     * Affiche la liste des clients.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $this->featureLimitService->assertCanCreateCustomer((string) ($user->tenant_id ?? ''));
        $shopId = $user->shop_id ?? $user->tenant_id;
        $isRoot = $user->type === 'ROOT';

        // Paramètres de filtrage
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $customerType = $request->input('customer_type', '');
        $perPage = (int) $request->input('per_page', 15);

        /** @var \Illuminate\Database\Eloquent\Builder<CustomerModel> $query */
        $query = CustomerModel::query();

        if (!$isRoot) {
            $query->where('shop_id', $shopId);
        }

        // Appliquer le filtrage par dépôt selon les permissions
        $query = $this->depotFilterService->applyDepotFilter($query, $request, 'depot_id');

        // Appliquer les filtres
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($customerType)) {
            $query->where('customer_type', $customerType);
        }

        // Pagination
        /** @var \Illuminate\Pagination\LengthAwarePaginator<\Src\Infrastructure\Quincaillerie\Models\CustomerModel> $paginator */
        $paginator = $query->orderBy('name')->paginate($perPage);
        $customers = $paginator->through(function ($customer) {
            /** @var \Src\Infrastructure\Quincaillerie\Models\CustomerModel $customer */
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'customer_type' => $customer->customer_type,
                'customer_type_label' => $customer->customer_type === 'company' ? 'Entreprise' : 'Particulier',
                'tax_number' => $customer->tax_number,
                'credit_limit' => $customer->credit_limit,
                'status' => $customer->status,
                'created_at' => $customer->created_at->format('d/m/Y'),
            ];
        });

        return Inertia::render('Hardware/Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'customer_type' => $customerType,
                'per_page' => $perPage,
            ],
            'routePrefix' => 'hardware',
        ]);
    }

    /**
     * Crée un nouveau client.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'customer_type' => 'nullable|string|in:individual,company',
            'tax_number' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;

        try {
            $dto = new CreateCustomerDTO(
                shopId: (int) $shopId,
                name: $validated['name'],
                phone: $validated['phone'] ?? null,
                email: $validated['email'] ?? null,
                address: $validated['address'] ?? null,
                customerType: $validated['customer_type'] ?? 'individual',
                taxNumber: $validated['tax_number'] ?? null,
                creditLimit: isset($validated['credit_limit']) ? (float) $validated['credit_limit'] : null
            );

            $customer = $this->createCustomerUseCase->execute($dto);

            // Assigner le dépôt selon les permissions
            $effectiveDepotId = $this->depotFilterService->getEffectiveDepotId($request);
            if ($effectiveDepotId !== null) {
                $customerModel = CustomerModel::find($customer->getId());
                if ($customerModel) {
                    $customerModel->update(['depot_id' => $effectiveDepotId]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Client créé avec succès.',
                'customer' => [
                    'id' => $customer->getId(),
                    'name' => $customer->getName(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur création client', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du client.',
            ], 500);
        }
    }

    /**
     * Affiche les détails d'un client.
     */
    public function show(Request $request, string $id): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;
        $isRoot = $user->type === 'ROOT';

        /** @var CustomerModel|null $customerModel */
        $customerModel = CustomerModel::query()->find($id);

        if (!$customerModel) {
            abort(404, 'Client introuvable.');
        }

        // Vérifier l'accès
        if (!$isRoot && $customerModel->shop_id !== $shopId) {
            abort(403, 'Accès non autorisé.');
        }

        $customer = [
            'id' => $customerModel->id,
            'name' => $customerModel->name,
            'phone' => $customerModel->phone,
            'email' => $customerModel->email,
            'address' => $customerModel->address,
            'customer_type' => $customerModel->customer_type,
            'customer_type_label' => $customerModel->customer_type === 'company' ? 'Entreprise' : 'Particulier',
            'tax_number' => $customerModel->tax_number,
            'credit_limit' => $customerModel->credit_limit,
            'status' => $customerModel->status,
            'created_at' => $customerModel->created_at->format('d/m/Y H:i'),
            'updated_at' => $customerModel->updated_at->format('d/m/Y H:i'),
        ];

        return Inertia::render('Hardware/Customers/Show', [
            'customer' => $customer,
            'routePrefix' => 'hardware',
        ]);
    }

    /**
     * Met à jour un client.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'customer_type' => 'nullable|string|in:individual,company',
            'tax_number' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        try {
            $dto = new UpdateCustomerDTO(
                id: $id,
                name: $validated['name'],
                phone: $validated['phone'] ?? null,
                email: $validated['email'] ?? null,
                address: $validated['address'] ?? null,
                customerType: $validated['customer_type'] ?? null,
                taxNumber: $validated['tax_number'] ?? null,
                creditLimit: isset($validated['credit_limit']) ? (float) $validated['credit_limit'] : null
            );

            $customer = $this->updateCustomerUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Client mis à jour avec succès.',
                'customer' => [
                    'id' => $customer->getId(),
                    'name' => $customer->getName(),
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
                'message' => 'Erreur lors de la mise à jour du client.',
            ], 500);
        }
    }

    /**
     * Active un client.
     */
    public function activate(Request $request, string $id): JsonResponse
    {
        try {
            $customer = $this->activateCustomerUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'Client activé avec succès.',
                'customer' => [
                    'id' => $customer->getId(),
                    'status' => $customer->getStatus(),
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
                'message' => 'Erreur lors de l\'activation du client.',
            ], 500);
        }
    }

    /**
     * Désactive un client.
     */
    public function deactivate(Request $request, string $id): JsonResponse
    {
        try {
            $customer = $this->deactivateCustomerUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'Client désactivé avec succès.',
                'customer' => [
                    'id' => $customer->getId(),
                    'status' => $customer->getStatus(),
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
                'message' => 'Erreur lors de la désactivation du client.',
            ], 500);
        }
    }

    /**
     * Retourne la liste des clients actifs (pour les selects/dropdowns).
     */
    public function listActive(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }
        $shopId = $user->shop_id ?? $user->tenant_id;

        $customers = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'customer_type']);

        return response()->json([
            'success' => true,
            'customers' => $customers,
        ]);
    }

    /**
     * Modèle Excel pour l'import de clients Hardware.
     */
    public function importTemplate(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modèle Clients Hardware');

        $headers = [
            'nom',
            'telephone',
            'email',
            'adresse',
            'type_client',
            'num_tva',
            'plafond_credit',
        ];

        $colIndex = 1;
        foreach ($headers as $header) {
            $columnLetter = chr(ord('A') + $colIndex - 1);
            $sheet->setCellValue($columnLetter . '1', $header);
            $colIndex++;
        }

        // Exemple de ligne
        $sheet->setCellValue('A2', 'Client Quincaillerie Démo');
        $sheet->setCellValue('B2', '0999999999');
        $sheet->setCellValue('C2', 'client.hardware@example.com');
        $sheet->setCellValue('D2', 'Avenue Industrielle 123, Kinshasa');
        $sheet->setCellValue('E2', 'particulier'); // particulier / entreprise
        $sheet->setCellValue('F2', '');
        $sheet->setCellValue('G2', 0);

        $filename = 'modele_import_clients_hardware_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Aperçu de l'import de clients Hardware.
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
            return response()->json(['message' => 'Shop ID introuvable.'], 403);
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
            Log::error('Hardware Customer import preview: parse error', ['error' => $e->getMessage()]);
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

        $existing = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->get(['name', 'phone', 'email']);

        $existingKeyed = [];
        foreach ($existing as $c) {
            $key = mb_strtolower(trim($c->name)) . '|' . mb_strtolower(trim((string) $c->phone));
            $existingKeyed[$key] = true;
            if ($c->email) {
                $existingKeyed['email|' . mb_strtolower(trim((string) $c->email))] = true;
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

            $typeRaw = $rowAssoc['type_client'] ?? '';
            if ($typeRaw !== '') {
                $val = mb_strtolower($typeRaw);
                $allowed = ['individual', 'company', 'particulier', 'entreprise'];
                if (!in_array($val, $allowed, true)) {
                    $lineErrors[] = "Type client invalide (utiliser particulier/entreprise) : {$typeRaw}.";
                }
            }

            $creditRaw = $rowAssoc['plafond_credit'] ?? '';
            if ($creditRaw !== '') {
                if (!is_numeric($creditRaw) || (float) $creditRaw < 0) {
                    $lineErrors[] = 'Plafond crédit doit être un nombre positif.';
                }
            }

            if ($name !== '') {
                $key = mb_strtolower($name) . '|' . mb_strtolower($phone);
                if (isset($existingKeyed[$key])) {
                    $lineErrors[] = 'Client déjà existant (nom + téléphone).';
                }
                if (isset($seenInFile[$key])) {
                    $lineErrors[] = 'Client en double dans le fichier (nom + téléphone).';
                }
            }
            if ($email !== '') {
                $ekey = 'email|' . mb_strtolower($email);
                if (isset($existingKeyed[$ekey])) {
                    $lineErrors[] = 'Email déjà utilisé par un autre client.';
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
     * Import effectif des clients Hardware.
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
            return response()->json(['message' => 'Shop ID introuvable.'], 403);
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
            Log::error('Hardware Customer import: parse error', ['error' => $e->getMessage()]);
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

        $existing = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->get(['name', 'phone', 'email']);

        $existingKeyed = [];
        foreach ($existing as $c) {
            $key = mb_strtolower(trim($c->name)) . '|' . mb_strtolower(trim((string) $c->phone));
            $existingKeyed[$key] = true;
            if ($c->email) {
                $existingKeyed['email|' . mb_strtolower(trim((string) $c->email))] = true;
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
            $address = $rowAssoc['adresse'] ?? '';

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $lineErrors[] = 'Email invalide.';
            }

            $typeRaw = $rowAssoc['type_client'] ?? '';
            $customerType = 'individual';
            if ($typeRaw !== '') {
                $val = mb_strtolower($typeRaw);
                if (in_array($val, ['company', 'entreprise'], true)) {
                    $customerType = 'company';
                } elseif (in_array($val, ['individual', 'particulier'], true)) {
                    $customerType = 'individual';
                } else {
                    $lineErrors[] = "Type client invalide (utiliser particulier/entreprise) : {$typeRaw}.";
                }
            }

            $creditRaw = $rowAssoc['plafond_credit'] ?? '';
            $creditLimit = null;
            if ($creditRaw !== '') {
                if (!is_numeric($creditRaw) || (float) $creditRaw < 0) {
                    $lineErrors[] = 'Plafond crédit doit être un nombre positif.';
                } else {
                    $creditLimit = (float) $creditRaw;
                }
            }

            if ($name !== '') {
                $key = mb_strtolower($name) . '|' . mb_strtolower($phone);
                if (isset($existingKeyed[$key])) {
                    $lineErrors[] = 'Client déjà existant (nom + téléphone).';
                }
                if (isset($seenInFile[$key])) {
                    $lineErrors[] = 'Client en double dans le fichier (nom + téléphone).';
                }
            }
            if ($email !== '') {
                $ekey = 'email|' . mb_strtolower($email);
                if (isset($existingKeyed[$ekey])) {
                    $lineErrors[] = 'Email déjà utilisé par un autre client.';
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
                $dto = new CreateCustomerDTO(
                    shopId: (int) $shopId,
                    name: $name,
                    phone: $phone !== '' ? $phone : null,
                    email: $email !== '' ? $email : null,
                    address: $address !== '' ? $address : null,
                    customerType: $customerType,
                    taxNumber: ($rowAssoc['num_tva'] ?? '') !== '' ? $rowAssoc['num_tva'] : null,
                    creditLimit: $creditLimit
                );

                $customer = $this->createCustomerUseCase->execute($dto);

                // Assigner le dépôt selon les permissions
                $effectiveDepotId = $this->depotFilterService->getEffectiveDepotId($request);
                if ($effectiveDepotId !== null) {
                    $customerModel = CustomerModel::find($customer->getId());
                    if ($customerModel) {
                        $customerModel->update(['depot_id' => $effectiveDepotId]);
                    }
                }

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
            'message' => 'Import clients Hardware terminé.',
            'success' => $success,
            'failed' => $failed,
            'total' => $total,
            'errors' => $errors,
        ]);
    }
}
