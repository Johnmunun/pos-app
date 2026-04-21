<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Src\Application\Billing\Services\FeatureLimitService;
use Src\Application\Ecommerce\DTO\CreateCustomerDTO;
use Src\Application\Ecommerce\UseCases\CreateCustomerUseCase;
use Src\Domain\Ecommerce\Repositories\CustomerRepositoryInterface;
use Src\Infrastructure\Ecommerce\Models\CustomerModel;
use Illuminate\Support\Facades\Log;

class CustomerController
{
    public function __construct(
        private readonly CreateCustomerUseCase $createCustomerUseCase,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly FeatureLimitService $featureLimitService,
    ) {
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $userModel = \App\Models\User::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        if (!$request->user()?->hasPermission('ecommerce.view')) {
            abort(403, 'Vous n\'avez pas la permission de voir les clients.');
        }

        $shopId = $this->getShopId($request);
        $activeOnly = $request->input('active_only', false);

        $customers = $this->customerRepository->findByShop($shopId, $activeOnly);

        $customersData = array_map(function ($customer) {
            return [
                'id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'first_name' => $customer->getFirstName(),
                'last_name' => $customer->getLastName(),
                'full_name' => $customer->getFullName(),
                'phone' => $customer->getPhone(),
                'total_orders' => $customer->getTotalOrders(),
                'total_spent' => $customer->getTotalSpent()->getAmount(),
                'is_active' => $customer->isActive(),
                'created_at' => $customer->getCreatedAt()->format('d/m/Y'),
            ];
        }, $customers);

        return Inertia::render('Ecommerce/Customers/Index', [
            'customers' => $customersData,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()?->hasPermission('ecommerce.create')) {
            abort(403, 'Vous n\'avez pas la permission de créer des clients.');
        }
        $this->featureLimitService->assertCanCreateCustomer((string) ($request->user()?->tenant_id ?? ''));

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'default_shipping_address' => 'nullable|string',
            'default_billing_address' => 'nullable|string',
        ]);

        $shopId = $this->getShopId($request);

        $dto = new CreateCustomerDTO(
            $shopId,
            $validated['email'],
            $validated['first_name'],
            $validated['last_name'],
            $validated['phone'] ?? null,
            $validated['default_shipping_address'] ?? null,
            $validated['default_billing_address'] ?? null
        );

        try {
            $customer = $this->createCustomerUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Client créé avec succès.',
                'customer' => [
                    'id' => $customer->getId(),
                    'email' => $customer->getEmail(),
                    'full_name' => $customer->getFullName(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Téléchargement du modèle Excel pour l'import de clients ecommerce.
     */
    public function importTemplate(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modèle Clients Ecommerce');

        $headers = [
            'prenom',
            'nom',
            'email',
            'telephone',
            'adresse_livraison',
            'adresse_facturation',
            'actif',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray(
            [
                'Jean',
                'Dupont',
                'jean.dupont@example.com',
                '0999999999',
                'Avenue de la Paix 123, Kinshasa',
                'Avenue de la Paix 123, Kinshasa',
                'oui',
            ],
            null,
            'A2'
        );

        $filename = 'modele_import_clients_ecommerce_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Aperçu de l'import de clients ecommerce (validation sans insertion).
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

        $shopId = $this->getShopId($request);
        $file = $request->file('file');
        $path = $file->getRealPath();

        $sampleHeader = [];
        $sampleRows = [];
        try {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'csv' || $ext === 'txt') {
                $reader = new Csv();
                $handle = fopen($path, 'r');
                $line = $handle ? fgets($handle) : null;
                if ($handle) {
                    fclose($handle);
                }
                $delimiter = $line !== null && strpos($line, ';') !== false ? ';' : ',';
                $reader->setDelimiter($delimiter);
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $allRows = $sheet->toArray();
            $sampleHeader = $allRows[0] ?? [];
            $dataRows = array_slice($allRows, 1);
            $sampleRows = array_slice($dataRows, 0, 20);
        } catch (\Throwable $e) {
            Log::error('Ecommerce customer import preview: parse error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Impossible de lire le fichier : ' . $e->getMessage()], 422);
        }

        $rows = $sheet->toArray();
        if (empty($rows)) {
            return response()->json([
                'total' => 0,
                'valid' => 0,
                'invalid' => 0,
                'errors' => [],
                'sample' => ['header' => [], 'rows' => []],
            ]);
        }

        $headerRow = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $dataRows = array_slice($rows, 1);

        $requiredCols = ['prenom', 'nom', 'email'];
        foreach ($requiredCols as $col) {
            if (!in_array($col, $headerRow, true)) {
                return response()->json([
                    'message' => "Colonne obligatoire manquante : '{$col}'.",
                    'total' => count($dataRows),
                    'valid' => 0,
                    'invalid' => count($dataRows),
                    'errors' => [['line' => 1, 'field' => $col, 'message' => "Colonne obligatoire manquante : '{$col}'."]],
                    'sample' => ['header' => $sampleHeader, 'rows' => $sampleRows],
                ], 422);
            }
        }

        $existing = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->get(['email']);
        $existingEmails = $existing->pluck('email')->filter()->map(fn ($e) => mb_strtolower(trim((string) $e)))->flip()->all();

        $seen = [];
        $valid = 0;
        $invalid = 0;
        $errorsDetailed = [];

        foreach ($dataRows as $index => $row) {
            $lineNum = $index + 2;
            $rowAssoc = [];
            foreach ($headerRow as $i => $key) {
                $rowAssoc[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
            }

            if (!array_filter($rowAssoc, fn ($v) => $v !== '')) {
                continue;
            }

            $lineErrors = [];
            $firstName = $rowAssoc['prenom'] ?? '';
            $lastName = $rowAssoc['nom'] ?? '';
            $email = trim((string) ($rowAssoc['email'] ?? ''));

            if ($firstName === '') {
                $lineErrors[] = 'Prénom obligatoire.';
            }
            if ($lastName === '') {
                $lineErrors[] = 'Nom obligatoire.';
            }
            if ($email === '') {
                $lineErrors[] = 'Email obligatoire.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $lineErrors[] = 'Email invalide.';
            } elseif (isset($existingEmails[mb_strtolower($email)])) {
                $lineErrors[] = 'Email déjà utilisé.';
            } elseif (isset($seen[mb_strtolower($email)])) {
                $lineErrors[] = 'Email en double dans le fichier.';
            }

            if (!empty($lineErrors)) {
                $invalid++;
                $errorsDetailed[] = ['line' => $lineNum, 'field' => null, 'message' => implode(' | ', $lineErrors)];
            } else {
                $valid++;
                if ($email !== '') {
                    $seen[mb_strtolower($email)] = true;
                }
            }
        }

        return response()->json([
            'total' => count($dataRows),
            'valid' => $valid,
            'invalid' => $invalid,
            'errors' => $errorsDetailed,
            'sample' => ['header' => $sampleHeader, 'rows' => $sampleRows],
        ]);
    }

    /**
     * Import de clients ecommerce depuis fichier Excel/CSV.
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

        $shopId = $this->getShopId($request);
        $file = $request->file('file');
        $path = $file->getRealPath();

        try {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'csv' || $ext === 'txt') {
                $reader = new Csv();
                $handle = fopen($path, 'r');
                $line = $handle ? fgets($handle) : null;
                if ($handle) {
                    fclose($handle);
                }
                $delimiter = $line !== null && strpos($line, ';') !== false ? ';' : ',';
                $reader->setDelimiter($delimiter);
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            Log::error('Ecommerce customer import: parse error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Impossible de lire le fichier : ' . $e->getMessage(),
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'message' => 'Fichier vide.',
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ]);
        }

        $headerRow = array_map('trim', array_map('strtolower', (array) $rows[0]));
        $dataRows = array_slice($rows, 1);

        $requiredCols = ['prenom', 'nom', 'email'];
        foreach ($requiredCols as $col) {
            if (!in_array($col, $headerRow, true)) {
                return response()->json([
                    'message' => "Colonne obligatoire manquante : '{$col}'.",
                    'total' => count($dataRows),
                    'success' => 0,
                    'failed' => count($dataRows),
                    'errors' => [],
                ], 422);
            }
        }

        $existing = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->get(['email']);
        $existingEmails = $existing->pluck('email')->filter()->map(fn ($e) => mb_strtolower(trim((string) $e)))->flip()->all();

        $seen = [];
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($dataRows as $index => $row) {
            $lineNum = $index + 2;
            $rowAssoc = [];
            foreach ($headerRow as $i => $key) {
                $rowAssoc[$key] = isset($row[$i]) ? trim((string) $row[$i]) : '';
            }

            if (!array_filter($rowAssoc, fn ($v) => $v !== '')) {
                continue;
            }

            $lineErrors = [];
            $firstName = $rowAssoc['prenom'] ?? '';
            $lastName = $rowAssoc['nom'] ?? '';
            $email = trim((string) ($rowAssoc['email'] ?? ''));

            if ($firstName === '') {
                $lineErrors[] = 'Prénom obligatoire.';
            }
            if ($lastName === '') {
                $lineErrors[] = 'Nom obligatoire.';
            }
            if ($email === '') {
                $lineErrors[] = 'Email obligatoire.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $lineErrors[] = 'Email invalide.';
            } elseif (isset($existingEmails[mb_strtolower($email)])) {
                $lineErrors[] = 'Email déjà utilisé.';
            } elseif (isset($seen[mb_strtolower($email)])) {
                $lineErrors[] = 'Email en double dans le fichier.';
            }

            $phone = $rowAssoc['telephone'] ?? '';
            $shippingAddr = $rowAssoc['adresse_livraison'] ?? '';
            $billingAddr = $rowAssoc['adresse_facturation'] ?? '';
            $activeRaw = mb_strtolower($rowAssoc['actif'] ?? '');
            $isActive = !in_array($activeRaw, ['non', 'no', '0', 'false'], true);

            if (!empty($lineErrors)) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . implode(' | ', $lineErrors);
                continue;
            }

            try {
                $dto = new CreateCustomerDTO(
                    $shopId,
                    $email,
                    $firstName,
                    $lastName,
                    $phone ?: null,
                    $shippingAddr ?: null,
                    $billingAddr ?: null
                );
                $this->createCustomerUseCase->execute($dto);
                $seen[mb_strtolower($email)] = true;
                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . $e->getMessage();
            }
        }

        $total = $success + $failed;

        return response()->json([
            'message' => 'Import clients ecommerce terminé.',
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }
}
