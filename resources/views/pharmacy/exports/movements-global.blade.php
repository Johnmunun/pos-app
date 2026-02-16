@extends('pharmacy.exports.base')

@section('title', 'Historique des Mouvements de Stock')
@section('subtitle')
    @if(!empty($filters['from']) || !empty($filters['to']))
        Période: {{ $filters['from'] ?? '—' }} au {{ $filters['to'] ?? '—' }}
    @else
        Tous les mouvements
    @endif
    @if(!empty($filters['product_name']))
        · Produit: {{ $filters['product_name'] }}
    @endif
    @if(!empty($filters['type']))
        · Type: {{ $filters['type'] }}
    @endif
@endsection
@section('page-size', 'A4 landscape')

@section('content')
    {{-- Résumé global --}}
    <div class="summary-box" style="margin-bottom: 16px;">
        <div class="summary-title">Résumé des mouvements</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Total mouvements</span>
                    <span class="summary-value">{{ $totals['total_movements'] }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Total entrées</span>
                    <span class="summary-value text-success">+{{ $totals['total_in'] }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Total sorties</span>
                    <span class="summary-value text-danger">-{{ $totals['total_out'] }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 25%;">
                <div class="summary-row">
                    <span class="summary-label">Total ajustements</span>
                    <span class="summary-value text-warning">{{ $totals['total_adjustment'] }}</span>
                </div>
            </div>
        </div>
    </div>

    @if(count($categories) === 0)
        <div style="text-align: center; padding: 40px; color: #666;">
            <p>Aucun mouvement trouvé pour les critères sélectionnés.</p>
        </div>
    @else
        {{-- Boucle sur les catégories --}}
        @foreach($categories as $category)
            <div style="margin-bottom: 20px; page-break-inside: avoid;">
                {{-- En-tête catégorie --}}
                <div style="background: #1f2937; color: white; padding: 8px 12px; font-weight: bold; font-size: 11px; margin-bottom: 0;">
                    {{ $category['name'] }}
                    <span style="float: right; font-weight: normal; font-size: 10px;">
                        Entrées: +{{ $category['totals']['in'] }} | 
                        Sorties: -{{ $category['totals']['out'] }} | 
                        Ajust.: {{ $category['totals']['adjustment'] }}
                    </span>
                </div>

                {{-- Produits de la catégorie --}}
                @foreach($category['products'] as $product)
                    <div style="margin-bottom: 12px; border: 1px solid #e5e7eb; border-top: none;">
                        {{-- En-tête produit --}}
                        <div style="background: #f3f4f6; padding: 6px 12px; font-size: 10px; border-bottom: 1px solid #e5e7eb;">
                            <strong>{{ $product['code'] }}</strong> - {{ $product['name'] }}
                            <span style="float: right; font-size: 9px; color: #666;">
                                E: <span class="text-success">+{{ $product['totals']['in'] }}</span> | 
                                S: <span class="text-danger">-{{ $product['totals']['out'] }}</span> | 
                                A: <span class="text-warning">{{ $product['totals']['adjustment'] }}</span>
                            </span>
                        </div>

                        {{-- Tableau des mouvements --}}
                        <table class="data-table" style="margin: 0; font-size: 9px;">
                            <thead>
                                <tr style="background: #f9fafb;">
                                    <th style="width: 12%; padding: 4px 8px;">Date</th>
                                    <th style="width: 25%; padding: 4px 8px;">Référence</th>
                                    <th class="align-center" style="width: 10%; padding: 4px 8px;">Type</th>
                                    <th class="align-right" style="width: 10%; padding: 4px 8px;">Quantité</th>
                                    <th style="width: 20%; padding: 4px 8px;">Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product['movements'] as $movement)
                                    <tr>
                                        <td class="nowrap" style="padding: 3px 8px;">{{ $movement['created_at'] }}</td>
                                        <td style="padding: 3px 8px;">{{ $movement['reference'] ?: '—' }}</td>
                                        <td class="align-center" style="padding: 3px 8px;">
                                            @php
                                                $badgeClass = match($movement['type']) {
                                                    'IN' => 'badge-success',
                                                    'OUT' => 'badge-danger',
                                                    'ADJUSTMENT' => 'badge-warning',
                                                    default => 'badge-neutral'
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}" style="font-size: 8px; padding: 2px 6px;">
                                                {{ $movement['type_label'] }}
                                            </span>
                                        </td>
                                        <td class="align-right font-bold" style="padding: 3px 8px;">
                                            @if($movement['type'] === 'IN')
                                                <span class="text-success">+{{ $movement['quantity'] }}</span>
                                            @elseif($movement['type'] === 'OUT')
                                                <span class="text-danger">-{{ $movement['quantity'] }}</span>
                                            @else
                                                {{ $movement['quantity'] }}
                                            @endif
                                        </td>
                                        <td style="padding: 3px 8px;">{{ $movement['created_by'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif

    {{-- Totaux globaux --}}
    <div class="summary-box" style="margin-top: 20px; width: 400px; margin-left: auto;">
        <div class="summary-title">Totaux globaux</div>
        <div class="summary-row">
            <span class="summary-label">Total des entrées</span>
            <span class="summary-value text-success">+{{ $totals['total_in'] }} unités</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total des sorties</span>
            <span class="summary-value text-danger">-{{ $totals['total_out'] }} unités</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total des ajustements</span>
            <span class="summary-value text-warning">{{ $totals['total_adjustment'] }} unités</span>
        </div>
        <div class="summary-row summary-total">
            <span class="summary-label">SOLDE NET</span>
            <span class="summary-value">{{ $totals['total_in'] - $totals['total_out'] + $totals['total_adjustment'] }} unités</span>
        </div>
    </div>

    {{-- Note de bas de page --}}
    <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 8px; color: #666;">
        <strong>Légende :</strong> 
        <span class="badge badge-success" style="font-size: 7px; padding: 1px 4px;">Entrée</span> = Réception de stock |
        <span class="badge badge-danger" style="font-size: 7px; padding: 1px 4px;">Sortie</span> = Vente ou consommation |
        <span class="badge badge-warning" style="font-size: 7px; padding: 1px 4px;">Ajustement</span> = Correction d'inventaire
        <br><br>
        Document généré le {{ now()->format('d/m/Y à H:i') }} pour audit et contrôle de gestion.
    </div>
@endsection
