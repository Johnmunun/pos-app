<?php

namespace Src\Infrastructure\Mobile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Depot;
use App\Models\Tenant;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Src\Infrastructure\Logs\Models\UserLoginHistoryModel;
use Src\Infrastructure\Pharmacy\Models\CategoryModel as PharmacyCategoryModel;
use Src\Infrastructure\Pharmacy\Models\ProductModel as PharmacyProductModel;
use Src\Infrastructure\Pharmacy\Models\SaleModel as PharmacySaleModel;
use Src\Infrastructure\Quincaillerie\Models\CategoryModel as HardwareCategoryModel;
use Src\Infrastructure\Quincaillerie\Models\ProductModel as HardwareProductModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\CategoryModel as CommerceCategoryModel;
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel as CommerceProductModel;
use Src\Infrastructure\GlobalCommerce\Sales\Models\SaleModel as CommerceSaleModel;

class MobileAuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        // Validate + attempt credentials using the existing web LoginRequest rules.
        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            $message = (string) (collect($e->errors())->flatten()->first() ?? 'Invalid email or password.');
            return response()->json([
                'message' => $message,
                'code' => 'AUTH_INVALID_CREDENTIALS',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Authentication service unavailable. Please try again.',
                'code' => 'AUTH_SERVICE_UNAVAILABLE',
            ], 503);
        }

        /** @var UserModel|null $user */
        $user = Auth::user();
        if ($user === null) {
            return response()->json(['message' => 'Authentication failed.'], 401);
        }

        if (method_exists($user, 'isRoot') && $user->isRoot()) {
            return response()->json(['message' => 'ROOT users are not allowed on mobile.'], 403);
        }

        // Record login history (best-effort; must not break auth).
        try {
            UserLoginHistoryModel::create([
                'user_id' => (int) $user->id,
                'logged_in_at' => now(),
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'device' => php_uname('n'),
                'status' => 'success',
            ]);
        } catch (\Throwable) {
            // Ignore.
        }

        if (!Schema::hasTable('personal_access_tokens')) {
            return response()->json([
                'message' => 'Mobile authentication is not configured on server (Sanctum tokens table missing).',
                'code' => 'AUTH_TOKEN_STORE_MISSING',
            ], 503);
        }

        try {
            $token = $user->createToken('pos-mobile')->plainTextToken;
        } catch (\Throwable $e) {
            Log::error('Mobile login token creation failed', [
                'user_id' => (int) $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to create session token. Please try again later.',
                'code' => 'AUTH_TOKEN_CREATE_FAILED',
            ], 503);
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->serializeUser($user),
            'tenant' => $this->serializeTenant($user->tenant ?? $user->tenant_id),
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Revoke current token (does not affect other devices).
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out.'], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'user' => $this->serializeUser($user),
            'tenant' => $this->serializeTenant($user->tenant ?? $user->tenant_id),
            'permissions' => method_exists($user, 'permissionCodes') ? $user->permissionCodes() : [],
        ], 200);
    }

    /**
     * Mobile bootstrap for client UI initialization.
     * Keep it lightweight: tenant + depots + current sector.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        /** @var UserModel|null $user */
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $depots = $user->depots()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $tenant = $user->tenant;
        return response()->json([
            'user' => $this->serializeUser($user),
            'tenant' => $this->serializeTenant($tenant),
            'modules' => [
                'sector' => $tenant?->sector,
            ],
            'depots' => $depots->map(fn (Depot $d) => [
                'id' => (int) $d->id,
                'name' => $d->name,
                'code' => $d->code,
            ]),
            // UI theme: to be extended later (logo/colors) once we align mobile theme tokens.
            'theme' => [
                'brand_name' => $tenant?->name,
                'logo_url' => null,
            ],
        ], 200);
    }

    /**
     * POS bootstrap optimized for mobile startup.
     * Returns module-scoped lightweight data to avoid multiple round-trips.
     */
    public function posBootstrap(Request $request, string $module): JsonResponse
    {
        /** @var UserModel|null $user */
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $module = strtolower(trim($module));
        if (!in_array($module, ['pharmacy', 'hardware', 'commerce'], true)) {
            return response()->json(['message' => 'Invalid module.'], 422);
        }

        $shopId = $this->resolveShopId($request, $user);
        if ($shopId === null) {
            return response()->json(['message' => 'Shop not found for this user.'], 403);
        }

        $shop = Shop::query()->find($shopId);
        $tenantId = $user->tenant_id !== null ? (int) $user->tenant_id : ($shop?->tenant_id ?? null);
        $updatedSinceInput = trim((string) $request->input('updated_since', ''));
        $updatedSince = null;
        if ($updatedSinceInput !== '') {
            try {
                $updatedSince = new \DateTimeImmutable($updatedSinceInput);
            } catch (\Throwable) {
                return response()->json(['message' => 'Invalid updated_since datetime.'], 422);
            }
        }

        $categories = [];
        $products = [];
        $recentSales = [];
        $deletedIds = [
            'categories' => [],
            'products' => [],
            'customers' => [],
            'cash_registers' => [],
        ];

        if ($module === 'pharmacy') {
            $categoriesQuery = PharmacyCategoryModel::query()
                ->where('shop_id', (string) $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(120);
            if ($updatedSince !== null) {
                $categoriesQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
            }
            $categories = $categoriesQuery
                ->get(['id', 'name', 'updated_at'])
                ->map(fn (PharmacyCategoryModel $c) => ['id' => (string) $c->id, 'name' => (string) $c->name])
                ->values();

            $productsQuery = PharmacyProductModel::query()
                ->where('shop_id', (string) $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(250);
            if ($updatedSince !== null) {
                $productsQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
            }
            $products = $productsQuery
                ->get(['id', 'name', 'code', 'barcode', 'category_id', 'price_amount', 'price_currency', 'stock', 'updated_at'])
                ->map(fn (PharmacyProductModel $p) => [
                    'id' => (string) $p->id,
                    'name' => (string) $p->name,
                    'code' => (string) ($p->code ?? ''),
                    'barcode' => (string) ($p->barcode ?? ''),
                    'category_id' => $p->category_id ? (string) $p->category_id : null,
                    'price_amount' => (float) ($p->price_amount ?? 0),
                    'currency' => (string) ($p->price_currency ?? 'CDF'),
                    'stock' => (float) ($p->stock ?? 0),
                ])
                ->values();

            $recentSalesQuery = PharmacySaleModel::query()
                ->where('shop_id', (string) $shopId)
                ->orderByDesc('created_at')
                ->limit(20);
            if ($updatedSince !== null) {
                $recentSalesQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
            }
            $recentSales = $recentSalesQuery
                ->get(['id', 'status', 'total_amount', 'currency', 'created_at', 'updated_at'])
                ->map(fn (PharmacySaleModel $s) => [
                    'id' => (string) $s->id,
                    'status' => (string) ($s->status ?? ''),
                    'total_amount' => (float) ($s->total_amount ?? 0),
                    'currency' => (string) ($s->currency ?? 'CDF'),
                    'created_at' => $s->created_at->format(DATE_ATOM),
                ])
                ->values();

            if ($updatedSince !== null) {
                $deletedIds['categories'] = PharmacyCategoryModel::withTrashed()
                    ->where('shop_id', (string) $shopId)
                    ->whereNotNull('deleted_at')
                    ->where('deleted_at', '>=', $updatedSince->format('Y-m-d H:i:s'))
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->values()
                    ->all();

                $deletedIds['products'] = PharmacyProductModel::withTrashed()
                    ->where('shop_id', (string) $shopId)
                    ->whereNotNull('deleted_at')
                    ->where('deleted_at', '>=', $updatedSince->format('Y-m-d H:i:s'))
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->values()
                    ->all();
            }
        } elseif ($module === 'hardware') {
            $categoriesQuery = HardwareCategoryModel::query()
                ->where('shop_id', (int) $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(120);
            if ($updatedSince !== null) {
                $categoriesQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
            }
            $categories = $categoriesQuery
                ->get(['id', 'name', 'updated_at'])
                ->map(fn (HardwareCategoryModel $c) => ['id' => (string) $c->id, 'name' => (string) $c->name])
                ->values();

            $productsQuery = HardwareProductModel::query()
                ->where('shop_id', (int) $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(250);
            if ($updatedSince !== null) {
                $productsQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
            }
            $products = $productsQuery
                ->get(['id', 'name', 'code', 'barcode', 'category_id', 'price_amount', 'price_currency', 'stock', 'updated_at'])
                ->map(fn (HardwareProductModel $p) => [
                    'id' => (string) $p->id,
                    'name' => (string) $p->name,
                    'code' => (string) ($p->code ?? ''),
                    'barcode' => (string) ($p->barcode ?? ''),
                    'category_id' => $p->category_id ? (string) $p->category_id : null,
                    'price_amount' => (float) ($p->price_amount ?? 0),
                    'currency' => (string) ($p->price_currency ?? 'CDF'),
                    'stock' => (float) ($p->stock ?? 0),
                ])
                ->values();

            // Hardware sales are stored in pharmacy sales model.
            $recentSalesQuery = PharmacySaleModel::query()
                ->where('shop_id', (string) $shopId)
                ->orderByDesc('created_at')
                ->limit(20);
            if ($updatedSince !== null) {
                $recentSalesQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
            }
            $recentSales = $recentSalesQuery
                ->get(['id', 'status', 'total_amount', 'currency', 'created_at', 'updated_at'])
                ->map(fn (PharmacySaleModel $s) => [
                    'id' => (string) $s->id,
                    'status' => (string) ($s->status ?? ''),
                    'total_amount' => (float) ($s->total_amount ?? 0),
                    'currency' => (string) ($s->currency ?? 'CDF'),
                    'created_at' => $s->created_at->format(DATE_ATOM),
                ])
                ->values();

            if ($updatedSince !== null) {
                $deletedIds['categories'] = HardwareCategoryModel::withTrashed()
                    ->where('shop_id', (int) $shopId)
                    ->whereNotNull('deleted_at')
                    ->where('deleted_at', '>=', $updatedSince->format('Y-m-d H:i:s'))
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->values()
                    ->all();

                $deletedIds['products'] = HardwareProductModel::withTrashed()
                    ->where('shop_id', (int) $shopId)
                    ->whereNotNull('deleted_at')
                    ->where('deleted_at', '>=', $updatedSince->format('Y-m-d H:i:s'))
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->values()
                    ->all();
            }
        } else {
            $categoriesQuery = CommerceCategoryModel::query()
                ->where('shop_id', (int) $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(120);
            if ($updatedSince !== null) {
                $categoriesQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
            }
            $categories = $categoriesQuery
                ->get(['id', 'name', 'updated_at'])
                ->map(fn (CommerceCategoryModel $c) => ['id' => (string) $c->id, 'name' => (string) $c->name])
                ->values();

            $productsQuery = CommerceProductModel::query()
                ->where('shop_id', (int) $shopId)
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(250);
            if ($updatedSince !== null) {
                $productsQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
            }
            $products = $productsQuery
                ->get(['id', 'name', 'sku', 'barcode', 'category_id', 'sale_price_amount', 'sale_price_currency', 'stock', 'updated_at'])
                ->map(fn (CommerceProductModel $p) => [
                    'id' => (string) $p->id,
                    'name' => (string) $p->name,
                    'code' => (string) ($p->sku ?? ''),
                    'barcode' => (string) ($p->barcode ?? ''),
                    'category_id' => $p->category_id ? (string) $p->category_id : null,
                    'price_amount' => (float) ($p->sale_price_amount ?? 0),
                    'currency' => (string) ($p->sale_price_currency ?? 'USD'),
                    'stock' => (float) ($p->stock ?? 0),
                ])
                ->values();

            $recentSalesQuery = CommerceSaleModel::query()
                ->where('shop_id', (int) $shopId)
                ->orderByDesc('created_at')
                ->limit(20);
            if ($updatedSince !== null) {
                $recentSalesQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
            }
            $recentSales = $recentSalesQuery
                ->get(['id', 'status', 'total_amount', 'currency', 'created_at', 'updated_at'])
                ->map(fn (CommerceSaleModel $s) => [
                    'id' => (string) $s->id,
                    'status' => strtoupper((string) ($s->status ?? '')),
                    'total_amount' => (float) ($s->total_amount ?? 0),
                    'currency' => (string) ($s->currency ?? 'USD'),
                    'created_at' => $s->created_at->format(DATE_ATOM),
                ])
                ->values();
        }

        $customersQuery = Customer::query()
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->orderBy('full_name')
            ->limit(80);
        if ($updatedSince !== null) {
            $customersQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
        }
        $customers = $customersQuery
            ->get(['id', 'full_name', 'phone', 'email', 'updated_at'])
            ->map(fn (Customer $c) => [
                'id' => (string) $c->id,
                'full_name' => (string) ($c->full_name ?? ''),
                'phone' => (string) ($c->phone ?? ''),
                'email' => (string) ($c->email ?? ''),
            ])
            ->values();

        $cashRegistersQuery = CashRegister::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(30);
        if ($updatedSince !== null) {
            $cashRegistersQuery->where('updated_at', '>=', $updatedSince->format('Y-m-d H:i:s'));
        }
        $cashRegisters = $cashRegistersQuery
            ->get(['id', 'name', 'code', 'updated_at'])
            ->map(function (CashRegister $register) {
                $openSession = $register->openSession();
                return [
                    'id' => (int) $register->id,
                    'name' => (string) $register->name,
                    'code' => (string) ($register->code ?? ''),
                    'open_session' => $openSession ? [
                        'id' => (int) $openSession->id,
                        'opening_balance' => (float) $openSession->opening_balance,
                        'opened_at' => $openSession->opened_at->format(DATE_ATOM),
                    ] : null,
                ];
            })->values();

        return response()->json([
            'module' => $module,
            'user' => $this->serializeUser($user),
            'tenant' => $this->serializeTenant($user->tenant ?? $user->tenant_id),
            'context' => [
                'shop_id' => $shop ? (string) $shop->id : (string) $shopId,
                'shop_name' => $shop?->name,
                'depot_id' => $request->filled('depot_id') ? (int) $request->input('depot_id') : null,
            ],
            'catalog' => [
                'categories' => $categories,
                'products' => $products,
            ],
            'customers' => $customers,
            'cash_registers' => $cashRegisters,
            'recent_sales' => $recentSales,
            'deleted_ids' => $deletedIds,
            'sync' => [
                'mode' => $updatedSince !== null ? 'incremental' : 'full',
                'updated_since' => $updatedSince?->format(DATE_ATOM),
                'server_time' => now()->toAtomString(),
            ],
        ], 200);
    }

    private function serializeUser(UserModel $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) ($user->name ?? ''),
            'email' => (string) ($user->email ?? ''),
            'type' => (string) ($user->type ?? ''),
            'tenant_id' => $user->tenant_id !== null ? (int) $user->tenant_id : null,
            'shop_id' => $user->shop_id !== null ? (string) $user->shop_id : null,
        ];
    }

    private function serializeTenant(Tenant|int|string|null $tenant): ?array
    {
        if ($tenant instanceof Tenant) {
            return [
                'id' => (int) $tenant->id,
                'name' => (string) ($tenant->name ?? ''),
                'code' => (string) ($tenant->code ?? ''),
                'sector' => $tenant->sector,
                'slug' => $tenant->slug,
            ];
        }

        if (is_int($tenant) || is_string($tenant)) {
            $t = Tenant::query()->find($tenant);
            if ($t === null) {
                return null;
            }
            return $this->serializeTenant($t);
        }

        return null;
    }

    private function resolveShopId(Request $request, UserModel $user): ?string
    {
        $shopId = null;
        $depotId = $request->filled('depot_id') ? (int) $request->input('depot_id') : null;
        if ($depotId && $user->tenant_id !== null) {
            $shopByDepot = Shop::query()
                ->where('depot_id', $depotId)
                ->where('tenant_id', (int) $user->tenant_id)
                ->first();
            if ($shopByDepot) {
                $shopId = (string) $shopByDepot->id;
            }
        }

        if ($shopId === null) {
            $shopId = $user->shop_id !== null
                ? (string) $user->shop_id
                : ($user->tenant_id ? (string) $user->tenant_id : null);
        }

        return $shopId;
    }
}

