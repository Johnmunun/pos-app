<?php

namespace Src\Infrastructure\Pharmacy\Http\Controllers;

use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class CashRegisterController
{
    private function getModule(): string
    {
        $prefix = request()->route()?->getPrefix();
        return $prefix === 'hardware' ? 'Hardware' : 'Pharmacy';
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'Non authentifié.');
        }
        $shopId = null;
        $depotId = $request->session()->get('current_depot_id');
        if ($depotId && $user->tenant_id && \Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $shopByDepot = \App\Models\Shop::where('depot_id', $depotId)->where('tenant_id', $user->tenant_id)->first();
            if ($shopByDepot) {
                $shopId = (string) $shopByDepot->id;
            }
        }
        if ($shopId === null) {
            $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        }
        $userModel = UserModel::find($user->id);
        $isRoot = $userModel ? $userModel->isRoot() : false;
        if (!$shopId && !$isRoot) {
            abort(403, 'Boutique non assignée. Contactez l\'administrateur.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Veuillez sélectionner une boutique.');
        }
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $tenantId = $request->user()?->tenant_id ?? $shopId;

        $registers = CashRegister::query()
            ->where('shop_id', $shopId)
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(function (CashRegister $reg) {
                $openSession = $reg->openSession();
                return [
                    'id' => $reg->id,
                    'name' => $reg->name,
                    'code' => $reg->code,
                    'description' => $reg->description,
                    'initial_balance' => (float) $reg->initial_balance,
                    'is_active' => $reg->is_active,
                    'open_session' => $openSession ? [
                        'id' => $openSession->id,
                        'opened_at' => $openSession->opened_at->format('Y-m-d H:i'),
                        'opening_balance' => (float) $openSession->opening_balance,
                        'opened_by' => $openSession->opened_by,
                    ] : null,
                ];
            });

        return Inertia::render($this->getModule() . '/CashRegister/Index', [
            'cashRegisters' => $registers,
            'routePrefix' => $this->getModule() === 'Hardware' ? 'hardware' : 'pharmacy',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
            'initial_balance' => 'nullable|numeric|min:0',
        ]);

        $shopId = $this->getShopId($request);
        $tenantId = $request->user()?->tenant_id ?? $shopId;

        $exists = CashRegister::where('tenant_id', $tenantId)->where('code', $request->input('code'))->exists();
        if ($exists) {
            return response()->json(['message' => 'Une caisse avec ce code existe déjà.'], 422);
        }

        $reg = CashRegister::create([
            'tenant_id' => $tenantId,
            'shop_id' => $shopId,
            'name' => $request->input('name'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'initial_balance' => $request->input('initial_balance', 0),
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Caisse créée.', 'cash_register' => ['id' => $reg->id, 'name' => $reg->name, 'code' => $reg->code]], 201);
    }

    public function openSession(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $shopId = $this->getShopId($request);
        $reg = CashRegister::where('id', $id)->where('shop_id', $shopId)->first();
        if (!$reg) {
            return response()->json(['message' => 'Caisse introuvable.'], 404);
        }

        if ($reg->openSession()) {
            return response()->json(['message' => 'Cette caisse a déjà une session ouverte.'], 422);
        }

        $userId = (int) $request->user()->id;
        $openingBalance = (float) ($request->input('opening_balance') ?? 0);

        $session = CashRegisterSession::create([
            'cash_register_id' => $reg->id,
            'opened_by' => $userId,
            'opening_balance' => $openingBalance,
            'status' => CashRegisterSession::STATUS_OPEN,
            'opened_at' => now(),
        ]);

        return response()->json([
            'message' => 'Session ouverte.',
            'session' => [
                'id' => $session->id,
                'opened_at' => $session->opened_at->format('Y-m-d H:i'),
                'opening_balance' => (float) $session->opening_balance,
            ],
        ], 201);
    }

    public function closeSession(Request $request, int $sessionId): JsonResponse
    {
        $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shopId = $this->getShopId($request);
        $session = CashRegisterSession::with('cashRegister')
            ->where('id', $sessionId)
            ->whereHas('cashRegister', fn ($q) => $q->where('shop_id', $shopId))
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Session introuvable.'], 404);
        }
        if ($session->status !== CashRegisterSession::STATUS_OPEN) {
            return response()->json(['message' => 'Cette session est déjà fermée.'], 422);
        }

        $closingBalance = (float) $request->input('closing_balance');
        $notes = $request->input('notes');

        $expectedBalance = (float) DB::table('pharmacy_sales')
            ->where('cash_register_session_id', $session->id)
            ->where('status', 'COMPLETED')
            ->sum('paid_amount');

        $expectedBalance += (float) $session->opening_balance;
        $difference = $closingBalance - $expectedBalance;

        $session->update([
            'closed_by' => $request->user()->id,
            'closing_balance' => $closingBalance,
            'expected_balance' => $expectedBalance,
            'difference' => $difference,
            'status' => CashRegisterSession::STATUS_CLOSED,
            'closed_at' => now(),
            'notes' => $notes,
        ]);

        return response()->json([
            'message' => 'Session fermée.',
            'session' => [
                'id' => $session->id,
                'closing_balance' => $closingBalance,
                'expected_balance' => $expectedBalance,
                'difference' => $difference,
            ],
        ]);
    }

    /** Liste des caisses + session ouverte pour le POS (create). */
    public function listForPos(Request $request): JsonResponse
    {
        $shopId = $this->getShopId($request);

        $registers = CashRegister::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (CashRegister $reg) {
                $openSession = $reg->openSession();
                return [
                    'id' => $reg->id,
                    'name' => $reg->name,
                    'code' => $reg->code,
                    'open_session' => $openSession ? [
                        'id' => $openSession->id,
                        'opening_balance' => (float) $openSession->opening_balance,
                    ] : null,
                ];
            });

        return response()->json(['cash_registers' => $registers]);
    }
}
