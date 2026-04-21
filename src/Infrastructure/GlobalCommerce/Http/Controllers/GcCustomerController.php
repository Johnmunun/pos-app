<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;
use Src\Application\Billing\Services\FeatureLimitService;

class GcCustomerController
{
    public function __construct(
        private readonly FeatureLimitService $featureLimitService,
    ) {
    }

    private function getTenantId(Request $request): int
    {
        $user = $request->user();
        if ($user === null || !$user->tenant_id) {
            abort(403, 'Tenant not found.');
        }

        return (int) $user->tenant_id;
    }

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $search = $request->input('search', '');

        $query = Customer::query()->where('tenant_id', $tenantId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $customers = $query->orderBy('full_name')
            ->limit(100)
            ->get()
            ->map(fn (Customer $c) => [
                'id' => (string) $c->id,
                'full_name' => $c->full_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'is_active' => (bool) $c->is_active,
            ])
            ->values()
            ->all();

        return Inertia::render('Commerce/Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Commerce/Customers/Create');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $this->featureLimitService->assertCanCreateCustomer((string) $tenantId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $code = $this->generateCustomerCode($tenantId);

        Customer::create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'first_name' => null,
            'last_name' => null,
            'full_name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => true,
        ]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client créé.']);
        }
        return redirect()->route('commerce.customers.index')->with('success', 'Client créé.');
    }

    public function update(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $customer = Customer::where('tenant_id', $tenantId)->find($id);
        
        if (!$customer) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Client introuvable.'], 404);
            }
            return redirect()->route('commerce.customers.index')->with('error', 'Client introuvable.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $customer->update([
            'full_name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client mis à jour.']);
        }
        return redirect()->route('commerce.customers.index')->with('success', 'Client mis à jour.');
    }

    private function generateCustomerCode(int $tenantId): string
    {
        do {
            $code = 'C' . $tenantId . '-' . Str::upper(Str::random(6));
        } while (
            Customer::where('tenant_id', $tenantId)
                ->where('code', $code)
                ->exists()
        );

        return $code;
    }

    /**
     * Modèle Excel pour l'import de clients Global Commerce.
     */
    public function importTemplate(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modèle Clients Commerce');

        $headers = [
            'nom',
            'email',
            'telephone',
            'adresse',
            'actif',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $sheet->fromArray(
            [
                'Client Démo',
                'client@example.com',
                '0999999999',
                'Avenue de la Paix 123',
                'oui',
            ],
            null,
            'A2'
        );

        $filename = 'modele_import_clients_commerce_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import simple de clients Global Commerce.
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

        $tenantId = $this->getTenantId($request);

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
            Log::error('GcCustomer import: parse error', ['error' => $e->getMessage()]);
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

        if (!in_array('nom', $headerRow, true)) {
            return response()->json([
                'message' => "Colonne obligatoire manquante : 'nom'.",
                'total' => count($dataRows),
                'success' => 0,
                'failed' => count($dataRows),
                'errors' => [],
            ], 422);
        }

        $existing = Customer::query()
            ->where('tenant_id', $tenantId)
            ->get(['full_name', 'phone', 'email']);
        $existingKeyed = [];
        foreach ($existing as $c) {
            $key = mb_strtolower(trim($c->full_name)) . '|' . mb_strtolower(trim((string) $c->phone));
            $existingKeyed[$key] = true;
            if ($c->email) {
                $existingKeyed['email|' . mb_strtolower(trim((string) $c->email))] = true;
            }
        }

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

            if (!array_filter($rowAssoc, fn ($v) => $v !== '' && $v !== null)) {
                continue;
            }

            $lineErrors = [];

            $name = $rowAssoc['nom'] ?? '';
            if ($name === '') {
                $lineErrors[] = 'Nom obligatoire.';
            }

            $email = $rowAssoc['email'] ?? '';
            $phone = $rowAssoc['telephone'] ?? '';
            $address = $rowAssoc['adresse'] ?? '';

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $lineErrors[] = 'Email invalide.';
            }

            if ($name !== '') {
                $key = mb_strtolower($name) . '|' . mb_strtolower($phone);
                if (isset($existingKeyed[$key])) {
                    $lineErrors[] = 'Client déjà existant (nom + téléphone).';
                }
                if (isset($seen[$key])) {
                    $lineErrors[] = 'Client en double dans le fichier.';
                }
            }
            if ($email !== '') {
                $ekey = 'email|' . mb_strtolower($email);
                if (isset($existingKeyed[$ekey])) {
                    $lineErrors[] = 'Email déjà utilisé par un autre client.';
                }
                if (isset($seen[$ekey])) {
                    $lineErrors[] = 'Email en double dans le fichier.';
                }
            }

            $activeRaw = mb_strtolower($rowAssoc['actif'] ?? '');
            $isActive = !in_array($activeRaw, ['non', 'no', '0', 'false'], true);

            if (!empty($lineErrors)) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . implode(' | ', $lineErrors);
                continue;
            }

            try {
                $code = $this->generateCustomerCode($tenantId);

                Customer::create([
                    'tenant_id' => $tenantId,
                    'code' => $code,
                    'first_name' => null,
                    'last_name' => null,
                    'full_name' => $name,
                    'email' => $email ?: null,
                    'phone' => $phone ?: null,
                    'address' => $address ?: null,
                    'is_active' => $isActive,
                ]);

                if ($name !== '') {
                    $seen[mb_strtolower($name) . '|' . mb_strtolower($phone)] = true;
                }
                if ($email !== '') {
                    $seen['email|' . mb_strtolower($email)] = true;
                }

                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Ligne {$lineNum}: " . $e->getMessage();
            }
        }

        $total = $success + $failed;

        return response()->json([
            'message' => 'Import clients terminé.',
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }
}

