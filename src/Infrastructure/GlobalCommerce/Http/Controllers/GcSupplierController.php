<?php

namespace Src\Infrastructure\GlobalCommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Ramsey\Uuid\Uuid;
use Src\Infrastructure\GlobalCommerce\Procurement\Models\SupplierModel;
use Illuminate\Support\Facades\Log;

class GcSupplierController
{
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $depotId = $request->session()->get('current_depot_id');

        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shop = \App\Models\Shop::where('depot_id', (int) $depotId)
                ->where('tenant_id', $user->tenant_id)
                ->first();
            if ($shop) {
                return (string) $shop->id;
            }
        }

        if ($user->shop_id !== null && $user->shop_id !== '') {
            return (string) $user->shop_id;
        }

        if ($user->tenant_id) {
            return (string) $user->tenant_id;
        }

        abort(403, 'Shop ID not found.');
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $suppliers = SupplierModel::byShop($shopId)->orderBy('name')->get();
        $list = $suppliers->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'email' => $s->email,
            'phone' => $s->phone,
            'address' => $s->address,
            'is_active' => $s->is_active,
        ])->values()->all();
        return Inertia::render('Commerce/Suppliers/Index', [
            'suppliers' => $list,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Commerce/Suppliers/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);
        SupplierModel::create([
            'id' => Uuid::uuid4()->toString(),
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => true,
        ]);
        return redirect()->route('commerce.suppliers.index')->with('success', 'Fournisseur créé.');
    }

    public function edit(Request $request, string $id): Response|RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $supplier = SupplierModel::byShop($shopId)->find($id);
        if (!$supplier) {
            return redirect()->route('commerce.suppliers.index')->with('error', 'Fournisseur introuvable.');
        }
        return Inertia::render('Commerce/Suppliers/Edit', [
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'address' => $supplier->address,
                'is_active' => $supplier->is_active,
            ],
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $supplier = SupplierModel::byShop($shopId)->find($id);
        if (!$supplier) {
            return redirect()->route('commerce.suppliers.index')->with('error', 'Fournisseur introuvable.');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $supplier->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : $supplier->is_active,
        ]);
        return redirect()->route('commerce.suppliers.index')->with('success', 'Fournisseur mis à jour.');
    }

    public function toggleActive(Request $request, string $id): RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $supplier = SupplierModel::byShop($shopId)->find($id);
        if (!$supplier) {
            return redirect()->route('commerce.suppliers.index')->with('error', 'Fournisseur introuvable.');
        }
        $supplier->update(['is_active' => !$supplier->is_active]);
        $label = $supplier->is_active ? 'activé' : 'désactivé';
        return redirect()->route('commerce.suppliers.index')->with('success', "Fournisseur {$label}.");
    }

    /**
     * Modèle Excel pour l'import de fournisseurs Global Commerce.
     */
    public function importTemplate(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modèle Fournisseurs Commerce');

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
                'Fournisseur Démo',
                'fournisseur@example.com',
                '0990000000',
                'Adresse du fournisseur',
                'oui',
            ],
            null,
            'A2'
        );

        $filename = 'modele_import_fournisseurs_commerce_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import simple de fournisseurs Global Commerce.
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
            Log::error('GcSupplier import: parse error', ['error' => $e->getMessage()]);
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

        $existing = SupplierModel::byShop($shopId)->get(['name', 'phone', 'email']);
        $existingKeyed = [];
        foreach ($existing as $s) {
            $key = mb_strtolower(trim($s->name)) . '|' . mb_strtolower(trim((string) $s->phone));
            $existingKeyed[$key] = true;
            if ($s->email) {
                $existingKeyed['email|' . mb_strtolower(trim((string) $s->email))] = true;
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
                    $lineErrors[] = 'Fournisseur déjà existant (nom + téléphone).';
                }
                if (isset($seen[$key])) {
                    $lineErrors[] = 'Fournisseur en double dans le fichier.';
                }
            }
            if ($email !== '') {
                $ekey = 'email|' . mb_strtolower($email);
                if (isset($existingKeyed[$ekey])) {
                    $lineErrors[] = 'Email déjà utilisé par un autre fournisseur.';
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
                SupplierModel::create([
                    'id' => Uuid::uuid4()->toString(),
                    'shop_id' => $shopId,
                    'name' => $name,
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
            'message' => 'Import fournisseurs terminé.',
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }
}
