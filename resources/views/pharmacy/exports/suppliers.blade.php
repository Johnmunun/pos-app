@extends('pharmacy.exports.base')

@section('title', 'Liste des Fournisseurs')
@section('subtitle', 'Répertoire complet des fournisseurs')

@section('content')
    @if(!empty($summary))
    <div class="summary-box" style="margin-bottom: 12px;">
        <div class="summary-title">Résumé</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 33%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Total fournisseurs</span>
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
                <th style="width: 5%;">#</th>
                <th style="width: 22%;">Nom</th>
                <th style="width: 18%;">Contact</th>
                <th style="width: 15%;">Téléphone</th>
                <th style="width: 18%;">Email</th>
                <th class="align-center" style="width: 10%;">Commandes</th>
                <th class="align-center" style="width: 12%;">Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item['name'] }}</td>
                    <td>{{ $item['contact_person'] ?? '—' }}</td>
                    <td class="nowrap">{{ $item['phone'] ?? '—' }}</td>
                    <td>{{ $item['email'] ?? '—' }}</td>
                    <td class="align-center">{{ $item['total_orders'] ?? 0 }}</td>
                    <td class="align-center">
                        @if(($item['status'] ?? 'active') === 'active')
                            <span class="badge badge-success">Actif</span>
                        @else
                            <span class="badge badge-neutral">Inactif</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="align-center text-muted" style="padding: 20px;">Aucun fournisseur trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
