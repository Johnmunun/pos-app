@extends('pharmacy.exports.base')

@section('title', 'Rapport d\'activité')
@section('subtitle')
    @if(!empty($filters['from']) || !empty($filters['to']))
        Période : {{ $filters['from'] ?? '—' }} au {{ $filters['to'] ?? '—' }}
    @else
        Rapport d'activité
    @endif
@endsection

@section('content')
    @php
        $report = $report ?? [];
        $period = $report['period'] ?? [];
        $sales = $report['sales'] ?? [];
        $purchases = $report['purchases'] ?? [];
        $movements = $report['movements'] ?? [];
        $stock = $report['stock'] ?? [];
        $currency = $header['currency'] ?? 'CDF';
    @endphp

    <div class="summary-box" style="margin-bottom: 14px;">
        <div class="summary-title">Ventes</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 50%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Chiffre d'affaires</span>
                    <span class="summary-value">{{ number_format($sales['total'] ?? 0, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 50%;">
                <div class="summary-row">
                    <span class="summary-label">Nombre de ventes</span>
                    <span class="summary-value">{{ $sales['count'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="summary-box" style="margin-bottom: 14px;">
        <div class="summary-title">Achats reçus</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 50%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Nombre de réceptions</span>
                    <span class="summary-value">{{ $purchases['count'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 50%;">
                <div class="summary-row">
                    <span class="summary-label">Montant total</span>
                    <span class="summary-value">{{ number_format($purchases['total'] ?? 0, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="summary-box" style="margin-bottom: 14px;">
        <div class="summary-title">Mouvements de stock</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 25%; padding-right: 8px;">
                <div class="summary-row">
                    <span class="summary-label">Total opérations</span>
                    <span class="summary-value">{{ $movements['total_ops'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 8px;">
                <div class="summary-row">
                    <span class="summary-label">Entrées</span>
                    <span class="summary-value text-success">+{{ $movements['qty_in'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 8px;">
                <div class="summary-row">
                    <span class="summary-label">Sorties</span>
                    <span class="summary-value text-danger">-{{ $movements['qty_out'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%;">
                <div class="summary-row">
                    <span class="summary-label">Ajustements</span>
                    <span class="summary-value">{{ $movements['qty_adjustment'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="summary-box" style="margin-bottom: 14px;">
        <div class="summary-title">État du stock (fin de période)</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 33%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Produits actifs</span>
                    <span class="summary-value">{{ $stock['product_count'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 33%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Valeur du stock</span>
                    <span class="summary-value">{{ number_format($stock['total_value'] ?? 0, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 34%;">
                <div class="summary-row">
                    <span class="summary-label">Produits en stock bas</span>
                    <span class="summary-value text-warning">{{ $stock['low_stock_count'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
