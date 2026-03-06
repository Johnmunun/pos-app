@extends('commerce.exports.base')

@section('title', 'Liste des Achats')
@section('subtitle', 'Historique des bons de commande Global Commerce')

@section('content')
    @if(!empty($summary))
        <div class="summary-box" style="margin-bottom: 12px;">
            <div class="summary-title">Résumé</div>
            <div class="summary-row">
                <span class="summary-label">Total achats</span>
                <span class="summary-value">{{ $summary['total'] ?? 0 }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Montant total</span>
                <span class="summary-value">
                    {{ number_format($summary['total_amount'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'USD' }}
                </span>
            </div>
        </div>
    @endif

    <table class="data-table">
        <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 20%;">Référence</th>
            <th style="width: 18%;">Date</th>
            <th style="width: 27%;">Fournisseur</th>
            <th class="align-right" style="width: 15%;">Montant</th>
            <th style="width: 10%;">Devise</th>
            <th class="align-center" style="width: 10%;">Statut</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item['index'] }}</td>
                <td class="font-bold">{{ $item['id'] }}</td>
                <td class="nowrap">{{ $item['date'] }}</td>
                <td>{{ $item['supplier_name'] ?? '—' }}</td>
                <td class="align-right">
                    {{ number_format($item['total_amount'] ?? 0, 2, ',', ' ') }} {{ $item['currency'] ?? $header['currency'] ?? 'USD' }}
                </td>
                <td>{{ $item['currency'] ?? $header['currency'] ?? 'USD' }}</td>
                <td class="align-center">
                    @php $status = strtoupper($item['status'] ?? ''); @endphp
                    @if($status === 'RECEIVED')
                        <span class="badge badge-success">Réceptionné</span>
                    @else
                        <span class="badge badge-neutral">{{ $item['status'] ?? 'En attente' }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="align-center text-muted" style="padding: 16px;">
                    Aucun achat trouvé.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection

