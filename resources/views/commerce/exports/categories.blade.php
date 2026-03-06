@extends('commerce.exports.base')

@section('title', 'Liste des Catégories')
@section('subtitle', 'Hiérarchie des catégories Global Commerce')

@section('content')
    @if(!empty($summary))
        <div class="summary-box" style="margin-bottom: 12px;">
            <div class="summary-title">Résumé</div>
            <div class="summary-row">
                <span class="summary-label">Total catégories</span>
                <span class="summary-value">{{ $summary['total'] ?? 0 }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Actives</span>
                <span class="summary-value text-success">{{ $summary['active'] ?? 0 }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Inactives</span>
                <span class="summary-value text-muted">{{ $summary['inactive'] ?? 0 }}</span>
            </div>
        </div>
    @endif

    <table class="data-table">
        <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 25%;">Nom</th>
            <th style="width: 30%;">Description</th>
            <th style="width: 20%;">Parent</th>
            <th class="align-right" style="width: 10%;">Ordre</th>
            <th class="align-center" style="width: 10%;">Statut</th>
        </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item['index'] }}</td>
                <td class="font-bold">{{ $item['name'] }}</td>
                <td>{{ $item['description'] ?? '—' }}</td>
                <td>{{ $item['parent'] ?? '—' }}</td>
                <td class="align-right">{{ $item['sort_order'] ?? 0 }}</td>
                <td class="align-center">
                    @if($item['is_active'])
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-neutral">Inactive</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="align-center text-muted" style="padding: 16px;">
                    Aucune catégorie trouvée.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection

