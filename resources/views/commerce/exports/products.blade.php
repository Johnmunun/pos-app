@extends('commerce.exports.base')

@section('title', 'Liste des Produits')
@section('subtitle', 'Catalogue produits Global Commerce')

@section('content')
    @if(!empty($summary))
        <div class="summary-box" style="margin-bottom: 12px;">
            <div class="summary-title">Résumé</div>
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; width: 33%; padding-right: 10px;">
                    <div class="summary-row">
                        <span class="summary-label">Total produits</span>
                        <span class="summary-value">{{ $summary['total'] ?? 0 }}</span>
                    </div>
                </div>
                <div style="display: table-cell; width: 33%; padding-right: 10px;">
                    <div class="summary-row">
                        <span class="summary-label">Actifs</span>
                        <span class="summary-value text-success">{{ $summary['active'] ?? 0 }}</span>
                    </div>
                </div>
                <div style="display: table-cell; width: 34%;">
                    <div class="summary-row">
                        <span class="summary-label">Inactifs</span>
                        <span class="summary-value text-muted">{{ $summary['inactive'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <table class="data-table">
        <thead>
        <tr>
            <th style="width: 4%;">#</th>
            <th style="width: 14%;">SKU</th>
            <th style="width: 24%;">Nom</th>
            <th style="width: 18%;">Catégorie</th>
            <th class="align-right" style="width: 10%;">Prix achat</th>
            <th class="align-right" style="width: 10%;">Prix vente</th>
            <th class="align-right" style="width: 10%;">Stock</th>
            <th class="align-center" style="width: 10%;">Actif</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item['index'] }}</td>
                <td class="font-bold nowrap">{{ $item['sku'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['category'] }}</td>
                <td class="align-right">{{ number_format($item['purchase_price'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'USD' }}</td>
                <td class="align-right">{{ number_format($item['sale_price'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'USD' }}</td>
                <td class="align-right">{{ $item['stock'] ?? 0 }} / {{ $item['minimum_stock'] ?? 0 }}</td>
                <td class="align-center">
                    @if($item['is_active'])
                        <span class="badge badge-success">Actif</span>
                    @else
                        <span class="badge badge-neutral">Inactif</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="align-center text-muted" style="padding: 16px;">
                    Aucun produit trouvé.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection

