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
use Src\Domain\Ecommerce\Entities\Order;
use Src\Infrastructure\Ecommerce\Models\PaymentMethodModel;
use Src\Application\Billing\Services\FeatureLimitService;
use App\Models\Shop;

class OrderController
{
    public function __construct(
        private readonly CreateOrderUseCase $createOrderUseCase,
        private readonly UpdateOrderStatusUseCase $updateOrderStatusUseCase,
        private readonly UpdatePaymentStatusUseCase $updatePaymentStatusUseCase,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderItemRepositoryInterface $orderItemRepository,
        private readonly FeatureLimitService $featureLimitService
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
        $search = $request->input('search', '');

        $query = \Src\Infrastructure\Ecommerce\Models\OrderModel::query()->where('shop_id', $shopId);

        if ($from) {
            $query->where('created_at', '>=', $from->copy()->startOfDay());
        }
        if ($to) {
            $query->where('created_at', '<=', $to->copy()->endOfDay());
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_email', 'like', $term);
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(10, min(100, $perPage));
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        $ordersData = collect($paginator->items())->map(function ($model) {
            return [
                'id' => $model->id,
                'order_number' => $model->order_number,
                'status' => $model->status,
                'customer_name' => $model->customer_name,
                'customer_email' => $model->customer_email,
                'total_amount' => (float) $model->total_amount,
                'currency' => $model->currency ?? 'USD',
                'payment_status' => $model->payment_status,
                'created_at' => $model->created_at?->format('d/m/Y H:i') ?? '',
            ];
        })->all();

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
                'search' => $search,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
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
        $itemModels = \Src\Infrastructure\Ecommerce\Models\OrderItemModel::where('order_id', $id)->get()->keyBy('id');

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
            'items' => array_map(function ($item) use ($itemModels) {
                $model = $itemModels[$item->getId()] ?? null;
                $downloadLink = null;
                if ($model && $model->is_digital && $model->download_token) {
                    $downloadLink = route('ecommerce.download', ['token' => $model->download_token]);
                }
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
                    'is_digital' => $model ? (bool) $model->is_digital : false,
                    'download_link' => $downloadLink,
                ];
            }, $items),
        ];

        return Inertia::render('Ecommerce/Orders/Show', [
            'order' => $orderData,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
            $user = $request->user();
            if (!$user || (!$user->hasPermission('ecommerce.order.create') && !$user->hasPermission('ecommerce.create'))) {
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
            'payment_status' => 'nullable|string|in:pending,paid,failed,refunded',
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

        $paymentStatusIn = $validated['payment_status'] ?? Order::PAYMENT_STATUS_PENDING;
        $methodCode = $validated['payment_method'] ?? null;
        $needsFusionPayment = false;
        if ($methodCode !== null && $methodCode !== '') {
            $pm = PaymentMethodModel::query()
                ->where('shop_id', $shopId)
                ->where('code', $methodCode)
                ->first();
            if ($pm !== null && strtolower((string) $pm->type) === 'fusionpay') {
                $needsFusionPayment = true;
                $paymentStatusIn = Order::PAYMENT_STATUS_PENDING;
            }
        }

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
            $paymentStatusIn,
            $items,
            $user->id
        );

        $tenantId = $user->tenant_id ? (string) $user->tenant_id : null;
        if ($tenantId === null) {
            $shopRow = Shop::query()->find($shopId);
            $tenantId = $shopRow ? (string) $shopRow->tenant_id : null;
        }
        $this->featureLimitService->assertCanRecordSale($tenantId);

        try {
            $order = $this->createOrderUseCase->execute($dto);
            app(\App\Services\AppNotificationService::class)->notifyEcommerceOrder(
                'Nouvelle commande e-commerce',
                'Commande '.$order->getOrderNumber().' creee pour '.$order->getCustomerName().'.',
                $request->user()?->tenant_id ? (int) $request->user()->tenant_id : null
            );

            $digitalTokens = \Src\Infrastructure\Ecommerce\Models\OrderItemModel::where('order_id', $order->getId())
                ->where('is_digital', true)
                ->whereNotNull('download_token')
                ->pluck('download_token')
                ->values()
                ->toArray();

            $redirectUrl = null;
            if (!empty($digitalTokens) && $order->getPaymentStatus() === Order::PAYMENT_STATUS_PAID) {
                $redirectUrl = route('ecommerce.payment.success', ['token' => $digitalTokens[0]]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès.',
                'order' => [
                    'id' => $order->getId(),
                    'order_number' => $order->getOrderNumber(),
                ],
                'needs_fusion_payment' => $needsFusionPayment,
                'digital_download_tokens' => $digitalTokens,
                'redirect_url' => $redirectUrl,
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
        $user = $request->user();
        if (
            !$user
            || (
                !$user->hasPermission('ecommerce.order.status.update')
                && !$user->hasPermission('ecommerce.order.update')
                && !$user->hasPermission('module.ecommerce')
            )
        ) {
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
        $user = $request->user();
        if (
            !$user
            || (
                !$user->hasPermission('ecommerce.order.payment.update')
                && !$user->hasPermission('ecommerce.order.update')
                && !$user->hasPermission('module.ecommerce')
            )
        ) {
            abort(403, 'Vous n\'avez pas la permission de modifier les commandes.');
        }

        $validated = $request->validate([
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
        ]);

        try {
            $order = $this->updatePaymentStatusUseCase->execute($id, $validated['payment_status']);
            if (($validated['payment_status'] ?? '') === 'paid') {
                app(\App\Services\AppNotificationService::class)->notifyEcommerceOrder(
                    'Paiement e-commerce confirme',
                    'La commande '.$order->getOrderNumber().' est marquee payee.',
                    $request->user()?->tenant_id ? (int) $request->user()->tenant_id : null
                );
            }

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
