@extends('pharmacy.exports.base')

@section('title', 'Rapport des Expirations')
@section('subtitle', 'Suivi des produits expirés et à expirer')

@section('content')
    @if(!empty($summary))
    <div class="summary-box" style="margin-bottom: 12px;">
        <div class="summary-title">Résumé des expirations</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Total lots</span>
                    <span class="summary-value">{{ $summary['total_batches'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Expirés</span>
                    <span class="summary-value text-danger">{{ $summary['expired'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Expire bientôt</span>
                    <span class="summary-value text-warning">{{ $summary['expiring_soon'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%;">
                <div class="summary-row">
                    <span class="summary-label">Valeur à risque</span>
                    <span class="summary-value text-danger">{{ number_format($summary['at_risk_value'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 20%;">Produit</th>
                <th style="width: 12%;">Code</th>
                <th style="width: 12%;">N Lot</th>
                <th class="align-center" style="width: 10%;">Quantité</th>
                <th class="align-center" style="width: 12%;">Expiration</th>
                <th class="align-center" style="width: 10%;">Jours restants</th>
                <th class="align-center" style="width: 12%;">Statut</th>
                <th class="align-right" style="width: 12%;">Valeur</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item['product_name'] }}</td>
                    <td class="nowrap">{{ $item['product_code'] ?? '—' }}</td>
                    <td class="nowrap">{{ $item['batch_number'] ?? '—' }}</td>
                    <td class="align-center">{{ $item['quantity'] ?? 0 }}</td>
                    <td class="align-center nowrap">{{ $item['expiration_date'] ?? '—' }}</td>
                    <td class="align-center">
                        @php $days = $item['days_until_expiry'] ?? 0; @endphp
                        @if($days < 0)
                            <span class="text-danger font-bold">{{ abs($days) }}j dépassé</span>
                        @elseif($days <= 30)
                            <span class="text-warning font-bold">{{ $days }}j</span>
                        @else
                            {{ $days }}j
                        @endif
                    </td>
                    <td class="align-center">
                        @php
                            $status = $item['status'] ?? 'ok';
                            $statusClass = match($status) {
                                'expired' => 'badge-danger',
                                'expiring_soon', 'critical' => 'badge-warning',
                                default => 'badge-success'
                            };
                            $statusLabel = match($status) {
                                'expired' => 'Expiré',
                                'expiring_soon' => 'Expire bientôt',
                                'critical' => 'Critique',
                                default => 'OK'
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="align-right nowrap">{{ number_format($item['value'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="align-center text-muted" style="padding: 20px;">Aucune expiration trouvée.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
