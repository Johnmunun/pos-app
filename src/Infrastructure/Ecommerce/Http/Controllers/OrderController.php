<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use App\Services\EcommerceOrderCreatedMailService;
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
use Src\Infrastructure\GlobalCommerce\Inventory\Models\ProductModel;
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
        private readonly FeatureLimitService $featureLimitService,
        private readonly EcommerceOrderCreatedMailService $ecommerceOrderCreatedMailService
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
        $pm = ($methodCode !== null && $methodCode !== '')
            ? PaymentMethodModel::query()
                ->where('shop_id', $shopId)
                ->where('code', $methodCode)
                ->first()
            : null;

        $productIds = array_values(array_unique(array_map(
            static fn (array $i): string => (string) $i['product_id'],
            $validated['items']
        )));
        $productModels = ProductModel::query()
            ->where('shop_id', $shopId)
            ->whereIn('id', $productIds)
            ->get(['id', 'product_type', 'mode_paiement']);

        $isImmediatePaymentItem = static function (array $item) use ($productModels): bool {
            $model = $productModels->firstWhere('id', (string) $item['product_id']);
            if ($model === null) {
                // Fallback sécurisé: comportement historique = paiement immédiat
                return true;
            }
            $isDigital = strtolower((string) ($model->product_type ?? 'physical')) === 'digital';
            $mode = (string) ($model->mode_paiement ?? 'paiement_immediat');

            return $isDigital || $mode !== 'paiement_livraison';
        };

        $immediateItemsRaw = [];
        $deliveryItemsRaw = [];
        foreach ($validated['items'] as $item) {
            if ($isImmediatePaymentItem($item)) {
                $immediateItemsRaw[] = $item;
            } else {
                $deliveryItemsRaw[] = $item;
            }
        }

        $requiresImmediateOnlinePayment = !empty($immediateItemsRaw);

        $availableMethods = PaymentMethodModel::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->get(['id', 'code', 'type', 'sort_order'])
            ->sortBy(fn ($m) => [(int) ($m->sort_order ?? 0), (string) $m->code])
            ->values();
        $fusionMethod = $availableMethods->first(fn ($m) => strtolower((string) $m->type) === 'fusionpay');
        $selectedMethodIsFusion = $pm !== null && strtolower((string) $pm->type) === 'fusionpay';

        $needsFusionPayment = $requiresImmediateOnlinePayment;

        $toItemDtos = static function (array $rawItems): array {
            return array_map(static function ($item) {
                return new OrderItemDTO(
                    (string) $item['product_id'],
                    (string) $item['product_name'],
                    $item['product_sku'] ?? null,
                    (float) $item['quantity'],
                    (float) $item['unit_price'],
                    (float) $item['discount_amount'],
                    $item['product_image_url'] ?? null
                );
            }, $rawItems);
        };

        $sumSubtotal = static function (array $rawItems): float {
            return (float) array_reduce($rawItems, static function ($carry, $item) {
                $line = ((float) $item['unit_price'] * (float) $item['quantity']) - (float) ($item['discount_amount'] ?? 0);

                return $carry + $line;
            }, 0.0);
        };

        $currency = strtoupper(trim((string) $validated['currency']));

        $tenantId = $user->tenant_id ? (string) $user->tenant_id : null;
        if ($tenantId === null) {
            $shopRow = Shop::query()->find($shopId);
            $tenantId = $shopRow ? (string) $shopRow->tenant_id : null;
        }
        $this->featureLimitService->assertCanRecordSale($tenantId);

        try {
            $createdOrders = [];
            $primaryOrder = null;
            $deliveryOrder = null;

            $subtotalTotal = max(0.00001, $sumSubtotal($validated['items']));
            $baseShipping = (float) $validated['shipping_amount'];
            $baseTax = (float) $validated['tax_amount'];
            $baseDiscount = (float) $validated['discount_amount'];

            $createAndNotify = function (CreateOrderDTO $createDto, ?string $mailNote = null) use ($request, &$createdOrders) {
                $order = $this->createOrderUseCase->execute($createDto);
                $createdOrders[] = $order;
                app(\App\Services\AppNotificationService::class)->notifyEcommerceOrder(
                    'Nouvelle commande e-commerce',
                    'Commande '.$order->getOrderNumber().' creee pour '.$order->getCustomerName().'.',
                    $request->user()?->tenant_id ? (int) $request->user()->tenant_id : null
                );
                $this->ecommerceOrderCreatedMailService->notifyOrderCreated($order, $mailNote);

                return $order;
            };

            if ($requiresImmediateOnlinePayment) {
                $immediateSubtotal = $sumSubtotal($immediateItemsRaw);
                $deliverySubtotal = $sumSubtotal($deliveryItemsRaw);

                $immediateRatio = min(1.0, max(0.0, $immediateSubtotal / $subtotalTotal));
                $immediateTax = round($baseTax * $immediateRatio, 2);
                $immediateDiscount = round($baseDiscount * $immediateRatio, 2);
                $deliveryTax = round($baseTax - $immediateTax, 2);
                $deliveryDiscount = round($baseDiscount - $immediateDiscount, 2);

                // Logique métier: les articles à livraison portent les frais de livraison.
                $immediateShipping = empty($deliveryItemsRaw) ? $baseShipping : 0.0;
                $deliveryShipping = empty($deliveryItemsRaw) ? 0.0 : $baseShipping;

                $fusionPaymentCode = $selectedMethodIsFusion
                    ? (string) $pm->code
                    : (string) ($fusionMethod->code ?? '');

                $immediateDto = new CreateOrderDTO(
                    $shopId,
                    $validated['customer_name'],
                    $validated['customer_email'],
                    $validated['customer_phone'] ?? null,
                    $validated['shipping_address'],
                    $validated['billing_address'] ?? null,
                    $immediateSubtotal,
                    $immediateShipping,
                    $immediateTax,
                    $immediateDiscount,
                    $currency,
                    $fusionPaymentCode,
                    $validated['notes'] ?? null,
                    Order::PAYMENT_STATUS_PENDING,
                    $toItemDtos($immediateItemsRaw),
                    $user->id
                );
                $primaryOrder = $createAndNotify($immediateDto);

                if (!empty($deliveryItemsRaw)) {
                    $deliveryMethodCode = null;
                    if ($pm !== null && strtolower((string) $pm->type) !== 'fusionpay') {
                        $deliveryMethodCode = (string) $pm->code;
                    } else {
                        $nonFusion = $availableMethods->first(fn ($m) => strtolower((string) $m->type) !== 'fusionpay');
                        $deliveryMethodCode = $nonFusion ? (string) $nonFusion->code : null;
                    }

                    $deliveryDto = new CreateOrderDTO(
                        $shopId,
                        $validated['customer_name'],
                        $validated['customer_email'],
                        $validated['customer_phone'] ?? null,
                        $validated['shipping_address'],
                        $validated['billing_address'] ?? null,
                        $deliverySubtotal,
                        $deliveryShipping,
                        $deliveryTax,
                        $deliveryDiscount,
                        $currency,
                        $deliveryMethodCode,
                        $validated['notes'] ?? null,
                        $paymentStatusIn,
                        $toItemDtos($deliveryItemsRaw),
                        $user->id
                    );
                    $deliveryOrder = $createAndNotify($deliveryDto, 'Commande a la livraison enregistree.');
                }
            } else {
                $items = $toItemDtos($validated['items']);
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
                    $currency,
                    $validated['payment_method'] ?? null,
                    $validated['notes'] ?? null,
                    $paymentStatusIn,
                    $items,
                    $user->id
                );
                $primaryOrder = $createAndNotify($dto);
            }

            if ($primaryOrder === null) {
                throw new \RuntimeException('Impossible de créer la commande principale.');
            }

            $digitalTokens = \Src\Infrastructure\Ecommerce\Models\OrderItemModel::where('order_id', $primaryOrder->getId())
                ->where('is_digital', true)
                ->whereNotNull('download_token')
                ->pluck('download_token')
                ->values()
                ->toArray();

            $redirectUrl = null;
            if (!empty($digitalTokens) && $primaryOrder->getPaymentStatus() === Order::PAYMENT_STATUS_PAID) {
                $redirectUrl = route('ecommerce.payment.success', ['token' => $digitalTokens[0]]);
            }

            $message = 'Commande créée avec succès.';
            if ($needsFusionPayment && $deliveryOrder !== null) {
                $message = 'Commande scindée automatiquement : paiement en ligne pour les articles immédiats et commande séparée pour les articles à la livraison.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'order' => [
                    'id' => $primaryOrder->getId(),
                    'order_number' => $primaryOrder->getOrderNumber(),
                ],
                'secondary_order' => $deliveryOrder ? [
                    'id' => $deliveryOrder->getId(),
                    'order_number' => $deliveryOrder->getOrderNumber(),
                ] : null,
                'needs_fusion_payment' => $needsFusionPayment,
                'online_payment_unavailable' => false,
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
