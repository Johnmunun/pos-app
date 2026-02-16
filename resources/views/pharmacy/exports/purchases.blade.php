@extends('pharmacy.exports.base')

@section('title', 'Liste des Achats')
@section('subtitle')
    @if(!empty($filters['from']) || !empty($filters['to']))
        Période: {{ $filters['from'] ?? '—' }} au {{ $filters['to'] ?? '—' }}
    @else
        Tous les achats
    @endif
@endsection

@section('content')
    @if(!empty($summary))
    <div class="summary-box" style="margin-bottom: 12px;">
        <div class="summary-title">Résumé des achats</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Commandes totales</span>
                    <span class="summary-value">{{ $summary['total_orders'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Reçues</span>
                    <span class="summary-value text-success">{{ $summary['received_count'] ?? 0 }}</span>
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
                    <span class="summary-label">Montant total</span>
                    <span class="summary-value">{{ number_format($summary['total_amount'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 12%;">Référence</th>
                <th style="width: 12%;">Date</th>
                <th style="width: 20%;">Fournisseur</th>
                <th class="align-center" style="width: 8%;">Articles</th>
                <th class="align-right" style="width: 15%;">Montant</th>
                <th class="align-center" style="width: 13%;">Statut</th>
                <th style="width: 15%;">Livraison prévue</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="nowrap font-bold">{{ $item['reference'] ?? '—' }}</td>
                    <td class="nowrap">{{ $item['date'] ?? '—' }}</td>
                    <td>{{ $item['supplier'] ?? '—' }}</td>
                    <td class="align-center">{{ $item['items_count'] ?? 0 }}</td>
                    <td class="align-right nowrap">{{ number_format($item['total'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</td>
                    <td class="align-center">
                        @php
                            $status = $item['status'] ?? 'draft';
                            $statusClass = match($status) {
                                'received', 'completed' => 'badge-success',
                                'partial', 'ordered' => 'badge-info',
                                'cancelled' => 'badge-danger',
                                default => 'badge-neutral'
                            };
                            $statusLabel = match($status) {
                                'received', 'completed' => 'Reçu',
                                'partial' => 'Partiel',
                                'ordered' => 'Commandé',
                                'draft' => 'Brouillon',
                                'cancelled' => 'Annulé',
                                default => $status
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="nowrap">{{ $item['expected_date'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="align-center text-muted" style="padding: 20px;">Aucun achat trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($items))
    <div class="summary-box" style="margin-top: 12px; width: 300px; margin-left: auto;">
        <div class="summary-row summary-total">
            <span class="summary-label">TOTAL DES ACHATS</span>
            <span class="summary-value">{{ number_format($summary['total_amount'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
        </div>
    </div>
    @endif
@endsection
