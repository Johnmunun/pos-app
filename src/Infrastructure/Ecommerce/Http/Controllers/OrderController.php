<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Ecommerce\DTO\CreateOrderDTO;
use Src\Application\Ecommerce\DTO\OrderItemDTO;
use Src\Application\Ecommerce\UseCases\CreateOrderUseCase;
use Src\Application\Ecommerce\UseCases\UpdateOrderStatusUseCase;
use Src\Application\Ecommerce\UseCases\UpdatePaymentStatusUseCase;
use Src\Domain\Ecommerce\Repositories\OrderRepositoryInterface;
use Src\Domain\Ecommerce\Repositories\OrderItemRepositoryInterface;
use Carbon\Carbon;

class OrderController
{
    public function __construct(
        private readonly CreateOrderUseCase $createOrderUseCase,
        private readonly UpdateOrderStatusUseCase $updateOrderStatusUseCase,
        private readonly UpdatePaymentStatusUseCase $updatePaymentStatusUseCase,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderItemRepositoryInterface $orderItemRepository
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
            abort(403, 'Vous n\'avez pas la permission de voir les commandes.');
        }

        $shopId = $this->getShopId($request);
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : null;
        $status = $request->input('status');

        $orders = $this->orderRepository->findByShop(
            $shopId,
            $from ? \DateTimeImmutable::createFromMutable($from) : null,
            $to ? \DateTimeImmutable::createFromMutable($to) : null,
            $status
        );

        $ordersData = array_map(function ($order) {
            return [
                'id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'status' => $order->getStatus(),
                'customer_name' => $order->getCustomerName(),
                'customer_email' => $order->getCustomerEmail(),
                'total_amount' => $order->getTotal()->getAmount(),
                'currency' => $order->getCurrency(),
                'payment_status' => $order->getPaymentStatus(),
                'created_at' => $order->getCreatedAt()->format('d/m/Y H:i'),
            ];
        }, $orders);

        // Stats
        $stats = [
            'total' => $this->orderRepository->countByShop($shopId),
            'pending' => $this->orderRepository->countByShop($shopId, 'pending'),
            'confirmed' => $this->orderRepository->countByShop($shopId, 'confirmed'),
            'shipped' => $this->orderRepository->countByShop($shopId, 'shipped'),
            'delivered' => $this->orderRepository->countByShop($shopId, 'delivered'),
            'cancelled' => $this->orderRepository->countByShop($shopId, 'cancelled'),
        ];

        return Inertia::render('Ecommerce/Orders/Index', [
            'orders' => $ordersData,
            'stats' => $stats,
            'filters' => [
                'from' => $from?->format('Y-m-d'),
                'to' => $to?->format('Y-m-d'),
                'status' => $status,
            ],
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        if (!$request->user()?->hasPermission('ecommerce.view')) {
            abort(403, 'Vous n\'avez pas la permission de voir les commandes.');
        }

        $order = $this->orderRepository->findById($id);

        if (!$order) {
            abort(404, 'Commande introuvable.');
        }

        $items = $this->orderItemRepository->findByOrderId($id);

        $orderData = [
            'id' => $order->getId(),
            'order_number' => $order->getOrderNumber(),
            'status' => $order->getStatus(),
            'customer_name' => $order->getCustomerName(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_phone' => $order->getCustomerPhone(),
            'shipping_address' => $order->getShippingAddress(),
            'billing_address' => $order->getBillingAddress(),
            'subtotal_amount' => $order->getSubtotal()->getAmount(),
            'shipping_amount' => $order->getShippingAmount()->getAmount(),
            'tax_amount' => $order->getTaxAmount()->getAmount(),
            'discount_amount' => $order->getDiscountAmount()->getAmount(),
            'total_amount' => $order->getTotal()->getAmount(),
            'currency' => $order->getCurrency(),
            'payment_method' => $order->getPaymentMethod(),
            'payment_status' => $order->getPaymentStatus(),
            'notes' => $order->getNotes(),
            'confirmed_at' => $order->getConfirmedAt()?->format('d/m/Y H:i'),
            'shipped_at' => $order->getShippedAt()?->format('d/m/Y H:i'),
            'delivered_at' => $order->getDeliveredAt()?->format('d/m/Y H:i'),
            'cancelled_at' => $order->getCancelledAt()?->format('d/m/Y H:i'),
            'created_at' => $order->getCreatedAt()->format('d/m/Y H:i'),
            'items' => array_map(function ($item) {
                return [
                    'id' => $item->getId(),
                    'product_id' => $item->getProductId(),
                    'product_name' => $item->getProductName(),
                    'product_sku' => $item->getProductSku(),
                    'quantity' => $item->getQuantity()->getValue(),
                    'unit_price' => $item->getUnitPrice()->getAmount(),
                    'discount_amount' => $item->getDiscountAmount()->getAmount(),
                    'subtotal' => $item->getSubtotal()->getAmount(),
                    'product_image_url' => $item->getProductImageUrl(),
                ];
            }, $items),
        ];

        return Inertia::render('Ecommerce/Orders/Show', [
            'order' => $orderData,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()?->hasPermission('ecommerce.create')) {
            abort(403, 'Vous n\'avez pas la permission de créer des commandes.');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'shipping_address' => 'required|string',
            'billing_address' => 'nullable|string',
            'subtotal_amount' => 'required|numeric|min:0',
            'shipping_amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.product_name' => 'required|string',
            'items.*.product_sku' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'required|numeric|min:0',
            'items.*.product_image_url' => 'nullable|string',
        ]);

        $shopId = $this->getShopId($request);
        $user = $request->user();

        $items = array_map(function ($item) {
            return new OrderItemDTO(
                $item['product_id'],
                $item['product_name'],
                $item['product_sku'] ?? null,
                (float) $item['quantity'],
                (float) $item['unit_price'],
                (float) $item['discount_amount'],
                $item['product_image_url'] ?? null
            );
        }, $validated['items']);

        $dto = new CreateOrderDTO(
            $shopId,
            $validated['customer_name'],
            $validated['customer_email'],
            $validated['customer_phone'] ?? null,
            $validated['shipping_address'],
            $validated['billing_address'] ?? null,
            (float) $validated['subtotal_amount'],
            (float) $validated['shipping_amount'],
            (float) $validated['tax_amount'],
            (float) $validated['discount_amount'],
            $validated['currency'],
            $validated['payment_method'] ?? null,
            $validated['notes'] ?? null,
            $items,
            $user?->id
        );

        try {
            $order = $this->createOrderUseCase->execute($dto);

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès.',
                'order' => [
                    'id' => $order->getId(),
                    'order_number' => $order->getOrderNumber(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        if (!$request->user()?->hasPermission('ecommerce.update')) {
            abort(403, 'Vous n\'avez pas la permission de modifier les commandes.');
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,cancelled',
        ]);

        try {
            $order = $this->updateOrderStatusUseCase->execute($id, $validated['status']);

            return response()->json([
                'success' => true,
                'message' => 'Statut de la commande mis à jour.',
                'order' => [
                    'id' => $order->getId(),
                    'status' => $order->getStatus(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updatePaymentStatus(Request $request, string $id): JsonResponse
    {
        if (!$request->user()?->hasPermission('ecommerce.update')) {
            abort(403, 'Vous n\'avez pas la permission de modifier les commandes.');
        }

        $validated = $request->validate([
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
        ]);

        try {
            $order = $this->updatePaymentStatusUseCase->execute($id, $validated['payment_status']);

            return response()->json([
                'success' => true,
                'message' => 'Statut de paiement mis à jour.',
                'order' => [
                    'id' => $order->getId(),
                    'payment_status' => $order->getPaymentStatus(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if (!$request->user()?->hasPermission('ecommerce.delete')) {
            abort(403, 'Vous n\'avez pas la permission de supprimer des commandes.');
        }

        $order = $this->orderRepository->findById($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande introuvable.',
            ], 404);
        }

        // Annuler la commande au lieu de la supprimer
        try {
            $this->updateOrderStatusUseCase->execute($id, 'cancelled');

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
