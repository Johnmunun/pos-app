@extends('commerce.exports.base')

@section('title', 'Liste des Ventes')
@section('subtitle', 'Historique des ventes Global Commerce')

@section('content')
    @if(!empty($summary))
        <div class="summary-box" style="margin-bottom: 12px;">
            <div class="summary-title">Résumé</div>
            <div class="summary-row">
                <span class="summary-label">Total ventes</span>
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
            <th style="width: 27%;">Client</th>
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
                <td>{{ $item['customer_name'] ?? 'Client comptoir' }}</td>
                <td class="align-right">
                    {{ number_format($item['total_amount'] ?? 0, 2, ',', ' ') }} {{ $item['currency'] ?? $header['currency'] ?? 'USD' }}
                </td>
                <td>{{ $item['currency'] ?? $header['currency'] ?? 'USD' }}</td>
                <td class="align-center">
                    @php $status = strtoupper($item['status'] ?? ''); @endphp
                    @if($status === 'COMPLETED')
                        <span class="badge badge-success">Terminée</span>
                    @elseif($status === 'CANCELLED')
                        <span class="badge badge-neutral">Annulée</span>
                    @else
                        <span class="badge badge-neutral">Brouillon</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="align-center text-muted" style="padding: 16px;">
                    Aucune vente trouvée.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection

