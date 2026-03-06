@extends('commerce.exports.base')

@section('title', 'Stock Global Commerce')
@section('subtitle')
    Aperçu des niveaux de stock par produit.
@endsection
@section('page-size', 'A4 landscape')

@section('content')
    <div class="summary-box" style="margin-bottom: 16px;">
        <div class="summary-title">Résumé du stock</div>
        <div class="summary-row">
            <span class="summary-label">Total produits actifs</span>
            <span class="summary-value">{{ $summary['total_products'] ?? 0 }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Stock faible</span>
            <span class="summary-value text-warning">{{ $summary['low_stock'] ?? 0 }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">En rupture</span>
            <span class="summary-value text-danger">{{ $summary['out_of_stock'] ?? 0 }}</span>
        </div>
        <div class="summary-row summary-total">
            <span class="summary-label">Valeur totale du stock (prix vente)</span>
            <span class="summary-value">
                {{ number_format($summary['total_value'] ?? 0, 2, ',', ' ') }}
                {{ $summary['currency'] ?? ($header['currency'] ?? 'CDF') }}
            </span>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 14%;">SKU</th>
                <th style="width: 30%;">Produit</th>
                <th style="width: 20%;">Catégorie</th>
                <th class="align-right" style="width: 8%;">Stock</th>
                <th class="align-right" style="width: 8%;">Min.</th>
                <th class="align-right" style="width: 14%;">Valeur stock</th>
            </tr>
        </thead>
        <tbody>
            @php $i = 1; @endphp
            @foreach($items as $item)
                <tr>
                    <td class="align-center">{{ $i++ }}</td>
                    <td class="font-mono nowrap">{{ $item['sku'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['category'] }}</td>
                    <td class="align-right">{{ number_format($item['stock'], 2, ',', ' ') }}</td>
                    <td class="align-right">{{ number_format($item['minimum_stock'], 2, ',', ' ') }}</td>
                    <td class="align-right">
                        {{ number_format($item['stock_value'], 2, ',', ' ') }}
                        {{ $summary['currency'] ?? ($header['currency'] ?? 'CDF') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

