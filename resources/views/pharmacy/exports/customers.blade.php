@extends('pharmacy.exports.base')

@section('title', 'Liste des Clients')
@section('subtitle', 'Répertoire complet des clients')

@section('content')
    @if(!empty($summary))
    <div class="summary-box" style="margin-bottom: 12px;">
        <div class="summary-title">Résumé</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 33%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Total clients</span>
                    <span class="summary-value">{{ $summary['total'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 33%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Avec crédit</span>
                    <span class="summary-value text-warning">{{ $summary['with_credit'] ?? 0 }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 34%;">
                <div class="summary-row">
                    <span class="summary-label">Total crédit</span>
                    <span class="summary-value">{{ number_format($summary['total_credit'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 22%;">Nom</th>
                <th style="width: 15%;">Téléphone</th>
                <th style="width: 20%;">Email</th>
                <th style="width: 20%;">Adresse</th>
                <th class="align-right" style="width: 18%;">Solde</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item['name'] }}</td>
                    <td class="nowrap">{{ $item['phone'] ?? '—' }}</td>
                    <td>{{ $item['email'] ?? '—' }}</td>
                    <td>{{ $item['address'] ?? '—' }}</td>
                    <td class="align-right nowrap">
                        @if(($item['balance'] ?? 0) > 0)
                            <span class="text-danger font-bold">{{ number_format($item['balance'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
                        @else
                            {{ number_format($item['balance'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="align-center text-muted" style="padding: 20px;">Aucun client trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($items) && ($summary['total_credit'] ?? 0) > 0)
    <div class="summary-box" style="margin-top: 12px; width: 300px; margin-left: auto;">
        <div class="summary-row summary-total">
            <span class="summary-label">TOTAL CRÉDIT CLIENTS</span>
            <span class="summary-value text-danger">{{ number_format($summary['total_credit'] ?? 0, 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</span>
        </div>
    </div>
    @endif
@endsection
