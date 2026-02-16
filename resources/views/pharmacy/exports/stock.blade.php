@extends('pharmacy.exports.base')

@section('title', 'État du Stock')
@section('subtitle', 'Liste des produits en stock')
@section('page-size', 'A4 landscape')

@section('content')
    @if(!empty($summary))
    <div class="summary-box" style="margin-bottom: 12px;">
        <div class="summary-title">Résumé</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Total produits</span>
                    <span class="summary-value">{{ $summary['total_products'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Stock faible</span>
                    <span class="summary-value text-warning">{{ $summary['low_stock'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Rupture de stock</span>
                    <span class="summary-value text-danger">{{ $summary['out_of_stock'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%;">
                <div class="summary-row">
                    <span class="summary-label">Valeur totale</span>
                    <span class="summary-value">{{ number_format($summary['total_value'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 12%;">Code</th>
                <th style="width: 25%;">Produit</th>
                <th style="width: 12%;">Catégorie</th>
                <th class="align-right" style="width: 10%;">Stock</th>
                <th class="align-right" style="width: 10%;">Seuil alerte</th>
                <th class="align-right" style="width: 12%;">Prix unitaire</th>
                <th class="align-right" style="width: 14%;">Valeur stock</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="nowrap">{{ $item['code'] ?? '—' }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['category'] ?? '—' }}</td>
                    <td class="align-right">
                        @if(($item['stock'] ?? 0) <= 0)
                            <span class="badge badge-danger">{{ $item['stock'] ?? 0 }}</span>
                        @elseif(($item['stock'] ?? 0) <= ($item['low_stock_threshold'] ?? 10))
                            <span class="badge badge-warning">{{ $item['stock'] ?? 0 }}</span>
                        @else
                            {{ $item['stock'] ?? 0 }}
                        @endif
                    </td>
                    <td class="align-right">{{ $item['low_stock_threshold'] ?? '—' }}</td>
                    <td class="align-right nowrap">{{ number_format($item['price'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</td>
                    <td class="align-right nowrap font-bold">{{ number_format($item['stock_value'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="align-center text-muted" style="padding: 20px;">Aucun produit trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($items))
    <div class="summary-box" style="margin-top: 12px; width: 300px; margin-left: auto;">
        <div class="summary-row summary-total">
            <span class="summary-label">VALEUR TOTALE DU STOCK</span>
            <span class="summary-value">{{ number_format($summary['total_value'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
        </div>
    </div>
    @endif
@endsection
