@extends('pharmacy.exports.base')

@section('title', 'Fiche de Mouvement de Stock')
@section('subtitle', 'Mouvement #' . substr($movement['id'], 0, 8))

@section('content')
    {{-- Informations du mouvement --}}
    <div class="summary-box" style="margin-bottom: 16px;">
        <div class="summary-title">Détails du mouvement</div>
        <div style="display: table; width: 100%;">
            <div style="display: table-cell; width: 33%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Type</span>
                    <span class="summary-value">
                        @php
                            $typeClass = match($movement['type']) {
                                'IN' => 'text-success',
                                'OUT' => 'text-danger',
                                'ADJUSTMENT' => 'text-warning',
                                default => ''
                            };
                        @endphp
                        <span class="{{ $typeClass }}" style="font-weight: bold;">{{ $movement['type_label'] }}</span>
                    </span>
                </div>
            </div>
            <div style="display: table-cell; width: 33%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Quantité</span>
                    <span class="summary-value font-bold">
                        @if($movement['type'] === 'IN')
                            <span class="text-success">+{{ $movement['quantity'] }}</span>
                        @elseif($movement['type'] === 'OUT')
                            <span class="text-danger">-{{ $movement['quantity'] }}</span>
                        @else
                            {{ $movement['quantity'] }}
                        @endif
                    </span>
                </div>
            </div>
            <div style="display: table-cell; width: 34%;">
                <div class="summary-row">
                    <span class="summary-label">Date</span>
                    <span class="summary-value">{{ $movement['created_at'] }}</span>
                </div>
            </div>
        </div>
        <div style="display: table; width: 100%; margin-top: 8px;">
            <div style="display: table-cell; width: 50%; padding-right: 10px;">
                <div class="summary-row">
                    <span class="summary-label">Référence</span>
                    <span class="summary-value">{{ $movement['reference'] ?: '—' }}</span>
                </div>
            </div>
            <div style="display: table-cell; width: 50%;">
                <div class="summary-row">
                    <span class="summary-label">Effectué par</span>
                    <span class="summary-value">{{ $movement['created_by_name'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Informations du produit --}}
    <div class="summary-box" style="margin-bottom: 16px;">
        <div class="summary-title">Informations produit</div>
        <table class="data-table" style="margin-top: 8px;">
            <thead>
                <tr>
                    <th style="width: 20%;">Code</th>
                    <th style="width: 35%;">Désignation</th>
                    <th style="width: 20%;">Catégorie</th>
                    <th class="align-right" style="width: 12%;">Stock actuel</th>
                    <th class="align-right" style="width: 13%;">Prix unit.</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="nowrap font-bold">{{ $product['code'] ?: '—' }}</td>
                    <td>{{ $product['name'] }}</td>
                    <td>{{ $product['category'] ?: '—' }}</td>
                    <td class="align-right font-bold">{{ $product['current_stock'] }}</td>
                    <td class="align-right nowrap">{{ number_format($product['price'], 2, ',', ' ') }} {{ $header['currency'] ?? 'CDF' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Signature --}}
    <div style="margin-top: 40px; display: table; width: 100%;">
        <div style="display: table-cell; width: 50%; text-align: center;">
            <div style="border-top: 1px solid #333; width: 200px; margin: 40px auto 0; padding-top: 5px;">
                Signature responsable stock
            </div>
        </div>
        <div style="display: table-cell; width: 50%; text-align: center;">
            <div style="border-top: 1px solid #333; width: 200px; margin: 40px auto 0; padding-top: 5px;">
                Signature vérificateur
            </div>
        </div>
    </div>

    {{-- Footer note --}}
    <div style="margin-top: 30px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 9px; color: #666;">
        <strong>Note :</strong> Ce document constitue une trace officielle du mouvement de stock. 
        Il doit être conservé pour les besoins d'audit et de contrôle.
        Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}.
    </div>
@endsection
