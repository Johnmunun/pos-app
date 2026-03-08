<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Ecommerce\DTO\CreateCustomerDTO;
use Src\Application\Ecommerce\UseCases\CreateCustomerUseCase;
use Src\Domain\Ecommerce\Repositories\CustomerRepositoryInterface;

class CustomerController
{
    public function __construct(
        private readonly CreateCustomerUseCase $createCustomerUseCase,
        private readonly CustomerRepositoryInterface $customerRepository
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
}
