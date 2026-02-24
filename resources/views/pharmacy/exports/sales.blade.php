@extends('pharmacy.exports.base')

@section('title', 'Liste des Ventes')
@section('subtitle')
    @if(!empty($filters['from']) || !empty($filters['to']))
        Période: {{ $filters['from'] ?? '—' }} au {{ $filters['to'] ?? '—' }}
    @else
        Toutes les ventes
    @endif
@endsection

@section('content')
    @if(!empty($summary))
    <div class="summary-box" style="margin-bottom: 12px;">
        <div class="summary-title">Résumé des ventes</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Nombre de ventes</span>
                    <span class="summary-value">{{ $summary['total_sales'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Ventes payées</span>
                    <span class="summary-value text-success">{{ $summary['paid_count'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">En attente</span>
                    <span class="summary-value text-warning">{{ $summary['pending_count'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%;">
                <div class="summary-row">
                    <span class="summary-label">Total encaissé</span>
                    <span class="summary-value">{{ number_format($summary['total_amount'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 10%;">Référence</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 14%;">Client</th>
                <th style="width: 12%;">Vendeur</th>
                <th class="align-center" style="width: 6%;">Articles</th>
                <th class="align-right" style="width: 12%;">Total</th>
                <th class="align-right" style="width: 12%;">Payé</th>
                <th class="align-center" style="width: 10%;">Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="nowrap font-bold">{{ $item['reference'] ?? '—' }}</td>
                    <td class="nowrap">{{ $item['date'] ?? '—' }}</td>
                    <td>{{ $item['customer'] ?? 'Client comptoir' }}</td>
                    <td>{{ $item['seller'] ?? '—' }}</td>
                    <td class="align-center">{{ $item['items_count'] ?? 0 }}</td>
                    <td class="align-right nowrap">{{ number_format($item['total'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</td>
                    <td class="align-right nowrap">{{ number_format($item['paid'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</td>
                    <td class="align-center">
                        @php
                            $status = $item['status'] ?? 'pending';
                            $statusClass = match($status) {
                                'paid', 'completed' => 'badge-success',
                                'partial' => 'badge-warning',
                                'cancelled' => 'badge-danger',
                                default => 'badge-neutral'
                            };
                            $statusLabel = match($status) {
                                'paid', 'completed' => 'Payé',
                                'partial' => 'Partiel',
                                'pending' => 'En attente',
                                'cancelled' => 'Annulé',
                                default => $status
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="align-center text-muted" style="padding: 20px;">Aucune vente trouvée.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($items))
    <div class="summary-box" style="margin-top: 12px; width: 300px; margin-left: auto;">
        <div class="summary-row">
            <span class="summary-label">Total des ventes</span>
            <span class="summary-value">{{ number_format($summary['total_amount'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total encaissé</span>
            <span class="summary-value text-success">{{ number_format($summary['total_paid'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
        </div>
        <div class="summary-row summary-total">
            <span class="summary-label">SOLDE DÛ</span>
            <span class="summary-value">{{ number_format(($summary['total_amount'] ?? 0) - ($summary['total_paid'] ?? 0), 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
        </div>
    </div>
    @endif
@endsection
