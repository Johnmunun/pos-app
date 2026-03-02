@extends('pharmacy.exports.base')

@section('title', 'Bon de Commande')
@section('subtitle')
    Référence: {{ $purchase_order['reference'] ?? '—' }}
@endsection

@section('content')
    <div style="margin-bottom: 20px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 20px;">
                    <div style="background-color: #f8fafc; padding: 12px; border-radius: 4px; border-left: 4px solid #3b82f6;">
                        <div style="font-weight: bold; color: #1e40af; margin-bottom: 8px; font-size: 11px;">INFORMATIONS FOURNISSEUR</div>
                        <div style="font-size: 10px; color: #1e293b; line-height: 1.6;">
                            <div style="font-weight: 600; margin-bottom: 4px;">{{ $purchase_order['supplier_name'] ?? '—' }}</div>
                            @if(!empty($purchase_order['supplier_phone']))
                                <div>Tél: {{ $purchase_order['supplier_phone'] }}</div>
                            @endif
                        </div>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 20px;">
                    <div style="background-color: #f8fafc; padding: 12px; border-radius: 4px; border-left: 4px solid #10b981;">
                        <div style="font-weight: bold; color: #065f46; margin-bottom: 8px; font-size: 11px;">INFORMATIONS COMMANDE</div>
                        <div style="font-size: 10px; color: #1e293b; line-height: 1.6;">
                            <div><strong>Référence:</strong> {{ $purchase_order['reference'] ?? '—' }}</div>
                            <div><strong>Date commande:</strong> {{ $purchase_order['ordered_at'] ?? $purchase_order['created_at'] ?? '—' }}</div>
                            @if(!empty($purchase_order['expected_at']))
                                <div><strong>Livraison prévue:</strong> {{ $purchase_order['expected_at'] }}</div>
                            @endif
                            @if(!empty($purchase_order['received_at']))
                                <div><strong>Date réception:</strong> {{ $purchase_order['received_at'] }}</div>
                            @endif
                            <div style="margin-top: 6px;">
                                <strong>Statut:</strong> 
                                <span style="
                                    padding: 2px 8px;
                                    border-radius: 3px;
                                    font-size: 9px;
                                    font-weight: 600;
                                    @php
                                        $status = $purchase_order['status'] ?? 'DRAFT';
                                        $statusColors = [
                                            'RECEIVED' => 'background-color: #d1fae5; color: #065f46;',
                                            'PARTIALLY_RECEIVED' => 'background-color: #fef3c7; color: #92400e;',
                                            'CONFIRMED' => 'background-color: #dbeafe; color: #1e40af;',
                                            'CANCELLED' => 'background-color: #fee2e2; color: #991b1b;',
                                            'DRAFT' => 'background-color: #f1f5f9; color: #475569;',
                                        ];
                                    @endphp
                                    {{ $statusColors[$status] ?? 'background-color: #f1f5f9; color: #475569;' }}
                                ">
                                    {{ $purchase_order['status_label'] ?? $purchase_order['status'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Produit</th>
                <th class="align-right" style="width: 12%;">Qté commandée</th>
                <th class="align-right" style="width: 12%;">Qté reçue</th>
                <th class="align-right" style="width: 18%;">Prix unitaire</th>
                <th class="align-right" style="width: 18%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div style="font-weight: 600; color: #1e293b; margin-bottom: 2px;">{{ $line['product_name'] ?? '—' }}</div>
                        @if(!empty($line['product_code']))
                            <div style="font-size: 9px; color: #64748b;">Code: {{ $line['product_code'] }}</div>
                        @endif
                    </td>
                    <td class="align-right">{{ number_format($line['ordered_quantity'] ?? 0, 0, ',', ' ') }}</td>
                    <td class="align-right">
                        @php
                            $received = $line['received_quantity'] ?? 0;
                            $ordered = $line['ordered_quantity'] ?? 0;
                            $isComplete = $received >= $ordered;
                        @endphp
                        <span style="color: {{ $isComplete ? '#065f46' : '#92400e' }}; font-weight: {{ $isComplete ? '600' : '500' }};">
                            {{ number_format($received, 0, ',', ' ') }}
                        </span>
                    </td>
                    <td class="align-right nowrap">{{ number_format($line['unit_cost'] ?? 0, 2, ',', ' ') }} {{ $line['currency'] ?? $purchase_order['currency'] ?? 'CDF' }}</td>
                    <td class="align-right nowrap font-bold">{{ number_format($line['line_total'] ?? 0, 2, ',', ' ') }} {{ $line['currency'] ?? $purchase_order['currency'] ?? 'CDF' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="align-center text-muted" style="padding: 20px;">Aucune ligne dans cette commande.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f8fafc; border-top: 2px solid #cbd5e1;">
                <td colspan="5" class="align-right" style="padding: 12px; font-weight: 600; color: #1e293b;">
                    TOTAL
                </td>
                <td class="align-right nowrap" style="padding: 12px; font-size: 12px; font-weight: bold; color: #1e40af;">
                    {{ number_format($purchase_order['total_amount'] ?? 0, 2, ',', ' ') }} {{ $purchase_order['currency'] ?? 'CDF' }}
                </td>
            </tr>
        </tfoot>
    </table>
@endsection
