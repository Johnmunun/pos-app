@extends('pharmacy.exports.base')

@section('title', 'Historique des Mouvements')
@section('subtitle')
    @if(!empty($filters['from']) || !empty($filters['to']))
        Période: {{ $filters['from'] ?? '—' }} au {{ $filters['to'] ?? '—' }}
    @else
        Tous les mouvements
    @endif
@endsection

@section('content')
    @if(!empty($summary))
    <div class="summary-box" style="margin-bottom: 12px;">
        <div class="summary-title">Résumé des mouvements</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Total mouvements</span>
                    <span class="summary-value">{{ $summary['total_movements'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Entrées</span>
                    <span class="summary-value text-success">+{{ $summary['total_in'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Sorties</span>
                    <span class="summary-value text-danger">-{{ $summary['total_out'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%;">
                <div class="summary-row">
                    <span class="summary-label">Solde net</span>
                    <span class="summary-value">{{ ($summary['total_in'] ?? 0) - ($summary['total_out'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 12%;">Date</th>
                <th style="width: 20%;">Produit</th>
                <th style="width: 10%;">Code</th>
                <th class="align-center" style="width: 10%;">Type</th>
                <th class="align-right" style="width: 10%;">Quantité</th>
                <th class="align-right" style="width: 10%;">Stock avant</th>
                <th class="align-right" style="width: 10%;">Stock après</th>
                <th style="width: 13%;">Motif</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="nowrap">{{ $item['date'] ?? '—' }}</td>
                    <td class="font-bold">{{ $item['product_name'] }}</td>
                    <td class="nowrap">{{ $item['product_code'] ?? '—' }}</td>
                    <td class="align-center">
                        @php
                            $type = $item['type'] ?? 'adjustment';
                            $typeClass = match($type) {
                                'in', 'purchase', 'return' => 'badge-success',
                                'out', 'sale', 'loss' => 'badge-danger',
                                default => 'badge-neutral'
                            };
                            $typeLabel = match($type) {
                                'in' => 'Entrée',
                                'out' => 'Sortie',
                                'purchase' => 'Achat',
                                'sale' => 'Vente',
                                'return' => 'Retour',
                                'loss' => 'Perte',
                                'adjustment' => 'Ajustement',
                                default => $type
                            };
                        @endphp
                        <span class="badge {{ $typeClass }}">{{ $typeLabel }}</span>
                    </td>
                    <td class="align-right nowrap">
                        @if(($item['quantity'] ?? 0) > 0)
                            <span class="text-success font-bold">+{{ $item['quantity'] }}</span>
                        @else
                            <span class="text-danger font-bold">{{ $item['quantity'] }}</span>
                        @endif
                    </td>
                    <td class="align-right">{{ $item['stock_before'] ?? '—' }}</td>
                    <td class="align-right font-bold">{{ $item['stock_after'] ?? '—' }}</td>
                    <td>{{ $item['reason'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="align-center text-muted" style="padding: 20px;">Aucun mouvement trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
