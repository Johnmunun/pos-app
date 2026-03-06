@extends('commerce.exports.base')

@section('title', 'Liste des Clients')
@section('subtitle', 'Répertoire clients Global Commerce')

@section('content')
    @if(!empty($summary))
        <div class="summary-box" style="margin-bottom: 12px;">
            <div class="summary-title">Résumé</div>
            <div class="summary-row">
                <span class="summary-label">Total clients</span>
                <span class="summary-value">{{ $summary['total'] ?? 0 }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Actifs</span>
                <span class="summary-value text-success">{{ $summary['active'] ?? 0 }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Inactifs</span>
                <span class="summary-value text-muted">{{ $summary['inactive'] ?? 0 }}</span>
            </div>
        </div>
    @endif

    <table class="data-table">
        <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 20%;">Code</th>
            <th style="width: 25%;">Nom</th>
            <th style="width: 20%;">Email</th>
            <th style="width: 20%;">Téléphone</th>
            <th class="align-center" style="width: 10%;">Actif</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item['index'] }}</td>
                <td class="font-bold nowrap">{{ $item['code'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['email'] ?? '—' }}</td>
                <td class="nowrap">{{ $item['phone'] ?? '—' }}</td>
                <td class="align-center">
                    @if($item['is_active'])
                        <span class="badge badge-success">Oui</span>
                    @else
                        <span class="badge badge-neutral">Non</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="align-center text-muted" style="padding: 16px;">
                    Aucun client trouvé.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection

