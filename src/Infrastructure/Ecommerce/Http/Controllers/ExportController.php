<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Src\Infrastructure\Ecommerce\Models\CustomerModel;
use Src\Infrastructure\Ecommerce\Models\OrderModel;
use Src\Infrastructure\Pharmacy\Services\PharmacyExportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController
{
    public function __construct(
        private readonly PharmacyExportService $exportService
    ) {
    }

    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(403, 'User not authenticated.');
        }

        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;

        if (!$shopId && !$isRoot) {
            abort(403, 'Shop ID not found. Please contact administrator.');
        }
        if ($isRoot && !$shopId) {
            abort(403, 'Please select a shop first.');
        }

        return (string) $shopId;
    }

    private function streamSpreadsheet(Spreadsheet $spreadsheet, string $baseName): StreamedResponse
    {
        $filename = $baseName . '_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function customersExcel(Request $request): StreamedResponse
    {
        $shopId = $this->getShopId($request);
        $customers = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Clients Ecommerce');

        $headers = [
            '#',
            'Nom complet',
            'Email',
            'Téléphone',
            'Adresse livraison',
            'Adresse facturation',
            'Total commandes',
            'Total dépensé',
            'Actif',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $index = 1;
        foreach ($customers as $customer) {
            $sheet->fromArray([
                $index++,
                trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                $customer->email,
                $customer->phone,
                $customer->default_shipping_address,
                $customer->default_billing_address,
                $customer->total_orders,
                $customer->total_spent,
                $customer->is_active ? 'Oui' : 'Non',
            ], null, 'A' . $row);
            $row++;
        }

        return $this->streamSpreadsheet($spreadsheet, 'ecommerce_clients');
    }

    public function ordersExcel(Request $request): StreamedResponse
    {
        $shopId = $this->getShopId($request);

        $orders = OrderModel::query()
            ->where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ventes Ecommerce');

        $headers = [
            '#',
            'Réf. commande',
            'Date',
            'Client',
            'Email',
            'Téléphone',
            'Statut',
            'Statut paiement',
            'Sous-total',
            'Livraison',
            'Taxes',
            'Remise',
            'Total',
            'Devise',
            'Mode de paiement',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        $index = 1;
        foreach ($orders as $order) {
            $sheet->fromArray([
                $index++,
                $order->order_number,
                optional($order->created_at)->format('d/m/Y H:i'),
                $order->customer_name,
                $order->customer_email,
                $order->customer_phone,
                $order->status,
                $order->payment_status,
                $order->subtotal_amount,
                $order->shipping_amount,
                $order->tax_amount,
                $order->discount_amount,
                $order->total_amount,
                $order->currency,
                $order->payment_method,
            ], null, 'A' . $row);
            $row++;
        }

        return $this->streamSpreadsheet($spreadsheet, 'ecommerce_ventes');
    }

    public function customersPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $this->getShopId($request);

        $customers = CustomerModel::query()
            ->where('shop_id', $shopId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $items = [];
        $index = 1;
        foreach ($customers as $c) {
            $items[] = [
                'index' => $index++,
                'name' => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
                'email' => $c->email,
                'phone' => $c->phone,
                'shipping' => $c->default_shipping_address,
                'billing' => $c->default_billing_address,
                'orders' => $c->total_orders,
                'total' => $c->total_spent,
                'is_active' => (bool) $c->is_active,
            ];
        }

        $summary = [
            'total' => count($items),
            'active' => collect($items)->filter(fn ($i) => $i['is_active'])->count(),
            'inactive' => collect($items)->filter(fn ($i) => !$i['is_active'])->count(),
        ];

        return $this->exportService->exportPdf('commerce.exports.customers', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'ecommerce_clients');
    }

    public function ordersPdf(Request $request): Response
    {
        $header = $this->exportService->getExportHeader($request);
        $shopId = $this->getShopId($request);

        $orders = OrderModel::query()
            ->where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();

        $items = [];
        $index = 1;
        foreach ($orders as $o) {
            $items[] = [
                'index' => $index++,
                'id' => $o->order_number,
                'number' => $o->order_number,
                'date' => optional($o->created_at)->format('d/m/Y H:i'),
                'customer_name' => $o->customer_name,
                'customer' => $o->customer_name,
                'email' => $o->customer_email,
                'status' => $o->status,
                'payment_status' => $o->payment_status,
                'total_amount' => $o->total_amount,
                'total' => $o->total_amount,
                'currency' => $o->currency,
            ];
        }

        $summary = [
            'total' => count($items),
            'amount' => collect($items)->sum('total'),
            'total_amount' => collect($items)->sum('total'),
        ];

        return $this->exportService->exportPdf('commerce.exports.sales', [
            'header' => $header,
            'items' => $items,
            'summary' => $summary,
        ], 'ecommerce_ventes');
    }
}

